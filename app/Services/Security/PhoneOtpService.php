<?php

namespace App\Services\Security;

use App\Models\Back\PhoneOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

class PhoneOtpService
{
    public function create(User $user, string $purpose = 'login', ?string $ip = null): string
    {
        $code = (string) random_int(100000, 999999);

        PhoneOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        PhoneOtp::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'ip_address' => $ip,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        if (config('services.otp.channel') === 'sms') {
            if ($this->sendSmsVonage($user, $code)) {
                return $code;
            }
        }

        $this->sendOtpEmail($user, $code);

        return $code;
    }

    public function verify(User $user, string $code, string $purpose = 'login'): bool
    {
        $otp = PhoneOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$otp || $otp->isExpired()) {
            return false;
        }

        if ($otp->attempts >= 5) {
            return false;
        }

        $otp->increment('attempts');

        if (!Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update([
            'verified_at' => now(),
        ]);

        if (!$user->phone_verified_at) {
            $user->update([
                'phone_verified_at' => now(),
            ]);
        }

        return true;
    }

    private function sendSmsVonage(User $user, string $code): bool
    {
        $apiKey = config('services.vonage.api_key');
        $apiSecret = config('services.vonage.api_secret');
        $from = config('services.vonage.from', 'Data360');

        if (!$apiKey || !$apiSecret || !$user->phone) {
            Log::warning('Vonage OTP non configuré ou téléphone absent', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);

            return false;
        }

        try {
            $to = $this->normalizePhoneForVonage($user->phone);

            $client = new Client(new Basic($apiKey, $apiSecret));

            $message = new SMS(
                $to,
                $from,
                "Code Data 360 : {$code}. Expire dans 10 minutes."
            );

            $response = $client->sms()->send($message);
            $sms = $response->current();

            if ((string) $sms->getStatus() !== '0') {
                Log::error('Vonage OTP refusé', [
                    'user_id' => $user->id,
                    'phone_original' => $user->phone,
                    'phone_vonage' => $to,
                    'status' => $sms->getStatus(),
                    'message_id' => $sms->getMessageId(),
                ]);

                return false;
            }

            Log::info('Vonage OTP envoyé', [
                'user_id' => $user->id,
                'phone_vonage' => $to,
                'message_id' => $sms->getMessageId(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Exception Vonage OTP', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function sendOtpEmail(User $user, string $code): void
    {
        Mail::raw(
            "Bonjour {$user->name},\n\nVotre code de connexion Data 360 est : {$code}\n\nCe code expire dans 10 minutes.\n\nSi vous n’êtes pas à l’origine de cette demande, ignorez ce message.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Code de connexion Data 360');
            }
        );
    }

    private function normalizePhoneForVonage(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = preg_replace('/[^\d+]/', '', $phone);

        return ltrim($phone, '+');
    }
}
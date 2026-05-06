<?php

namespace App\Services\Security;

use App\Models\Back\PhoneOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

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

        $this->sendSms($user->phone, $code);

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

    private function sendSms(string $phone, string $code): void
{
    $sid = config('services.twilio.sid');
    $token = config('services.twilio.token');
    $from = config('services.twilio.from');

    if (!$sid || !$token || !$from) {
        \Log::warning('Twilio non configuré', [
            'phone' => $phone,
            'code_debug_local' => app()->isLocal() ? $code : null,
        ]);

        return;
    }

    try {
        $client = new \Twilio\Rest\Client($sid, $token);

        $client->messages->create($phone, [
            'from' => $from,
            'body' => "Votre code Data 360 est : {$code}. Il expire dans 10 minutes.",
        ]);
    } catch (\Throwable $e) {
        \Log::error('Erreur SMS Twilio', [
            'phone' => $phone,
            'from' => $from,
            'error' => $e->getMessage(),
            'code_debug_local' => app()->isLocal() ? $code : null,
        ]);
    }
}
}
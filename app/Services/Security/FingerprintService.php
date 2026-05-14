<?php

namespace App\Services\Security;

use Illuminate\Http\Request;

class FingerprintService
{
    public function makeHash(Request $request): string
    {
        $parts = [
            $request->input('fingerprint'),
            $request->userAgent(),
            $request->input('timezone'),
            $request->input('language'),
            $request->input('screen'),
        ];

        $payload = collect($parts)
            ->filter()
            ->map(fn ($value) => trim((string) $value))
            ->implode('|');

        if (!$payload) {
            $payload = $request->ip() . '|' . $request->userAgent();
        }

        return hash('sha256', $payload);
    }
}
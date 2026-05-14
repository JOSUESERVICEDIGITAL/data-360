<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;

class VpnDetectionService
{
    public function check(?string $ip): array
    {
        if (!$ip || in_array($ip, ['127.0.0.1', '::1'], true)) {
            return [
                'is_vpn' => false,
                'is_proxy' => false,
                'is_tor' => false,
                'is_datacenter' => false,
                'risk_score' => 0,
                'provider' => 'local',
                'raw' => null,
            ];
        }

        $apiKey = config('services.proxycheck.api_key');

        if (!$apiKey) {
            return $this->basicCheck($ip);
        }

        try {
            $response = Http::timeout(10)->get("https://proxycheck.io/v2/{$ip}", [
                'key' => $apiKey,
                'vpn' => 1,
                'risk' => 1,
            ]);

            $json = $response->json();
            $data = $json[$ip] ?? [];

            $proxy = strtolower((string) ($data['proxy'] ?? 'no')) === 'yes';
            $type = strtolower((string) ($data['type'] ?? ''));
            $risk = (int) ($data['risk'] ?? 0);

            return [
                'is_vpn' => str_contains($type, 'vpn'),
                'is_proxy' => $proxy,
                'is_tor' => str_contains($type, 'tor'),
                'is_datacenter' => str_contains($type, 'hosting') || str_contains($type, 'datacenter'),
                'risk_score' => $risk,
                'provider' => 'proxycheck',
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            return $this->basicCheck($ip, $e->getMessage());
        }
    }

    private function basicCheck(string $ip, ?string $error = null): array
    {
        return [
            'is_vpn' => false,
            'is_proxy' => false,
            'is_tor' => false,
            'is_datacenter' => false,
            'risk_score' => 0,
            'provider' => 'basic',
            'raw' => [
                'ip' => $ip,
                'error' => $error,
                'message' => 'Aucune clé ProxyCheck configurée.',
            ],
        ];
    }
}
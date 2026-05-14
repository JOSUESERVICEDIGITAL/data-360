<?php

namespace App\Services\Api;

use App\Models\Back\ApiLog;

class ApiLoggerService
{
    public function log(
        string $apiName,
        ?string $endpoint,
        mixed $query,
        ?int $statusCode,
        bool $success,
        mixed $requestData = null,
        mixed $responseData = null,
        ?string $errorMessage = null
    ): void {
        try {
            ApiLog::create([
                'api_name' => $apiName,
                'endpoint' => $endpoint,
                'query' => is_string($query) ? $query : json_encode($query, JSON_UNESCAPED_UNICODE),
                'status_code' => $statusCode,
                'success' => $success,
                'request_data' => $requestData,
                'response_data' => $responseData,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            // On ne bloque jamais la recherche à cause d’un log.
        }
    }
}
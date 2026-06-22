<?php

namespace App\Services;

use App\Models\IdempotencyKey;

class IdempotencyService
{
    public function find(string $key, string $endpoint): ?IdempotencyKey
    {
        return IdempotencyKey::query()
            ->where('key', $key)
            ->where('endpoint', $endpoint)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function store(string $key, string $endpoint, array $body, int $status): void
    {
        IdempotencyKey::create([
            'key' => $key,
            'endpoint' => $endpoint,
            'response_body' => $body,
            'status_code' => $status,
            'expires_at' => now()->addDay(),
        ]);
    }
}

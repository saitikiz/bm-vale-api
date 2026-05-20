<?php

namespace App\Services\PronetServices\Endpoints;

use App\Services\PronetServices\PronetClient;

class Ping
{
    public function __construct(private PronetClient $client) {}

    public static function methodName(): string
    {
        return 'ping';
    }

    public function __invoke(array $payload = []): array
    {
        $bodyForChecksum = []; // ping body boş
        return $this->client->request('GET', '/extapi/ping', [
            // ek option gerekirse buraya
        ], $bodyForChecksum);
    }
}

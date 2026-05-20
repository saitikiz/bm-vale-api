<?php

namespace App\Services\PronetServices\Endpoints;

use App\Services\PronetServices\PronetClient;

class GetBonusesAndFreeBets
{
    public function __construct(private PronetClient $client) {}

    public static function methodName(): string
    {
        return 'getBonusesAndFreeBets';
    }

    public function __invoke(array $payload = []): array
    {
        $bodyForChecksum = []; // body boş
        return $this->client->request('GET', '/external-api/getBonusesAndFreeBets', [
            // ek option gerekirse buraya
        ], $bodyForChecksum);
    }
}

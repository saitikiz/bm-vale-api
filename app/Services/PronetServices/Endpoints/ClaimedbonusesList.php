<?php

namespace App\Services\PronetServices\Endpoints;

use App\Services\PronetServices\PronetClient;
use Illuminate\Support\Facades\Log;

class ClaimedbonusesList
{
    public function __construct(private PronetClient $client) {}

    public static function methodName(): string
    {
        return 'claimedbonusesList';
    }

    public function __invoke(array $data = []): array
    {
        Log::info("ClaimedbonusesList Service Start ==============================");
        Log::info("Data:\n",$data);
        $body = [];

        // Kimlik alanları: üçünden en az biri zorunlu
        if (!empty($data['username'])) {
            $body['username'] = (string) $data['username'];
        }
        if (!empty($data['customerCode'])) {
            $body['customerCode'] = (string) $data['customerCode'];
        }
        if (isset($data['customerId']) && $data['customerId'] !== '') {
            $body['customerId'] = (int) $data['customerId'];
        }

        if (!isset($body['username']) && !isset($body['customerCode']) && !isset($body['customerId'])) {
            return [
                'ok'    => false,
                'error' => 'username veya customerCode veya customerId alanlarından en az biri zorunlu.',
            ];
        }
        Log::info("Request: \n", $body);
        $response =  $this->client->request(
            'POST',
            '/extapi/customer/claimedbonuses/list',
            [
                'json' => $body,
            ],
            $body
        );
        Log::info("Response: \n", $response);
        return $response;
    }
}

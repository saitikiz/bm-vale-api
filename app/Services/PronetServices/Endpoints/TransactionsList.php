<?php

namespace App\Services\PronetServices\Endpoints;

use App\Services\PronetServices\PronetClient;
use Illuminate\Support\Facades\Log;

class TransactionsList
{
    public function __construct(private PronetClient $client) {}

    public static function methodName(): string
    {
        return 'transactionsList';
    }

    public function __invoke(array $data = []): array
    {
        Log::info("TransactionList Service Start ==============================");
        Log::info("Data:\n",$data);

        // 1) Body’yi dışarıdan gelen $data üzerinden kuruyoruz
        $body = [];       // ROOT payload
        $filterBody = []; // body içindeki filtreler

        // Tarih filtreleri (opsiyonel)
        if (!empty($data['startDate'])) {
            $filterBody['startDate'] = (string) $data['startDate'];
        }
        if (!empty($data['endDate'])) {
            $filterBody['endDate'] = (string) $data['endDate'];
        }

        // Status: dizi veya "C,R" gelebilir
        if (!empty($data['status'])) {
            $status = $data['status'];
            if (is_string($status)) {
                $status = array_filter(array_map('trim', explode(',', $status)));
            }
            $filterBody['status'] = array_values((array) $status);
        }

        // masterTypeId (opsiyonel)
        if (isset($data['masterTypeId']) && $data['masterTypeId'] !== '') {
            $filterBody['masterTypeId'] = (int) $data['masterTypeId'];
        }

        // language (opsiyonel)
        if (!empty($data['language'])) {
            $body['language'] = (string) $data['language'];
        }

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

        if (!empty($filterBody)) {
            $body['body'] = $filterBody;
        }

        Log::info("Request: \n", $body);

        $response = $this->client->request(
            'POST',
            '/extapi/customer/transactions/list',
            [
                'json' => $body,
                // verify istersen override edebilirsin:
                // 'verify' => true/false,
            ],
            $body
        );
        Log::info("Response: \n", $response);

        return $response;
    }
}

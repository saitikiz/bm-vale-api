<?php

namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PronetClient
{
    protected string $baseUrl;
    protected string $apiUser;
    protected string $secret;
    protected bool $verifySsl;
    protected Client $http;
    protected Client $bonusHttp;

    public function __construct()
    {
        $this->baseUrl   = config('pronet.base_url');
        $this->apiUser   = config('pronet.api_user');
        $this->secret    = config('pronet.secret');
        $this->verifySsl = (bool) config('pronet.verify_ssl', false);

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 10,
        ]);

        $this->bonusHttp = new Client([
            'base_uri'        => config('pronet.bonus_history_url'),
            'timeout'         => config('pronet.bonus_history_timeout'),
            'connect_timeout' => 5,
        ]);
    }

    /**
     * Ortak checksum helper:
     * JSON(body) + secret -> SHA512 (raw) -> Base64
     */
    protected function makeChecksum(array $body): string
    {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha512', $json . $this->secret, true);
        return base64_encode($hash);
    }

    /* =====================
    *  PRONET PUBLIC API METODLARI
    * ===================== */

    /** /extapi/ping */
    public function ping(): array
    {
        $body = []; // ping için gövde boş
        $checksum = $this->makeChecksum($body);

        try {
            $response = $this->http->request('GET', '/extapi/ping', [
                'verify'  => $this->verifySsl, // testte false, prod"da true
                'headers' => [
                    'content-type' => 'application/json',
                    'api_username' => $this->apiUser,
                    'checksum'     => $checksum,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $decoded    = json_decode($rawBody, true);

            return [
                'ok'       => $statusCode === 200,
                'status'   => $statusCode,
                //'raw'      => $rawBody,
                'response' => $decoded ?? $rawBody,
            ];
        } catch (GuzzleException $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    public function getBonusHistoryByName($username): array
    {
        $jsonBody = json_encode(["username" => $username], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = $this->bonusHttp->request("POST", "/bonuses", [
                "headers" => [
                    "content-type" => "application/json",
                ],
                "body" => $jsonBody,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $decoded    = json_decode($rawBody, true);

            return [
                'ok'       => $statusCode === 200,
                'status'   => $statusCode,
                //'raw'      => $rawBody,
                'response' => $decoded ?? $rawBody,
            ];
        } catch (GuzzleException $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    /** /extapi/customer/transactions/list */
    public function transactionsList(array $data = []): array
    {
        // 1) Body"yi dışarıdan gelen $data üzerinden kuruyoruz
        $body = [];       // ROOT payload
        $filterBody = []; // body içindeki filtreler

        // Tarih filtreleri (opsiyonel)
        if (!empty($data['startDate'])) {
            $filterBody['startDate'] = (string) $data['startDate']; // örn: 2025-10-01T00:00:00Z
        }
        if (!empty($data['endDate'])) {
            $filterBody['endDate'] = (string) $data['endDate'];     // örn: 2025-10-24T23:59:59Z
        }

        // Status: dizi veya "C,R" gelebilir
        if (!empty($data['status'])) {
            $status = $data['status'];
            if (is_string($status)) {
                $status = array_filter(array_map('trim', explode(',', $status)));
            }
            $filterBody['status'] = array_values((array) $status); // ["C","R"]
        }

        // masterTypeId (opsiyonel)
        if (isset($data['masterTypeId']) && $data['masterTypeId'] !== '') {
            $filterBody['masterTypeId'] = (int) $data['masterTypeId'];
        }

        // language (opsiyonel)
        if (!empty($data['language'])) {
            $body['language'] = (string) $data['language']; // "en" | "tr" ...
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
        // 2) Checksum
        $checksum = $this->makeChecksum($body);
        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = $this->http->request('POST', '/extapi/customer/transactions/list', [
                'verify'  => $this->verifySsl,
                'headers' => [
                    'content-type' => 'application/json',
                    'api_username' => $this->apiUser,
                    'checksum'     => $checksum,
                ],
                'body' => $jsonBody,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $decoded    = json_decode($rawBody, true);

            return [
                'ok'       => $statusCode === 200,
                'status'   => $statusCode,
                'request'  => $body,
                //'raw'      => $rawBody,
                'response' => $decoded ?? $rawBody,
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /** /extapi/customer/accounts/list */
    public function accountsList(array $data = []): array
    {
        // 1) Body"yi dışarıdan gelen $data üzerinden kuruyoruz
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

        // 2) Checksum
        $checksum = $this->makeChecksum($body);
        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = $this->http->request('POST', '/extapi/customer/accounts/list', [
                'verify'  => $this->verifySsl,
                'headers' => [
                    'content-type' => 'application/json',
                    'api_username' => $this->apiUser,
                    'checksum'     => $checksum,
                ],
                'body' => $jsonBody,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $decoded    = json_decode($rawBody, true);

            return [
                'ok'       => $statusCode === 200,
                'status'   => $statusCode,
                'request'  => $body,
                //'raw'      => $rawBody,
                'response' => $decoded ?? $rawBody,
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /** /extapi/customer/claimedbonuses/list */
    public function claimedbonusesList(array $data = []): array
    {
        // 1) Body"yi dışarıdan gelen $data üzerinden kuruyoruz
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

        // 2) Checksum
        $checksum = $this->makeChecksum($body);
        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        try {
            $response = $this->http->request('POST', '/extapi/customer/claimedbonuses/list', [
                'verify'  => $this->verifySsl,
                'headers' => [
                    'content-type' => 'application/json',
                    'api_username' => $this->apiUser,
                    'checksum'     => $checksum,
                ],
                'body' => $jsonBody,
            ]);
            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $decoded    = json_decode($rawBody, true);

            return [
                'ok'       => $statusCode === 200,
                'status'   => $statusCode,
                'request'  => $body,
                //'raw'      => $rawBody,
                'response' => $decoded ?? $rawBody,
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    /**
     * /extapi/customer/member/balance
     *
     * Example Request:
     * {
     *  "username": "testbonus1",
     *  "customerCode": "",
     *  "customerId": ""
     * }
     *
     * Example Response:
     * Sadece kullanıcı bilgileri , anlık balance ve bonus balance döner
     * {
     *  "body": {
     *   "errorCode": 0,
     *   "description": "Successfull call",
     *   "balance": 0,
     *   "firstName": "bonus",
     *   "surname": "bonus",
     *   "userName": "testbonus1",
     *   "email": "bonustest1@hotmail.com",
     *   "currency": "TRY",
     *   "bonusBalance": 10,
     *   "customerCode": "2025107726215"
     *  },
     * "message": "success"
     * }
     * */
    public function memberBalance(array $data = []): array
    {
        // 1) Body"yi dışarıdan gelen $data üzerinden kuruyoruz
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

        // 2) Checksum
        $checksum = $this->makeChecksum($body);
        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = $this->http->request('POST', '/extapi/customer/member/balance', [
                'verify'  => $this->verifySsl,
                'headers' => [
                    'content-type' => 'application/json',
                    'api_username' => $this->apiUser,
                    'checksum'     => $checksum,
                ],
                'body' => $jsonBody,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $decoded    = json_decode($rawBody, true);

            return [
                'ok'       => $statusCode === 200,
                'status'   => $statusCode,
                'request'  => $body,
                //'raw'      => $rawBody,
                'response' => $decoded ?? $rawBody,
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sport Bet Masters List
     *
     * Pronet API: POST /extapi/customer/sportbetmasters/list
     *
     * Example Request:
     * {
     *   "body": {
     *     "startDate": "2021-04-22T12:51:17.911Z",
     *     "endDate": "2021-04-22T12:51:17.911Z"
     *   },
     *   "customerId": 123
     * }
     *
     */
    public function sportbetmastersList(array $data = []): array
    {
        // 1) Body"yi kur
        $body = [];

        // Tarihler
        if (!empty($data['startDate'])) {
            $body['startDate'] = (string) $data['startDate'];
        }
        if (!empty($data['endDate'])) {
            $body['endDate'] = (string) $data['endDate'];
        }

        // Ana request array'i (body dahil)
        $requestPayload = [];

        if (!empty($body)) {
            $requestPayload['body'] = $body;
        }

        // Kimlik alanları
        if (!empty($data['username'])) {
            $requestPayload['username'] = (string) $data['username'];
        }
        if (!empty($data['customerCode'])) {
            $requestPayload['customerCode'] = (string) $data['customerCode'];
        }
        if (isset($data['customerId']) && $data['customerId'] !== '') {
            $requestPayload['customerId'] = (int) $data['customerId'];
        }

        // Zorunlu kontrol
        if (!isset($requestPayload['username']) &&
            !isset($requestPayload['customerCode']) &&
            !isset($requestPayload['customerId'])) {
            return [
                'ok'    => false,
                'error' => 'username veya customerCode veya customerId alanlarından en az biri zorunlu.',
            ];
        }

        // 2) Checksum
        $checksum = $this->makeChecksum($requestPayload);
        $jsonBody = json_encode($requestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = $this->http->request('POST', '/extapi/customer/sportbetmasters/list', [
                'verify'  => $this->verifySsl,
                'headers' => [
                    'content-type' => 'application/json',
                    'api_username' => $this->apiUser,
                    'checksum'     => $checksum,
                ],
                'body' => $jsonBody,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $decoded    = json_decode($rawBody, true);

            return [
                'ok'       => $statusCode === 200,
                'status'   => $statusCode,
                'request'  => $requestPayload,
                //'raw'      => $rawBody,
                'response' => $decoded ?? $rawBody,
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    /** : : /extapi/customer/member/summary */
    public function memberSummary(array $data = []): array
    {
        // 1) Body"yi dışarıdan gelen $data üzerinden kuruyoruz
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

        // 2) Checksum
        $checksum = $this->makeChecksum($body);
        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = $this->http->request('POST', '/extapi/customer/member/summary', [
                'verify'  => $this->verifySsl,
                'headers' => [
                    'content-type' => 'application/json',
                    'api_username' => $this->apiUser,
                    'checksum'     => $checksum,
                ],
                'body' => $jsonBody,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $decoded    = json_decode($rawBody, true);

            return [
                'ok'       => $statusCode === 200,
                'status'   => $statusCode,
                'request'  => $body,
                //'raw'      => $rawBody,
                'response' => $decoded ?? $rawBody,
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    /* =====================
    *  BONUS API METODLARI
    * ===================== */
    /** /external-api/getBonusesAndFreeBets */
    public function getBonusesAndFreeBets(): array
    {
        $body = []; // ping için gövde boş
        $checksum = $this->makeChecksum($body);

        try {
            $response = $this->http->request('GET', '/external-api/getBonusesAndFreeBets', [
                'verify'  => $this->verifySsl, // testte false, prod"da true
                'headers' => [
                    'content-type' => 'application/json',
                    'api_username' => $this->apiUser,
                    'checksum'     => $checksum,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $decoded    = json_decode($rawBody, true);

            return [
                'ok'       => $statusCode === 200,
                'status'   => $statusCode,
                //'raw'      => $rawBody,
                'response' => $decoded ?? $rawBody,
            ];
        } catch (GuzzleException $e) {
            return [
                'ok'    => false,
                'error' => $e->getMessage(),
            ];
        }
    }




}

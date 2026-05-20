<?php

namespace App\Services\PronetServices;

use BadMethodCallException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PronetClient
{
    private array $endpoints = [];

    private string $baseUrl;
    private string $apiUser;
    private string $secret;
    private bool $verifySsl;

    private Client $http;

    public function __construct()
    {
        $this->baseUrl   = config('pronet.base_url');
        $this->apiUser   = config('pronet.api_user');
        $this->secret    = config('pronet.secret');
        $this->verifySsl = (bool) config('pronet.verify_ssl', true);

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 10,
        ]);

        $this->registerEndpoints();
    }

    public function checksum(array $body): string
    {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha512', $json . $this->secret, true);
        return base64_encode($hash);
    }

    public function request(string $method, string $uri, array $options = [], array $checksumBody = []): array
    {
        // Eğer json ile gönderilecekse, tek JSON string üretelim (Guzzle encode etmesin)
        if (isset($options['json'])) {
            $bodyArray = $options['json'];

            // checksumBody verilmediyse otomatik bodyArray üzerinden üret
            if (empty($checksumBody)) {
                $checksumBody = $bodyArray;
            }

            $json = json_encode($bodyArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            unset($options['json']);
            $options['body'] = $json;
        }

        $headers = $options['headers'] ?? [];
        $headers['content-type'] = 'application/json';
        $headers['api_username'] = $this->apiUser;

        // checksum artık checksumBody'nin json_encode'u ile hesaplanıyor.
        // ama body olarak da bizim json'ı gönderdiğimiz için aynı olacak.
        $headers['checksum'] = $this->checksum($checksumBody);

        $options['headers'] = $headers;
        $options['verify']  = $options['verify'] ?? $this->verifySsl;

        try {
            $response   = $this->http->request($method, $uri, $options);
            $statusCode = $response->getStatusCode();
            $rawBody    = (string) $response->getBody();
            $decoded    = json_decode($rawBody, true);

            return [
                'ok'       => $statusCode >= 200 && $statusCode < 300,
                'status'   => $statusCode,
                'response' => $decoded ?? $rawBody,
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [
                'ok' => false,
                'status' => $e->getResponse()?->getStatusCode(),
                'error' => $e->getMessage(),
                'body' => (string) $e->getResponse()?->getBody(),
            ];
        }
    }
    private function registerEndpoints(): void
    {
        $classes = [
            \App\Services\PronetServices\Endpoints\Ping::class,
            \App\Services\PronetServices\Endpoints\TransactionsList::class,
            \App\Services\PronetServices\Endpoints\MemberBalance::class,
            \App\Services\PronetServices\Endpoints\ClaimedbonusesList::class,
            \App\Services\PronetServices\Endpoints\AccountsList::class,
            \App\Services\PronetServices\Endpoints\GetBonusesAndFreeBets::class,
            \App\Services\PronetServices\Endpoints\AssignBonusesAndFreebets::class,

        ];

        foreach ($classes as $class) {
            $this->endpoints[$class::methodName()] = new $class($this);
        }
    }

    public function __call(string $name, array $arguments)
    {
        if (!isset($this->endpoints[$name])) {
            throw new BadMethodCallException("Pronet endpoint not found: {$name}");
        }

        $payload = $arguments[0] ?? [];
        return ($this->endpoints[$name])($payload);
    }
}

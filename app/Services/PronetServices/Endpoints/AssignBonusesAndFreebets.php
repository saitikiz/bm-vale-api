<?php

namespace App\Services\PronetServices\Endpoints;

use App\Services\PronetServices\PronetClient;
use Illuminate\Support\Facades\Log;

class AssignBonusesAndFreebets
{
    public function __construct(private PronetClient $client) {}

    public static function methodName(): string
    {
        return 'assignBonusesAndFreebets';
    }

    public function __invoke(array $data = []): array
    {
        Log::info("assignBonusesAndFreebets Service Start ==============================");
        Log::info("Data:\n",$data);
        // Zorunlular (doc’a göre product, type, id zorunlu; customerCode/customerId zorunlu):contentReference[oaicite:5]{index=5}
        if (empty($data['customerCode']) && empty($data['customerId'])) {
            return ['ok' => false, 'error' => 'customerCode veya customerId zorunlu.'];
        }
        if (empty($data['product']) || empty($data['type']) || empty($data['id'])) {
            return ['ok' => false, 'error' => 'product, type, id zorunlu.'];
        }

        // ✅ DİKKAT: sırayı sabitle
        // Checksum örneği sırası: customerCode,id,type,amount,product,api_username,note:contentReference[oaicite:6]{index=6}
        $body = [];

        if (!empty($data['customerCode'])) {
            $body['customerCode'] = (string) $data['customerCode'];
        } else {
            // customerId kullanılacaksa (doc: customerCode veya customerId):contentReference[oaicite:7]{index=7}
            // Bu durumda sıralama konusunda doküman net değil; ama pratikte customerCode ile gitmek daha garanti.
            $body['customerId'] = (int) $data['customerId'];
        }

        $body['id']   = (int) $data['id'];
        $body['type'] = (string) $data['type'];

        // amount opsiyonel:contentReference[oaicite:8]{index=8}
        if (isset($data['amount']) && $data['amount'] !== '') {
            $body['amount'] = (int) $data['amount'];
        }

        $body['product'] = (string) $data['product'];

        // body içindeki api_username (doc request body’de var ve required):contentReference[oaicite:9]{index=9}
        // Bunu request’ten alma → config’ten bas ki header ile aynı olsun.
        $body['appUserName'] = !empty($data['api_username'])
            ? (string) $data['api_username']
            : (string) config('pronet.app_user_name', config('pronet.api_user'));

        if (!empty($data['note'])) {
            $body['note'] = (string) $data['note'];
        }
        Log::info("Request: \n", $body);

        $response = $this->client->request(
            'POST',
            '/external-api/customer/assignBonusesAndFreebets',
            ['json' => $body],
            $body
        );
        Log::info("Response: \n", $response);
        return $response;
    }
}

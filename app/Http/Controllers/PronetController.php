<?php

namespace App\Http\Controllers;
use App\Services\PronetServices\PronetClient;
use Illuminate\Http\Request;

class PronetController
{
    protected PronetClient $pronet;

    public function __construct(PronetClient $pronet)
    {
        $this->pronet = $pronet;
    }

    public function ping()
    {
        $result = $this->pronet->ping();
        return $result;
        if (! $result['ok']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }


    public function transactionsList(Request $request)
    {
        // İstersen sadece belirli alanları alabilirsin:
        $data = $request->only([
            'startDate',
            'endDate',
            'status',
            'masterTypeId',
            'language',
            'username',
            'customerCode',
            'customerId',
        ]);



        $result = $this->pronet->transactionsList($data);

        if (!($result['ok'] ?? false)) {
            // Eğer status döndüyse ona göre, dönmediyse 500
            $code = $result['status'] ?? 500;
            return response()->json($result, $code);
        }

        return response()->json($result, $result['status'] ?? 200);
    }

    public function memberBalance(Request $request)
    {
        $data = $request->only([
            'username',
            'customerCode',
            'customerId',
        ]);

        $result = $this->pronet->memberBalance($data);

        // Service tarafında 'ok' alanına göre http status
        $status = !empty($result['ok']) && $result['ok'] === true ? 200 : 400;

        return response()->json($result, $status);
    }

    public function claimedbonusesList(Request $request)
    {
        $data = $request->only([
            'username',
            'customerCode',
            'customerId',
        ]);

        $result = $this->pronet->claimedbonusesList($data);

        // Service 'ok' alanına göre http status belirleyelim
        $status = !empty($result['ok']) && $result['ok'] === true ? 200 : 400;

        return response()->json($result, $status);
    }

    public function accountsList(Request $request)
    {
        $data = $request->only([
            'username',
            'customerCode',
            'customerId',
        ]);

        $result = $this->pronet->accountsList($data);

        // Service zaten ok / error dönüyor, status kodunu buna göre set edebiliriz
        $status = $result['ok'] ?? false ? 200 : 400;

        return response()->json($result, $status);
    }

    public function getBonusesAndFreeBets(Request $request){
        $data = $request->only([
            'username',
            'customerCode',
            'customerId',
        ]);

        $result = $this->pronet->getBonusesAndFreeBets($data);

        $status = !empty($result['ok']) && $result['ok'] === true ? 200 : 400;

        return response()->json($result, $status);
    }

    public function assignBonusesAndFreebets(Request $request){
        $data = $request->only([
            'username',
            'customerCode',
            'customerId',
            'api_username',
            'product',
            'type',
            'id',
            'amount',
            'note'
        ]);

        $result = $this->pronet->assignBonusesAndFreebets($data);

        $status = !empty($result['ok']) && $result['ok'] === true ? 200 : 400;

        return response()->json($result, $status);
    }




    public function sportbetmastersList(Request $request)
    {
        $data = $request->only([
            'startDate',
            'endDate',
            'username',
            'customerCode',
            'customerId',
        ]);

        $result = $this->pronet->sportbetmastersList($data);

        $status = !empty($result['ok']) && $result['ok'] === true ? 200 : 400;

        return response()->json($result, $status);
    }

    public function memberSummary(Request $request)
    {
        $data = $request->only([
            'username',
            'customerCode',
            'customerId',
        ]);

        $result = $this->pronet->memberSummary($data);

        $status = !empty($result['ok']) && $result['ok'] === true ? 200 : 400;

        return response()->json($result, $status);
    }


}

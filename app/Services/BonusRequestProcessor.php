<?php

namespace App\Services;

use App\Models\Bonus;
use App\Models\BonusRequest;
use App\Support\Result;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BonusRequestProcessor
{
    public function __construct(
        protected PronetClient $pronet,
        protected BonusCalculationService $calculation,
    ) {}

    public function process(BonusRequest $req): Result
    {
        $bonus = Bonus::find($req->bonus_id);

        if (!$bonus || !$bonus->active) {
            return Result::fail('Bonus inactive or not found');
        }

        $function_name = $bonus->function_name ?? 'default';

        if (!method_exists($this, $function_name)) {
            $reason = "Tanımsız bonus fonksiyonu: {$function_name}";
            Log::warning("BonusRequestProcessor: {$reason}", ['uuid' => $req->uuid]);
            return Result::fail($reason);
        }

        Log::info("BonusRequestProcessor: dispatching [{$function_name}]", ['uuid' => $req->uuid]);

        return $this->{$function_name}($req, $bonus);
    }

    // -------------------------------------------------------------------------
    // Yardımcı
    // -------------------------------------------------------------------------

    private function apiError(BonusRequest $req, string $reason, array $apiResponse): Result
    {
        $detail = [
            'status' => $apiResponse['status'] ?? null,
            'body'   => $apiResponse['response']['body'] ?? $apiResponse['response'] ?? $apiResponse['error'] ?? null,
        ];

        Log::error("BonusRequestProcessor: {$reason}", [
            'uuid'   => $req->uuid,
            'detail' => $detail,
        ]);

        return Result::fail($reason, $detail);
    }

    // -------------------------------------------------------------------------
    // Bonus fonksiyonları
    // -------------------------------------------------------------------------

    private function f3336(BonusRequest $req, Bonus $bonus): Result
    {
        $endDate      = Carbon::now('Europe/Istanbul');
        $startDate    = $endDate->copy()->subHours(24);
        $endDateUtc   = $endDate->copy()->utc();
        $startDateUtc = $startDate->copy()->utc();

        $queryData = [
            'startDate'    => $startDateUtc->format('Y-m-d\TH:i:s\Z'),
            'endDate'      => $endDateUtc->format('Y-m-d\TH:i:s\Z'),
            'status'       => ['C', 'R'],
            'language'     => 'en',
            'username'     => $req->customer_username,
            'customerCode' => $req->customerid,
        ];

        $siteSummary = ['bonus_request_id' => $req->uuid];

        $memberSummary = $this->pronet->memberSummary($queryData);

        if (!($memberSummary['ok'] ?? false)) {
            return $this->apiError($req, 'memberSummary API hatası', $memberSummary);
        }

        $siteSummary['financialInfo']       = $memberSummary['response']['body']['financialInfo'];
        $siteSummary['generalCustomerInfo'] = $memberSummary['response']['body']['generalCustomerInfo'];
        $siteSummary['accountStatus']       = $memberSummary['response']['body']['accountStatus'];

        $transactionsList = $this->pronet->transactionsList($queryData);

        if (!($transactionsList['ok'] ?? false)) {
            return $this->apiError($req, 'transactionsList API hatası', $transactionsList);
        }

        $siteSummary['transactionsList'] = $transactionsList['response']['body'];

        $req->update(['site_summary' => json_encode($siteSummary)]);

        $bonusHistoryResponse = $this->pronet->getBonusHistoryByName($req->customer_username);

        if (!($bonusHistoryResponse['ok'] ?? false)) {
            return $this->apiError($req, 'getBonusHistoryByName API hatası', $bonusHistoryResponse);
        }

        $bonusHistory = $bonusHistoryResponse['response'];
        $req->update(['bonus_history' => json_encode($bonusHistory)]);

        $summary = $this->calculation->getNetStatusSinceLastBonus(
            $bonusHistory,
            $siteSummary['transactionsList']
        );

        if (!$summary->ok) {
            return Result::fail($summary->reason ?? 'Net durum hesaplanamadı');
        }

        $req->update(['bonus_summary' => json_encode($summary->data)]);

        // TODO: $summary verisine göre bonus assign et
        Log::info('f3336 summary', $summary->data);

        return Result::ok('f3336 işlendi', $summary->data);
    }
}

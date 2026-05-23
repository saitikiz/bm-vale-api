<?php

namespace App\Services;

use App\Models\Bonus;
use App\Models\BonusRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BonusRequestProcessor
{
    public function __construct(
        protected PronetClient $pronet,
        protected BonusCalculationService $calculation,
    ) {}

    public function process(BonusRequest $req): array
    {
        $bonus = Bonus::find($req->bonus_id);

        if (!$bonus || !$bonus->active) {
            return ['ok' => false, 'reason' => 'Bonus inactive or not found'];
        }

        $function_name = $bonus->function_name ?? 'default';

        if (!method_exists($this, $function_name)) {
            Log::warning("BonusRequestProcessor: unknown function [{$function_name}]", ['uuid' => $req->uuid]);
            return ['ok' => false, 'reason' => "Unknown bonus function: {$function_name}"];
        }

        Log::info("BonusRequestProcessor: dispatching [{$function_name}]", ['uuid' => $req->uuid]);

        return $this->{$function_name}($req, $bonus);
    }

    // -------------------------------------------------------------------------
    // Bonus fonksiyonları
    // -------------------------------------------------------------------------

    private function f3336(BonusRequest $req, Bonus $bonus): array
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
            return ['ok' => false, 'reason' => 'memberSummary API hatası', 'detail' => $memberSummary];
        }

        $siteSummary['financialInfo']       = $memberSummary['response']['body']['financialInfo'];
        $siteSummary['generalCustomerInfo'] = $memberSummary['response']['body']['generalCustomerInfo'];
        $siteSummary['accountStatus']       = $memberSummary['response']['body']['accountStatus'];

        $transactionsList = $this->pronet->transactionsList($queryData);

        if (!($transactionsList['ok'] ?? false)) {
            return ['ok' => false, 'reason' => 'transactionsList API hatası', 'detail' => $transactionsList];
        }

        $siteSummary['transactionsList'] = $transactionsList['response']['body'];

        $req->update(['site_summary' => json_encode($siteSummary)]);

        $bonusHistoryResponse = $this->pronet->getBonusHistoryByName($req->customer_username);

        if (!($bonusHistoryResponse['ok'] ?? false)) {
            return ['ok' => false, 'reason' => 'getBonusHistoryByName API hatası', 'detail' => $bonusHistoryResponse];
        }

        $bonusHistory = $bonusHistoryResponse['response'];
        $req->update(['bonus_history' => json_encode($bonusHistory)]);

        $summary = $this->calculation->getNetStatusSinceLastBonus(
            $bonusHistory,
            $siteSummary['transactionsList']
        );

        if (!$summary['success']) {
            return ['ok' => false, 'reason' => $summary['message'] ?? 'Net durum hesaplanamadı'];
        }

        // TODO: $summary verisine göre bonus assign et
        Log::info('f3336 summary', $summary);

        return ['ok' => true, 'reason' => 'f3336 işlendi', 'summary' => $summary];
    }
}

<?php

namespace App\Http\Controllers;
use App\Jobs\ProcessBonusRequest;
use App\Models\Bonus;
use App\Models\BonusRequest;
use App\Services\PronetClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BonusController
{
    protected PronetClient $pronet;

    public function __construct(PronetClient $pronet)
    {
        $this->pronet = $pronet;
    }

    public function index(Request $request)
    {

        $userBonusRequests = BonusRequest::where('user_id', $request->user_id)->where('user_name', 'like', '%' . $request->user_name . '%')->latest()->get();
        return view('home', compact('userBonusRequests'));
    }
    public function bonusRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'username' => [
                'required_without:user_id',
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9._-]+$/'
            ],

            'user_id' => [
                'required_without:username',
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_-]+$/'
            ],

            'bonus' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/'
            ],

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz veya eksik parametre',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $bonus = Bonus::where('uuid', $data['bonus'])->where('active', true)->first();

        if (!$bonus) {
            return response()->json([
                'success' => false,
                'message' => 'Bonus bulunamadı',
            ], 404);
        }
        // TODO eğer günde 1 olan bonus var ise aynı bonustan gelirse otomatik reddetme geliştirmesi yap.
        $exists = BonusRequest::where(function ($query) use ($data) {

            if (!empty($data['user_id'])) {
                $query->orWhere('customerid', $data['user_id']);
            }

            if (!empty($data['username'])) {
                $query->orWhere('customer_username', $data['username']);
            }

        })
            ->whereIn('status', ['new', 'checking'])
            ->exists();

        /*if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Aktif bonus talebiniz bulunmakta, lütfen sonuçlanmasını bekleyin.',
            ], 409);
        }*/

        $bonusRequest = BonusRequest::create([
            'uuid' => Str::uuid(),
            'worker_id' => null,
            'customerid' => $data['user_id'],
            'customer_username' => $data['username'],
            'bonus_id' => $bonus->id,
            'source' => 'other',
            'ip' => $request->ip(),
            'status' => 'new',
            'status_reason' => null,
            'note' => null,
            'locked_at' => null,
            'retry_count' => 0,
            'last_error' => null,
            'site_id' => null,
        ]);

        $function_name = $bonus->function_name ?? 'default';
        $reuslt = $this->{$function_name}($bonusRequest, $bonus);
        return $reuslt;

        //TODO en son kuyruğa ekleyeceğiz
        //ProcessBonusRequest::dispatch($bonusRequest->uuid)->onQueue('bonusRequest');


        return response()->json([
            'success' => true,
            'data' => $data
        ]);


    }

    /**
     * Bonus listesi içinden en son alınan bonusun yaratılma tarihini bulur.
     *
     * @param array $bonuses Bonus listesi (genellikle $response['data'])
     * @return \DateTimeImmutable|null En son bonus tarihi, liste boşsa null
     */
    function getLastBonusDate(array $bonuses): ?\DateTimeImmutable
    {
        $latest = null;

        foreach ($bonuses as $bonus) {
            if (empty($bonus['yaratilma_tarihi'])) {
                continue;
            }

            // Türkçe format: "15.07.2025 02:10"
            $date = \DateTimeImmutable::createFromFormat(
                'd.m.Y H:i',
                $bonus['yaratilma_tarihi']
            );

            if ($date === false) {
                continue; // hatalı formatlı kaydı atla
            }

            if ($latest === null || $date > $latest) {
                $latest = $date;
            }
        }

        return $latest;
    }

    /**
     * Verilen tarihten sonraki transactionları işleyerek net durumu hesaplar.
     * Net = Toplam Deposit - Toplam Withdrawal (sadece completed/"C" olanlar).
     *
     * @param array              $transactions Transaction listesi
     * @param \DateTimeImmutable $sinceDate    Bu tarihten sonraki işlemler alınır
     * @return array {
     *     net: float,
     *     total_deposit: float,
     *     total_withdrawal: float,
     *     deposit_count: int,
     *     withdrawal_count: int,
     *     skipped_count: int,
     *     transactions: array
     * }
     */
    function calculateNetSinceDate(array $transactions, \DateTimeImmutable $sinceDate): array
    {
        $totalDeposit    = 0.0;
        $totalWithdrawal = 0.0;
        $depositCount    = 0;
        $withdrawalCount = 0;
        $skippedCount    = 0;
        $filtered        = [];

        $sinceTimestampMs = $sinceDate->getTimestamp() * 1000;

        foreach ($transactions as $tx) {
            // Tarih kontrolü
            if (!isset($tx['transactionDate']) || $tx['transactionDate'] <= $sinceTimestampMs) {
                continue;
            }

            // Sadece tamamlanmış işlemleri say (R = reddedilmiş, P = pending vb. dahil değil)
            if (($tx['status'] ?? null) !== 'C') {
                $skippedCount++;
                continue;
            }

            $amount     = (float) ($tx['transactionAmount'] ?? $tx['amount'] ?? 0);
            $masterCode = $tx['masterCode'] ?? null;

            if ($masterCode === 'D') {
                $totalDeposit += $amount;
                $depositCount++;
            } elseif ($masterCode === 'W') {
                $totalWithdrawal += $amount;
                $withdrawalCount++;
            } else {
                $skippedCount++;
                continue;
            }

            $filtered[] = $tx;
        }

        return [
            'net'              => $totalDeposit - $totalWithdrawal,
            'total_deposit'    => $totalDeposit,
            'total_withdrawal' => $totalWithdrawal,
            'deposit_count'    => $depositCount,
            'withdrawal_count' => $withdrawalCount,
            'skipped_count'    => $skippedCount,
            'transactions'     => $filtered,
        ];
    }

    /**
     * Bonus geçmişi ve transaction listesini alır, son bonustan sonraki net durumu hesaplar.
     *
     * @param array $bonusHistory Bonus geçmişi response'u (içinde 'data' anahtarı beklenir)
     * @param array $transactions Transaction listesi
     * @return array {
     *     success: bool,
     *     message: string|null,
     *     last_bonus_date: string|null,        // 'd.m.Y H:i' formatında
     *     total_deposit: float,
     *     total_withdrawal: float,
     *     net: float,
     *     deposit_count: int,
     *     withdrawal_count: int,
     *     skipped_count: int
     * }
     */
    public function getNetStatusSinceLastBonus(array $bonusHistory, array $transactions): array
    {
        $lastBonusDate = $this->getLastBonusDate($bonusHistory['data'] ?? []);

        if ($lastBonusDate === null) {
            return [
                'success'          => false,
                'message'          => 'Bonus kaydı bulunamadı.',
                'last_bonus_date'  => null,
                'total_deposit'    => 0.0,
                'total_withdrawal' => 0.0,
                'net'              => 0.0,
                'deposit_count'    => 0,
                'withdrawal_count' => 0,
                'skipped_count'    => 0,
            ];
        }

        $result = $this->calculateNetSinceDate($transactions, $lastBonusDate);

        return [
            'success'          => true,
            'message'          => null,
            'last_bonus_date'  => $lastBonusDate->format('d.m.Y H:i'),
            'total_deposit'    => $result['total_deposit'],
            'total_withdrawal' => $result['total_withdrawal'],
            'net'              => $result['net'],
            'deposit_count'    => $result['deposit_count'],
            'withdrawal_count' => $result['withdrawal_count'],
            'skipped_count'    => $result['skipped_count'],
        ];
    }

    private function f3336($req, $bonus)
    {

        $endDate = Carbon::now('Europe/Istanbul');
        $startDate = $endDate->copy()->subHours(24);
        // API UTC beklediği için UTC'ye çevir
        $endDateUtc   = $endDate->copy()->utc();
        $startDateUtc = $startDate->copy()->utc();

        $data = [
            'startDate' => $startDateUtc->format('Y-m-d\TH:i:s\Z'),
            'endDate'   => $endDateUtc->format('Y-m-d\TH:i:s\Z'),
            'status' => ['C', 'R'],
            'language' => 'en',
            'username' => $req->customer_username,
            'customerCode' => $req->customerid,
        ];

        $siteSummary = [
            'bonus_request_id' => $req->uuid,
        ];

        $memberSummary = $this->pronet->memberSummary($data);

        if (!($memberSummary['ok'] ?? false)) {
            $code = $memberSummary['status'] ?? 500;
            return response()->json($memberSummary, $code);
        }

        $siteSummary['financialInfo'] = $memberSummary['response']['body']['financialInfo'];
        $siteSummary['generalCustomerInfo'] = $memberSummary['response']['body']['generalCustomerInfo'];
        $siteSummary['accountStatus'] = $memberSummary['response']['body']['accountStatus'];

        $transactionsList = $this->pronet->transactionsList($data);

        if (!($transactionsList['ok'] ?? false)) {
            $code = $transactionsList['status'] ?? 500;
            return response()->json($transactionsList, $code);
        }

        $siteSummary['transactionsList'] = $transactionsList['response']['body'];

        $bonusRequest = BonusRequest::where('uuid', $req->uuid)->first();
        $bonusRequest->update([
            'site_summary' => json_encode($siteSummary),
        ]);
        $bonusHistory = $this->pronet->getBonusHistoryByName($req->customer_username);

        if (!($bonusHistory['ok'] ?? false)) {
            $code = $bonusHistory['status'] ?? 500;
            return response()->json($bonusHistory, $code);
        }
        $bonusHistory = $bonusHistory['response'];

        $bonusRequest->update([
            'bonus_history' => json_encode($bonusHistory),
        ]);


        /*
         * Hesaplama alanaı
         */
        $summary = $this->getNetStatusSinceLastBonus($bonusHistory, $siteSummary['transactionsList']);

        if (!$summary['success']) {
            // bonus yoksa erken çıkış, log vb.
        }



        return "";
        dd($siteSummary,$transactionsList);
        return $memberSummary;
        $result = $this->pronet->transactionsList($data);
        Log::info($result);
        $result2 = $this->pronet->claimedbonusesList($data);
        Log::info($result2);
        $result3 = $this->pronet->memberBalance($data);
        Log::info($result3);

        $result4 = $this->pronet->memberSummary($data);
        return $result4['response'];
        $result4 = json_encode($result4,true);
        Log::info($result3);
        return $result4;
        dd($result,$result2,$result3,$result4);

        return ;

        //todo return düzelt
        if (!($result['ok'] ?? false)) {
            // Eğer status döndüyse ona göre, dönmediyse 500
            $code = $result['status'] ?? 500;
            return response()->json($result, $code);
        }

        //HESAPLAMA
        $txs = Arr::get($result, 'response.body', []);
        if (!is_array($txs)) $txs = [];

        // sadece status=C olanlar
        $completed = array_values(array_filter($txs, fn($t) => ($t['status'] ?? null) === 'C'));

        $depositTotal = 0.0;
        $withdrawTotal = 0.0;

        foreach ($completed as $t) {
            $amount = (float) ($t['amount'] ?? 0);

            if (($t['masterCode'] ?? null) === 'D') {
                $depositTotal += $amount;
            } elseif (($t['masterCode'] ?? null) === 'W') {
                $withdrawTotal += $amount;
            }
        }

        $net = $depositTotal - $withdrawTotal;

        $summary = [
            'deposit_total' => $depositTotal,
            'withdraw_total' => $withdrawTotal,
            'net' => $net,
            'count_completed' => count($completed),
        ];
        Log::info('summary', $summary);
        if ($net>0){
            if ($net>=5 && $net<1000) {
                $bonus_amount = $net*0.2;
            }else if ($net>=1000 && $net<20000) {
                $bonus_amount = $net*0.25;
            }else if ($net>=20000) {
                $bonus_amount = $net*0.3;
            }
            return ['ok' => true, 'reason' => $bonus_amount.' TL Kayıp bonusunuz eklendi'];
        }else{
            return ['ok' => false, 'reason' => 'Son 24 saat içerisinde kayıp bulunmadı.'];
        }
    }

    public function ping()
    {
        $result = $this->pronet->ping();

        if (! $result['ok']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }


    // %100 Hoş Geldin Bonusu – İlk Yatırımınıza Özel
    public function bonus1()
    {
        /*
         1-Yatırım geçmişine ve bonus geçmişine bak
         2-Onaylı depositi 1 tane ve hiç bonus almamış olmalı

        $data = {
    "username": "Bilo",
    "startDate": "2023-10-01T00:00:00Z",
    "endDate": "2025-10-24T23:59:59Z",
    "status": ["C", "R"],
    "language": "en"
  }
        $result = $this->pronet->transactionsList($data);
        response.body.length(masterCode=="D" && status=="C")(deposit ve complate olan) == 1 (yani ilk onaylı yatırımı)
        amount değerini al tut.

        Bonus assing fonskyionunu çalıştır , amount 100 tl den az ise amount kadar  , 100 tl den fazla ise 100 tl değerinde bonus ekle.

         */
    }

    // %50 Slot Bonusu –- Maks. 500₺
    public function bonus2()
    {
       /*
        1- Bonus geçmişine bak
       2- bu bonustan daha önce eklenmişmi bak
       3- daha sonra yatırımlarına bak
       4- aynı gün içinde yatırımı var ise bu bonusu assing et , yatırımlarının %50 si kadar bonus ekle max 500 tl
        */
    }

    // %20 Slot Yatırım Bonusu  (Maks. 333₺)
    public function bonus3()
    {
        /*
         1- Yatırım geçmişine bak
         2- gün içinde tüm yatırımlarına 1 defa alınabilir
        3- gün içindeki yatırımlarını al
        4- db den kontrol et bu bonustan o yatırıma daha önce almışmı bak
        5- yatırımlarının %20 si kadar bonus ekle max 333 tl (bonusun idsi ile assing et)
         */
    }

}

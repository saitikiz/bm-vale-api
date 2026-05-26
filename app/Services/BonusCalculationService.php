<?php

namespace App\Services;

use App\Support\Result;

class BonusCalculationService
{
    /**
     * Bonus listesi içinden en son alınan bonusun yaratılma tarihini bulur.
     *
     * @param array $bonuses Bonus listesi (genellikle $response['data'])
     * @return \DateTimeImmutable|null En son bonus tarihi, liste boşsa null
     */
    public function getLastBonusDate(array $bonuses): ?\DateTimeImmutable
    {
        $latest = null;

        foreach ($bonuses as $bonus) {
            if (empty($bonus['yaratilma_tarihi'])) {
                continue;
            }

            $date = \DateTimeImmutable::createFromFormat('d.m.Y H:i', $bonus['yaratilma_tarihi']);

            if ($date === false) {
                continue;
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
     * @return array{net: float, total_deposit: float, total_withdrawal: float, deposit_count: int, withdrawal_count: int, skipped_count: int, transactions: array}
     */
    public function calculateNetSinceDate(array $transactions, \DateTimeImmutable $sinceDate): array
    {
        $totalDeposit    = 0.0;
        $totalWithdrawal = 0.0;
        $depositCount    = 0;
        $withdrawalCount = 0;
        $skippedCount    = 0;
        $filtered        = [];

        $sinceTimestampMs = $sinceDate->getTimestamp() * 1000;

        foreach ($transactions as $tx) {
            if (!isset($tx['transactionDate']) || $tx['transactionDate'] <= $sinceTimestampMs) {
                continue;
            }

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
     */
    public function getNetStatusSinceLastBonus(array $bonusHistory, array $transactions): Result
    {
        $lastBonusDate = $this->getLastBonusDate($bonusHistory['data'] ?? []);

        if ($lastBonusDate === null) {
            return Result::fail('Bonus kaydı bulunamadı.');
        }

        $result = $this->calculateNetSinceDate($transactions, $lastBonusDate);

        return Result::ok(null, [
            'last_bonus_date'  => $lastBonusDate->format('d.m.Y H:i'),
            'total_deposit'    => $result['total_deposit'],
            'total_withdrawal' => $result['total_withdrawal'],
            'net'              => $result['net'],
            'deposit_count'    => $result['deposit_count'],
            'withdrawal_count' => $result['withdrawal_count'],
            'skipped_count'    => $result['skipped_count'],
        ]);
    }
}

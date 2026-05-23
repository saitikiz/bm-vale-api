<?php

namespace App\Jobs;

use App\Models\BonusRequest;
use App\Services\BonusRequestProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBonusRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $bonusRequestUuid;

    public $tries = 3;          // kaç deneme
    public $backoff = [10, 60]; // retry gecikmeleri

    public function __construct(string $bonusRequestUuid)
    {
        $this->bonusRequestUuid = $bonusRequestUuid;
    }

    public function handle(BonusRequestProcessor $processor): void
    {
        $req = BonusRequest::where('uuid', $this->bonusRequestUuid)->first();

        if (!$req) return;

        if ($req->locked_at) return;
        $req->update([
            'locked_at' => now(),
            'status' => 'checking',
        ]);

        try {
            $result = $processor->process($req);

            if ($result['ok']) {
                $req->update([
                    'status'        => 'approved_assigned',
                    'status_reason' => $result['reason'] ?? null,
                ]);
            } else {
                $lastError = $result['reason'] ?? 'Rejected';
                if (!empty($result['detail'])) {
                    $lastError .= ' | ' . (is_string($result['detail']) ? $result['detail'] : json_encode($result['detail']));
                }

                $req->update([
                    'status'        => 'rejected',
                    'status_reason' => $result['reason'] ?? 'Rejected',
                    'last_error'    => $lastError,
                ]);
            }
        } catch (\Throwable $e) {
            $req->update([
                'status' => 'rejected',
                'last_error' => $e->getMessage(),
            ]);

            throw $e; // tries/backoff çalışsın istiyorsan bunu bırak
        }


    }

    public function failed(\Throwable $e): void
    {
        // Job tamamen başarısız olursa request kaydını güncelle
        BonusRequest::where('uuid', $this->bonusRequestUuid)->update([
            'status' => 'rejected',
            'last_error' => $e->getMessage(),
        ]);
    }
}

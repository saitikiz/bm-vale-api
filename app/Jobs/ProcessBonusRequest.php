<?php

namespace App\Jobs;

use App\Models\Bonus;
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

        $bonus = Bonus::find($req->bonus_id);

        try {
            $result = $processor->process($req);

            if ($result->ok) {
                $req->update([
                    'status'         => 'approved_assigned',
                    'status_reason'  => $result->reason,
                    'client_message' => $this->renderMessage(
                        $bonus?->success_message ?? config('bonus.default_messages.success'),
                        $result->data
                    ),
                ]);
            } else {
                $req->update([
                    'status'         => 'rejected',
                    'status_reason'  => $result->reason ?? 'Rejected',
                    'last_error'     => $result->lastError(),
                    'client_message' => $this->renderMessage(
                        $bonus?->rejection_message ?? config('bonus.default_messages.rejection'),
                        $result->data
                    ),
                ]);
            }

            $this->sendCallback($req->fresh());
        } catch (\Throwable $e) {
            $req->update([
                'status'         => 'rejected',
                'last_error'     => $e->getMessage(),
                'client_message' => $bonus?->error_message ?? config('bonus.default_messages.error'),
            ]);

            $this->sendCallback($req->fresh());

            throw $e;
        }


    }

    private function sendCallback(BonusRequest $req): void
    {
        if (empty($req->callback_url)) {
            return;
        }

        $payload = json_encode([
            'uuid'            => $req->uuid,
            'status'          => $req->status,
            'status_reason'   => $req->status_reason,
            'bonus_summary'   => $req->bonus_summary ? json_decode($req->bonus_summary, true) : null,
            'callback_secret' => $req->callback_secret,
        ]);

        $ch = curl_init($req->callback_url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function renderMessage(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{{' . $key . '}}', (string) $value, $template);
            }
        }
        return $template;
    }

    public function failed(\Throwable $e): void
    {
        BonusRequest::where('uuid', $this->bonusRequestUuid)->update([
            'status'         => 'rejected',
            'last_error'     => $e->getMessage(),
            'client_message' => config('bonus.default_messages.error'),
        ]);
    }
}

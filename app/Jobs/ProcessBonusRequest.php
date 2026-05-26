<?php

namespace App\Jobs;

use App\Models\BonusRequest;
use App\Services\BonusRequestProcessor;
use App\Services\ClientMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBonusRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $bonusRequestUuid;

    public $tries = 3;
    public $backoff = [10, 60];

    public function __construct(string $bonusRequestUuid)
    {
        $this->bonusRequestUuid = $bonusRequestUuid;
    }

    public function handle(BonusRequestProcessor $processor, ClientMessageService $messages): void
    {
        $req = BonusRequest::where('uuid', $this->bonusRequestUuid)->first();

        if (!$req) return;

        if ($req->locked_at) return;
        $req->update([
            'locked_at' => now(),
            'status'    => 'checking',
        ]);

        try {
            $result = $processor->process($req);

            if ($result->ok) {
                $amount         = $result->data['bonus_amount'] ?? null;
                $messageKey     = $amount ? 'approved_amount' : 'approved';
                $clientMessage  = $messages->resolve($messageKey, $amount ? ['amount' => $amount] : []);

                $req->update([
                    'status'         => 'approved_assigned',
                    'status_reason'  => $result->reason,
                    'client_message' => $clientMessage,
                ]);
            } else {
                $req->update([
                    'status'         => 'rejected',
                    'status_reason'  => $result->reason ?? 'Rejected',
                    'last_error'     => $result->lastError(),
                    'client_message' => $messages->resolve('rejected'),
                ]);
            }

            $this->sendCallback($req->fresh());
        } catch (\Throwable $e) {
            $req->update([
                'status'         => 'rejected',
                'last_error'     => $e->getMessage(),
                'client_message' => $messages->resolve('error'),
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
            'client_message'  => $req->client_message,
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

    public function failed(\Throwable $e): void
    {
        $messages = app(ClientMessageService::class);

        BonusRequest::where('uuid', $this->bonusRequestUuid)->update([
            'status'         => 'rejected',
            'last_error'     => $e->getMessage(),
            'client_message' => $messages->resolve('error'),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBonusRequest;
use App\Models\Bonus;
use App\Models\BonusRequest;
use App\Services\PronetClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BonusController
{
    protected PronetClient $pronet;

    public function __construct(PronetClient $pronet)
    {
        $this->pronet = $pronet;
    }

    public function index(Request $request)
    {
        $userBonusRequests = BonusRequest::where('user_id', $request->user_id)
            ->where('user_name', 'like', '%' . $request->user_name . '%')
            ->latest()
            ->get();

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
            'callback_url'    => ['nullable', 'url', 'max:500'],
            'callback_secret' => ['nullable', 'string', 'max:255'],
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

        // TODO: günde 1 olan bonus var ise aynı bonustan tekrar gelirse otomatik reddetme
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

        $bonusRequest = BonusRequest::create([
            'uuid'              => Str::uuid(),
            'worker_id'         => null,
            'customerid'        => $data['user_id'] ?? null,
            'customer_username' => $data['username'],
            'bonus_id'          => $bonus->id,
            'source'            => 'other',
            'ip'                => $request->ip(),
            'status'            => 'new',
            'status_reason'     => null,
            'note'              => null,
            'locked_at'       => null,
            'retry_count'     => 0,
            'last_error'      => null,
            'site_id'         => null,
            'callback_url'    => $data['callback_url'] ?? null,
            'callback_secret' => $data['callback_secret'] ?? null,
        ]);

        ProcessBonusRequest::dispatch($bonusRequest->uuid)->onQueue('bonusRequest');

        return response()->json([
            'success'      => true,
            'message'      => 'Bonus eklemede',
            'bonusRequest' => $bonusRequest,
        ]);
    }

    public function stream(string $uuid)
    {
        $pending     = ['new', 'checking'];
        $maxSeconds  = 120;
        $pollSeconds = 2;

        $response = new StreamedResponse(function () use ($uuid, $pending, $maxSeconds, $pollSeconds) {
            $start = time();

            while (true) {
                $req = BonusRequest::where('uuid', $uuid)->first();

                if (!$req) {
                    $this->sse(['error' => true, 'message' => 'Talep bulunamadi']);
                    return;
                }

                if (!in_array($req->status, $pending, true)) {
                    $this->sse([
                        'status'        => $req->status,
                        'status_reason' => $req->status_reason,
                        'bonus_summary' => $req->bonus_summary ? json_decode($req->bonus_summary, true) : null,
                    ]);
                    return;
                }

                // Henuz islemde: baglantiyi acik tutmak icin keep-alive yorumu
                echo ": keep-alive\n\n";
                $this->flushOutput();

                if (connection_aborted() || (time() - $start) >= $maxSeconds) {
                    return;
                }

                sleep($pollSeconds);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    private function sse(array $data): void
    {
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        $this->flushOutput();
    }

    private function flushOutput(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    public function ping()
    {
        $result = $this->pronet->ping();

        if (!$result['ok']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }
}

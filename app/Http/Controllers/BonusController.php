<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBonusRequest;
use App\Models\Bonus;
use App\Models\BonusRequest;
use App\Services\PronetClient;
use Illuminate\Http\Request;
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
            'customerid'        => $data['user_id'],
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

    public function ping()
    {
        $result = $this->pronet->ping();

        if (!$result['ok']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }
}

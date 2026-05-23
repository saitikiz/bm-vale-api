<?php

namespace App\Services;
use App\Models\BonusRequest;
use App\Models\Bonus;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class BonusRequestProcessor
{
    public function __construct(PronetClient $pronet)
    {
        $this->pronet = $pronet;
    }
    public function process(BonusRequest $req): array
    {
        $bonus = Bonus::find($req->bonus_id);
        if (!$bonus || !$bonus->active) {
            return ['ok' => false, 'reason' => 'Bonus inactive or not found'];
        }

        // Bonus türünü senin modeline göre seç:
        // örn: $bonus->type, $bonus->code, $bonus->slug
        $function_name = $bonus->function_name ?? 'default';

        Log::info("$function_name request processed");
        Log::info('BonusRequest data', $req->toArray());
        // default
        return ['ok' => false, 'reason' => 'Unknown bonus type'];
    }

}

<?php

namespace App\Services;

use App\Models\BonusStatusMessage;
use Illuminate\Support\Facades\Cache;

class ClientMessageService
{
    private string $fallback = 'Bir hata oluştu, daha sonra tekrar deneyiniz.';

    private array $defaults = [
        'approved'          => 'Bonus talebiniz onaylandı. Bol şans!',
        'approved_amount'   => '{{amount}} TL bonus tanımlandı. Bol şans!',
        'rejected'          => 'Üzgünüz, bonus uygunluğunuz bulunamadı.',
        'error'             => 'Bir hata oluştu, daha sonra tekrar deneyiniz.',
        'duplicate_request' => 'Bu bonusu kısa süre içinde zaten talep ettiniz. Lütfen daha sonra tekrar deneyiniz.',
        'bonus_not_found'   => 'Bonus bulunamadı.',
    ];

    public function resolveById(int $id, array $vars = []): string
    {
        $message = Cache::remember("bsm:id:{$id}", 300, fn() => BonusStatusMessage::find($id));

        if (!$message) {
            return $this->fallback;
        }

        return $this->interpolate($message->template, $vars);
    }

    public function resolve(string $key, array $vars = []): string
    {
        $message = Cache::remember("bsm:key:{$key}", 300, function () use ($key) {
            return BonusStatusMessage::where('key', $key)->where('active', true)->first();
        });

        $template = $message ? $message->template : ($this->defaults[$key] ?? $this->fallback);

        return $this->interpolate($template, $vars);
    }

    public function interpolate(string $template, array $vars): string
    {
        foreach ($vars as $var => $value) {
            $template = str_replace('{{' . $var . '}}', $value, $template);
        }

        return $template;
    }
}

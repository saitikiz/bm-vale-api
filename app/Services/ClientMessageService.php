<?php

namespace App\Services;

use App\Models\BonusStatusMessage;
use Illuminate\Support\Facades\Cache;

class ClientMessageService
{
    private array $defaults = [
        'approved'          => 'Bonus talebiniz onaylandı. Bol şans!',
        'approved_amount'   => '{{amount}} TL bonus tanımlandı. Bol şans!',
        'rejected'          => 'Üzgünüz, bonus uygunluğunuz bulunamadı.',
        'error'             => 'Bir hata oluştu, daha sonra tekrar deneyiniz.',
        'duplicate_request' => 'Bu bonusu kısa süre içinde zaten talep ettiniz. Lütfen daha sonra tekrar deneyiniz.',
        'bonus_not_found'   => 'Bonus bulunamadı.',
    ];

    public function resolve(string $key, array $variables = []): string
    {
        $template = $this->getTemplate($key);

        return $this->interpolate($template, $variables);
    }

    private function getTemplate(string $key): string
    {
        $message = Cache::remember("bsm:{$key}", 300, function () use ($key) {
            return BonusStatusMessage::where('key', $key)->where('active', true)->first();
        });

        if ($message) {
            return $message->template;
        }

        return $this->defaults[$key] ?? 'Bir hata oluştu, daha sonra tekrar deneyiniz.';
    }

    private function interpolate(string $template, array $variables): string
    {
        foreach ($variables as $var => $value) {
            $template = str_replace('{{' . $var . '}}', $value, $template);
        }

        return $template;
    }
}

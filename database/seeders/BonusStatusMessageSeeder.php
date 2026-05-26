<?php

namespace Database\Seeders;

use App\Models\BonusStatusMessage;
use Illuminate\Database\Seeder;

class BonusStatusMessageSeeder extends Seeder
{
    public function run()
    {
        $messages = [
            [
                'key'      => 'approved',
                'template' => 'Bonus talebiniz onaylandı. Bol şans!',
            ],
            [
                'key'      => 'approved_amount',
                'template' => '{{amount}} TL bonus tanımlandı. Bol şans!',
            ],
            [
                'key'      => 'rejected',
                'template' => 'Üzgünüz, bonus uygunluğunuz bulunamadı.',
            ],
            [
                'key'      => 'error',
                'template' => 'Bir hata oluştu, daha sonra tekrar deneyiniz.',
            ],
            [
                'key'      => 'duplicate_request',
                'template' => 'Bu bonusu kısa süre içinde zaten talep ettiniz. Lütfen daha sonra tekrar deneyiniz.',
            ],
            [
                'key'      => 'bonus_not_found',
                'template' => 'Bonus bulunamadı.',
            ],
        ];

        foreach ($messages as $msg) {
            BonusStatusMessage::updateOrCreate(['key' => $msg['key']], [
                'template' => $msg['template'],
                'active'   => true,
            ]);
        }
    }
}

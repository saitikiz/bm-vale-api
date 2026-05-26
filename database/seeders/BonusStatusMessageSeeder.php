<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BonusStatusMessageSeeder extends Seeder
{
    public function run()
    {
        $messages = [
            ['id' => 1, 'key' => 'approved',          'template' => 'Bonus talebiniz onaylandı. Bol şans!'],
            ['id' => 2, 'key' => 'approved_amount',   'template' => '{{amount}} TL bonus tanımlandı. Bol şans!'],
            ['id' => 3, 'key' => 'rejected',           'template' => 'Üzgünüz, bonus uygunluğunuz bulunamadı.'],
            ['id' => 4, 'key' => 'error',              'template' => 'Bir hata oluştu, daha sonra tekrar deneyiniz.'],
            ['id' => 5, 'key' => 'duplicate_request',  'template' => 'Bu bonusu kısa süre içinde zaten talep ettiniz. Lütfen daha sonra tekrar deneyiniz.'],
            ['id' => 6, 'key' => 'bonus_not_found',    'template' => 'Bonus bulunamadı.'],
        ];

        foreach ($messages as $msg) {
            DB::table('bonus_status_messages')->upsert(
                ['id' => $msg['id'], 'key' => $msg['key'], 'template' => $msg['template'], 'active' => true],
                ['id'],
                ['key', 'template']
            );
        }
    }
}

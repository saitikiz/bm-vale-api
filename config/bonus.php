<?php

return [
    'cooldown_seconds' => (int) env('BONUS_COOLDOWN_SECONDS', 120),

    'default_messages' => [
        'success'   => 'Bonusunuz basariyla tanimlandi.',
        'rejection' => 'Uzgunuz, bonus uygunlugunuz bulunamadi.',
        'error'     => 'Bir hata olustu, daha sonra tekrar deneyiniz.',
        'cooldown'  => 'Bu bonusu cok yakin zamanda talep ettiniz. Lutfen daha sonra tekrar deneyiniz.',
    ],
];

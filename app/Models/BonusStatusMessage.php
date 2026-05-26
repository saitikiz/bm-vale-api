<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusStatusMessage extends Model
{
    // Sabit ID'ler — job/controller içinde doğrudan kullanın:
    // $req->update(['message_id' => BonusStatusMessage::APPROVED]);
    const APPROVED         = 1;
    const APPROVED_AMOUNT  = 2;
    const REJECTED         = 3;
    const ERROR            = 4;
    const DUPLICATE        = 5;
    const BONUS_NOT_FOUND  = 6;

    protected $fillable = ['key', 'template', 'active'];

    protected $casts = ['active' => 'boolean'];
}

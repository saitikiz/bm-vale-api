<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusStatusMessage extends Model
{
    protected $fillable = ['key', 'template', 'active'];

    protected $casts = ['active' => 'boolean'];
}

<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use SoftDeletes, Auditable, HasFactory;

    public $table = 'sites';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'uuid',
        'active',
        'name',
        'token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function siteWorkers()
    {
        return $this->hasMany(Worker::class, 'site_id', 'id');
    }

    public function siteBonusRequests()
    {
        return $this->hasMany(BonusRequest::class, 'site_id', 'id');
    }

    public function sitePlayers()
    {
        return $this->hasMany(Player::class, 'site_id', 'id');
    }

    public function siteBonus()
    {
        return $this->hasMany(Bonus::class, 'site_id', 'id');
    }
}

<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BonusRequest extends Model
{
    use SoftDeletes, Auditable, HasFactory;

    public $table = 'bonus_requests';

    protected $dates = [
        'locked_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public const SOURCE_SELECT = [
        'web'    => 'Web',
        'mobile' => 'Mobile',
        'other'  => 'Other',
    ];

    public const STATUS_SELECT = [
        'new'               => 'New',
        'checking'          => 'Checking',
        'approved'          => 'Approved',
        'approved_assigned' => 'Approved and Assigned',
        'cancelled'         => 'Cancelled',
        'rejected'          => 'Rejected',
    ];

    protected $fillable = [
        'uuid',
        'worker_id',
        'customer_username',
        'customer_code',
        'customerid',
        'source',
        'ip',
        'status',
        'status_reason',
        'note',
        'locked_at',
        'retry_count',
        'last_error',
        'site_summary',
        'bonus_history',
        'bonus_summary',
        'reason',
        'callback_url',
        'callback_secret',
        'site_id',
        'bonus_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function getLockedAtAttribute($value)
    {
        return $value ? Carbon::createFromFormat('Y-m-d H:i:s', $value)->format(config('panel.date_format') . ' ' . config('panel.time_format')) : null;
    }

    public function setLockedAtAttribute($value)
    {
        $this->attributes['locked_at'] = $value ? Carbon::createFromFormat(config('panel.date_format') . ' ' . config('panel.time_format'), $value)->format('Y-m-d H:i:s') : null;
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function bonus()
    {
        return $this->belongsTo(Bonus::class, 'bonus_id');
    }
}

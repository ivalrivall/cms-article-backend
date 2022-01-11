<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Promo extends Model implements Auditable
{
    use HasFactory, Sortable, SoftDeletes, Auditing;
    public $timestamps = true;
    protected $guarded = [];
    protected $hidden = [
        'created_at',
        'updated_at',
        'created_by',
        'user_id'
    ];
    public $sortable = [
        'id',
        'discount',
        'discount_max',
        'start_at',
        'end_at',
        'min_value',
        'updated_at',
        'user',
        'scope'
    ];
    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    //     'updated_at' => 'datetime:Y-m-d H:i:s'
    // ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pickup()
    {
        return $this->hasOne(Pickup::class);
    }

    public function getCreatedAtAttribute()
    {
        $data = Carbon::parse($this->attributes['created_at'])->format('Y-m-d H:i:s');
        return $data;
    }

    public function getUpdatedAtAttribute()
    {
        $data = Carbon::parse($this->attributes['updated_at'])->format('Y-m-d H:i:s');
        return $data;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Vehicle extends Model implements Auditable
{
    use HasFactory, SoftDeletes, Sortable, Auditing;

    protected $hidden = [
        'created_at',
        'deleted_at',
        'updated_at',
        'driver_id',
    ];

    public $sortable = [
        'id',
        'type',
        'name',
        'status',
        'max_weight',
        'max_volume',
        'license_plate',
    ];

    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    //     'updated_at' => 'datetime:Y-m-d H:i:s'
    // ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function pickupPlans()
    {
        return $this->hasMany(PickupPlan::class);
    }

    public function shipmentPlans()
    {
        return $this->hasMany(ShipmentPlan::class);
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

    public function transits()
    {
        return $this->hasMany(Transit::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, Sortable, SoftDeletes;

    protected $hidden = ['created_at', 'updated_at', 'user_id', 'deleted_at'];

    public $sortable = [
        'user',
        'type',
        'active',
        'status',
        'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function pickupDriverLogs()
    {
        return $this->hasMany(PickupDriverLog::class);
    }

    public function pickupPlans()
    {
        return $this->hasMany(PickupPlan::class);
    }

    public function shipmentPlans()
    {
        return $this->hasMany(ShipmentPlan::class);
    }

    public function transits()
    {
        return $this->hasMany(Transit::class);
    }
}

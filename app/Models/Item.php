<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Item extends Model implements Auditable
{
    /**
     * price:
     */
    use HasFactory, Auditing;
    public $timestamps = true;
    protected $guarded = [];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    //     'updated_at' => 'datetime:Y-m-d H:i:s'
    // ];

    /**
     * Get the pickup that owns the item.
     */
    public function pickup()
    {
        return $this->belongsTo(Pickup::class);
    }

    // /**
    //  * Get the unit of item
    //  */
    // public function unit()
    // {
    //     return $this->belongsTo(Unit::class);
    // }

    /**
     * Get the service of item
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
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

    public function routePrice()
    {
        return $this->belongsTo(RoutePrice::class);
    }
}

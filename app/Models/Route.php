<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
class Route extends Model implements Auditable
{
    use HasFactory, Sortable, Auditing;
    protected $hidden = ['updated_at', 'deleted_at'];
    protected $casts = [
        'price' => 'float',
        'minimum_weight' => 'float',
        // 'created_at' => 'datetime:Y-m-d H:i:s',
        // 'updated_at' => 'datetime:Y-m-d H:i:s'
    ];
    public $sortable = [
        'id',
        'fleet',
        'origin',
        'destination_district',
        'destination_city',
        'price',
        'minimum_weight',
        'minimum_price',
        'created_at',
        'estimate'
    ];

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
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

    public function routePrices()
    {
        return $this->hasMany(RoutePrice::class);
    }

    public function deletedRutePrices()
    {
        return $this->hasMany(RoutePrice::class)->withTrashed();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;
use App\Models\Pickup;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class PickupPlan extends Model implements Auditable
{
    /**
     * status: canceled, applied
     */
    use HasFactory, SoftDeletes, Sortable, Auditing;

    protected $hidden = [
        'deleted_at',
        'updated_at',
        'deleted_by',
        'vehicle_id',
    ];

    protected $guarded = [];

    public $sortable = [
        'pickups',
        'vehicle',
        'status',
        'user',
        'sender',
        'id',
        'created_by',
        'deleted_by',
        'number',
        'created_at',
    ];

    protected $appends = ['total_pickup_order'];

    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    //     'updated_at' => 'datetime:Y-m-d H:i:s'
    // ];

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

    public function pickups()
    {
        return $this->hasMany(Pickup::class, 'pickup_plan_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getTotalPickupOrderAttribute()
    {
        $pickups = Pickup::where('pickup_plan_id', $this->id)->get();
        $count = count($pickups);
        return $count;
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}

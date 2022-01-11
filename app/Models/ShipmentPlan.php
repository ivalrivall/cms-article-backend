<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;
use Carbon\Carbon;
use App\Models\Pickup;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
class ShipmentPlan extends Model implements Auditable
{
    /**
     *
     * status: applied, canceled, submitted, draft
     * kondisi cancel:
     * - jika sudah ada pod tidak boleh di cancel
     * - jika ada incoming yg masih di outstanding, boleh di cancel
     * - jika ada incoming yg di submitted, tidak boleh di cancel
     * - softdelete incoming juga jika shipment plan di cancel
     */
    use HasFactory, SoftDeletes, Sortable, Auditing;

    protected $hidden = [
        'deleted_at',
        'updated_at',
        'deleted_by'
    ];

    protected $table = 'shipment_plans';

    protected $guarded = [];

    protected $appends = ['total_pickup_order'];

    public $sortable = [
        'pickups',
        'status',
        'id',
        'vehicle',
        'created_by',
        'deleted_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

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
        return $this->hasMany(Pickup::class, 'shipment_plan_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function getTotalPickupOrderAttribute()
    {
        $pickups = Pickup::where('shipment_plan_id', $this->id)->get();
        $count = count($pickups);
        return $count;
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}

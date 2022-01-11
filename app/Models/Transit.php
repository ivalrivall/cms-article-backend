<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
use Haruncpi\LaravelIdGenerator\IdGenerator;
class Transit extends Model implements Auditable
{
    /**
     * status:
     * - pending (ada di outstanding)
     * - draft & applied (ada di submitted)
     * kondisi edit:
     * - jika masih di outstanding, boleh dikeluarkan dari shipment plan
     * - jika sudah submitted, dan ingin dikeluarkan dari shipment plan, maka harus dicancel dulu yg submitted
     *      lalu bisa di cancel di shipment plan
     * status_transit:
     * - failed
     * - success
     */
    public $timestamps = true;

    use HasFactory, SoftDeletes, Sortable, Auditing;

    public $sortable = [
        'created_at',
        'updated_at',
        'pickup',
        'id',
        'status',
        'vehicle',
        'number',
        'driver'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    //     'updated_at' => 'datetime:Y-m-d H:i:s',
    //     'deleted_at' => 'datetime:Y-m-d H:i:s'
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

    public function getDeletedAtAttribute()
    {
        $data = Carbon::parse($this->attributes['deleted_at'])->format('Y-m-d H:i:s');
        return $data;
    }

    public function pickup()
    {
        return $this->belongsTo(Pickup::class, 'pickup_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// VENDOR
use Kyslik\ColumnSortable\Sortable;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
class ProofOfDelivery extends Model implements Auditable
{
    /**
     * status_delivery: re-delivery, failed, success
     * status: draft, applied, submitted, canceled
     */
    use HasFactory, SoftDeletes, Sortable, Auditing;

    public $timestamps = true;

    protected $guarded = [];

    public $sortable = [
        'created_at',
        'updated_at',
        'pickup',
        'shipment_plan',
        'id',
        'created_by',
        'updated_by'
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
        'created_by',
        'deleted_by',
        'updated_by',
        'pickup_id'
    ];

    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    //     'updated_at' => 'datetime:Y-m-d H:i:s'
    // ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function pickup()
    {
        return $this->belongsTo(Pickup::class);
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
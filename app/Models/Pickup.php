<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Kyslik\ColumnSortable\Sortable;
use App\Models\Item;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
class Pickup extends Model implements Auditable
{
    // Status : applied, canceled, request
    use HasFactory, SoftDeletes, Sortable, Auditing;

    public $timestamps = true;

    protected $guarded = [];

    // protected $appends = ['redelivery_count'];

    public $sortable = [
        'created_at',
        'sender',
        'receiver',
        'debtor',
        'name',
        'pickupPlan',
        'pickup_plan_id',
        'shipmentPlan',
        'shipment_plan_id',
        'picktime',
        'proofOfDelivery',
        'proof_of_delivery',
        'id',
        'user',
        'number',
        'redelivery_count',
        'marketing',
        'pickup_plan',
        'cost',
        'status'
    ];

    protected $hidden = [
        'sender_id',
        'receiver_id',
        'debtor_id',
        'updated_at',
        'deleted_at',
        'created_by',
        'deleted_by'
    ];

    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    //     'updated_at' => 'datetime:Y-m-d H:i:s'
    // ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sender()
    {
        return $this->belongsTo(Sender::class);
    }

    public function receiver()
    {
        return $this->belongsTo(Receiver::class);
    }

    public function debtor()
    {
        return $this->belongsTo(Debtor::class);
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class);
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function pickupPlan()
    {
        return $this->belongsTo(PickupPlan::class, 'pickup_plan_id');
    }

    public function proofOfPickup()
    {
        return $this->hasOne(ProofOfPickup::class, 'pickup_id');
    }

    public function proofOfPickups()
    {
        return $this->hasMany(ProofOfPickup::class, 'pickup_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function shipmentPlan()
    {
        return $this->belongsTo(ShipmentPlan::class, 'shipment_plan_id');
    }

    public function trackings()
    {
        return $this->hasMany(Tracking::class);
    }

    public function transit()
    {
        return $this->hasOne(Transit::class, 'pickup_id');
    }

    public function transits()
    {
        return $this->hasMany(Transit::class, 'pickup_id');
    }

    public function pendingTransit()
    {
        return $this->transit()->where('status','pending')->get();
    }

    public function cost()
    {
        return $this->hasOne(Cost::class, 'pickup_id');
    }

    public function proofOfDelivery()
    {
        return $this->hasOne(ProofOfDelivery::class, 'pickup_id');
    }

    public function proofOfDeliveries()
    {
        return $this->hasMany(ProofOfDelivery::class, 'pickup_id');
    }

    // public function getRedeliveryCountAttribute()
    // {
    //     $tracking = Tracking::where('pickup_id', $this->id)
    //         ->where('docs', 'proof-of-delivery')
    //         ->where('status_delivery','re-delivery')
    //         ->count();
    //     return $tracking;
    // }

    public function pickupDriverLogs()
    {
        return $this->hasMany(PickupDriverLog::class);
    }

    public function marketing()
    {
        return $this->belongsTo(User::class, 'marketing_id');
    }

    public function extraCosts()
    {
        return $this->hasManyThrough(ExtraCost::class, Cost::class);
    }
}

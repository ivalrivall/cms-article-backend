<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Carbon\Carbon;

class Tracking extends Model
{
    use HasFactory, Sortable;

    public $timestamps = true;

    protected $guarded = [];

    public $sortable = [
        'pickup_id',
        'pickup',
        'id',
    ];

    protected $hidden = [
        'pickup_id'
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

    public function pickup()
    {
        return $this->belongsTo(Pickup::class);
    }
}

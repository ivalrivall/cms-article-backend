<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Debtor extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = true;

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'user_id'
    ];

    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    //     'updated_at' => 'datetime:Y-m-d H:i:s'
    // ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pickups()
    {
        return $this->hasMany(Pickup::class);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
use Kyslik\ColumnSortable\Sortable;
class Service extends Model implements Auditable
{
    /**
     * cache:
     * services
     * service:id
     */
    use HasFactory, Auditing, Sortable;
    public $timestamps = true;
    protected $guarded = [];
    public $sortable = [
        'created_at',
        'name',
        'id',
        'price'
    ];
    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    //     'updated_at' => 'datetime:Y-m-d H:i:s'
    // ];

    public function items()
    {
        return $this->hasMany(Item::class);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
class Role extends Model implements Auditable
{
    use HasFactory, SoftDeletes, Sortable, Auditing;
    public $timestamps = true;
    public $casts = [
        'ranking' => 'double',
        'privilleges' => 'array'
    ];
    public $hidden = ['created_at','updated_at'];
    public $sortable = [
        'created_at',
        'updated_at',
        'name',
        'id',
        'slug',
        'ranking',
        'privilleges',
        'description'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class);
    }
}

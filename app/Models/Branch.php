<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;
class Branch extends Model
{
    /**
     * cache:
     * branch:branchId
     * branches
     * branch.slug:slugName
     */
    use HasFactory, SoftDeletes, Sortable;

    public $sortable = [
        'created_at',
        'name',
        'id',
        'slug',
        'province',
        'city',
        'district'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function pickups()
    {
        return $this->hasMany(Pickup::class);
    }

    public function transitTo()
    {
        return $this->hasMany(Transit::class, 'to');
    }

    public function transitFrom()
    {
        return $this->hasMany(Transit::class, 'from');
    }

    public function pickupDriverLogFroms()
    {
        return $this->hasMany(PickupDriverLog::class, 'branch_from');
    }

    public function pickupDriverLogTos()
    {
        return $this->hasMany(PickupDriverLog::class, 'branch_to');
    }
}

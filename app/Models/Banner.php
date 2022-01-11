<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Banner extends Model implements Auditable
{
    /**
     * cache
     * banner:bannerId
     * banners
     */
    use HasFactory, Sortable, Auditing;

    public $sortable = [
        'created_at',
        'updated_at',
        'id',
        'order'
    ];
}

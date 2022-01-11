<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Article extends Model implements Auditable
{
    /**
     * cache:
     * article:articleId
     * articles
     */
    use HasFactory, Sortable, Auditing;

    public $sortable = [
        'created_at',
        'updated_at',
        'id',
        'description',
        'title',
        'url'
    ];
}

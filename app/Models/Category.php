<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Category extends Model implements Auditable
{
    public $timestamps = true;
    protected $guarded = [];
    use HasFactory, Sortable, Auditing;
    public $sortable = [
        'created_at',
        'updated_at',
        'id',
        'name',
        'slug'
    ];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}

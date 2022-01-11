<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
use Carbon\Carbon;

class TemplateNotification extends Model implements Auditable
{
    use HasFactory, Sortable, Auditing;

    public $timestamps = true;

    public $sortable = [
        'created_at',
        'updated_at',
        'type',
        'id',
        'body',
        'title'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

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

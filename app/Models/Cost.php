<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Kyslik\ColumnSortable\Sortable;
use App\Models\ExtraCost;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Cost extends Model implements Auditable
{
    /**
     * amount = total dengan potongan diskon dan tambahan biaya service, serta asuransi dan pajak
     * clear_amount = total tanpa diskon, dan service, hanya biaya barang
     * discount = total diskon
     * service = total layanan
     * amount_with_service = total tanpa diskon hanya biaya barang dan service
     * tax_rate = persentasi pajak
     * tax_amount = jumlah pajak (rupiah)
     * insurance_amount = jumlah asuransi (rupiah)
     * amount_with_tax = jumlah tax_amount dengan "amount" (rupiah)
     * amount_with_insurance = jumlah insurance_amount dengan "amount" (rupiah)
     * amount_with_tax_insurance = jumlah tax_amount dan insurance_amount dengan "amount" (rupiah)
     *
     * === FIELD ===
     * method (metode pembayaran) = Tempo, COD, Cash, Transfer
     * status (status pembayaran) = Hutang, Lunas
     *
     */
    public $timestamps = true;

    use HasFactory, Sortable, Auditing;

    protected $guarded = [];

    protected $hidden = [
        'pickup_id'
    ];

    public $sortable = [
        'created_at',
        'updated_at',
        'id',
        'pickup',
        'pickup_id',
        'amount'
    ];

    protected $appends = ['total_extra_cost','margin'];

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
        return $this->belongsTo(Pickup::class, 'pickup_id');
    }

    public function extraCosts()
    {
        return $this->hasMany(ExtraCost::class);
    }

    public function getTotalExtraCostAttribute()
    {
        $extraCosts = ExtraCost::where('cost_id', $this->id)->get()->all();
        if (count($extraCosts) > 0) {
            $total = array_sum(array_column($extraCosts, 'amount'));
        } else {
            $total = 0;
        }
        return intval($total);
    }

    public function getMarginAttribute()
    {
        $margin = intval($this->amount) - intval($this->total_extra_cost);
        return intval($margin);
    }
}

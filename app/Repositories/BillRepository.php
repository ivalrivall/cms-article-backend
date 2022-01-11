<?php

namespace App\Repositories;

use App\Models\Bill;
use App\Models\Pickup;
use App\Models\Service;
use App\Models\Unit;
use App\Models\Item;
use App\Models\Cost;
use App\Models\ExtraCost;
use Carbon\Carbon;

class BillRepository
{
    protected $bill;
    protected $pickup;
    protected $unit;
    protected $service;
    protected $cost;
    protected $extraCost;

    public function __construct(
        Bill $bill,
        Pickup $pickup,
        Unit $unit,
        Service $service,
        Item $item,
        Cost $cost,
        ExtraCost $extraCost
    )
    {
        $this->bill = $bill;
        $this->pickup = $pickup;
        $this->unit = $unit;
        $this->service = $service;
        $this->item = $item;
        $this->cost = $cost;
        $this->extraCost = $extraCost;
    }

    /**
     * Get all bill.
     *
     * @return Bill $bill
     */
    public function getAll()
    {
        return $this->bill->get();
    }

    /**
     * Get bill by id
     *
     * @param $id
     * @return mixed
     */
    public function getById($id)
    {
        return $this->bill->where('id', $id)->get();
    }

    /**
     * Get bill by pickup id
     *
     * @param $pickupId
     * @return mixed
     */
    public function getByPickupId($pickupId)
    {
        return $this->pickup->find($pickupId)->cost()->first();
    }

    /**
     * @param array $items
     * @param array $route
     * @param array $promo
     * @param boolean $savePrice
     * @param array $cost
     *
     * @return object
     */
    public function calculatePriceRepo($items, $route, $promo, $savePrice, $cost)
    {
        $result = $data = [];
        $taxRate = intval($cost['taxRate']) ?? 0;
        $insuranceAmount = intval($cost['insuranceAmount']) ?? 0;
        $totalWeight = array_sum(array_column($items, 'weight'));
        $itemWithZeroWeight = collect($items)->filter(function($q) {
            return $q['weight'] == 0;
        })->values()->toArray();
        $totalVolume = array_sum(array_column($itemWithZeroWeight, 'volume'));
        $totalMinimum = $totalVolume + $totalWeight;
        foreach ($items as $key => $value) {
            $service        = $value['service_id'] ? $this->service->find($value['service_id']) : null;
            $servicePrice   = $service['price'] ?? 0;
            $routePriceId   = $value['route_price_id'];
            $routePrice     = collect($route['deletedRutePrices'])->filter(function($q) use ($routePriceId) {
                return $q->id == $routePriceId;
            })->values()[0];
            if ($value['weight'] !== 0) {
                $priceItem = $this->getPricePerItem($value['weight'], $routePrice, $servicePrice);
            } else {
                $priceItem = $this->getPricePerItem($value['volume'], $routePrice, $servicePrice);
            }
            $data['with_minimum']   = $priceItem['with_minimum'];
            $data['price']          = $priceItem['price'];
            $data['clear_price']    = $priceItem['clear_price'];
            $data['service_price']  = $servicePrice;
            $data['name']           = $value['name'];
            $data['weight']         = $value['weight'];
            $data['route_price_id'] = $value['route_price_id'];
            $data['route_price']    = $routePrice;
            $data['volume']         = $value['volume'];
            $data['unit']           = $value['unit'] ?? 'buah';
            $data['unit_count']     = $value['unit_count'];
            $data['is_finance']     = $value['is_finance'] ?? false;
            $data['service']        = $service ?? null;
            $data['service_id']     = $service['id'] ?? null;
            $data['id']             = $value['id'] ?? null;
            $itemData[] = $data;
        }
        if ($savePrice) {
            foreach ($itemData as $key => $value) {
                $this->item->where('id', $value['id'])->update([
                    'price' => $value['price'],
                    'clear_price' => $value['clear_price'],
                    'service_price' => $value['service_price']
                ]);
            }
        }
        // if some item have with_minimum false (ada item dengan tipe kendaraan yg with_minimumnya di buat false), return normal price
        if (in_array(false, array_column($itemData, 'with_minimum'))) {
            $withMinimum = false;
            $total = array_sum(array_column($itemData, 'price'));
            $totalService = array_sum(array_column($itemData, 'service_price'));
            $totalClearPrice = array_sum(array_column($itemData, 'clear_price'));
            $finalTotal = $this->addingPromo($total, $promo);
            $taxAmount = intval($finalTotal['total']) * ($taxRate / 100);
            $finalWithTax = intval($finalTotal['total']) + $taxAmount;
            $finalWithInsurance = intval($finalTotal['total']) + $insuranceAmount;
            $finalPrice = intval($finalTotal['total']) + $taxAmount + $insuranceAmount;
        } else {
            // else, check total minimum or not
            if ($totalMinimum >= intval($route['minimum_weight'])) {
                $withMinimum = false;
                // normal calculate
                $total = array_sum(array_column($itemData, 'price'));
                $totalService = array_sum(array_column($itemData, 'service_price'));
                $totalClearPrice = array_sum(array_column($itemData, 'clear_price'));
                $finalTotal = $this->addingPromo($total, $promo);
                $taxAmount = intval($finalTotal['total']) * ($taxRate / 100);
                $finalWithTax = intval($finalTotal['total']) + $taxAmount;
                $finalWithInsurance = intval($finalTotal['total']) + $insuranceAmount;
                $finalPrice = intval($finalTotal['total']) + $taxAmount + $insuranceAmount;
            } else {
                $withMinimum = true;
                // minimum calculate
                $total = intval($route['minimum_weight']) * intval($route['minimum_price']);
                $totalService = array_sum(array_column($itemData, 'service_price'));
                // $totalClearPrice = $total - $totalService;
                $totalClearPrice = $total;
                $finalTotal = $this->addingPromo($total, $promo);
                $taxAmount = intval($finalTotal['total']) * ($taxRate / 100);
                $finalWithTax = intval($finalTotal['total']) + $taxAmount;
                $finalWithInsurance = intval($finalTotal['total']) + $insuranceAmount;
                $finalPrice = intval($finalTotal['total']) + $taxAmount + $insuranceAmount;
            }
        }
        $result = (object)[
            'success'                           => true,
            'with_minimum'                      => $withMinimum,
            'total_weight'                      => $totalMinimum,
            'items'                             => $itemData,
            'promo'                             => $promo,
            'total_service'                     => round($totalService), // total service
            'total_discount'                    => round($finalTotal['discount']), // total diskon
            'total_clear_price'                 => round($totalClearPrice), // total tanpa diskon, tanpa service, hanya biaya barang
            'total_price_with_service'          => round($total), // total tanpa diskon, hanya biaya barang dengan service
            'total_tax_rate'                    => $taxRate, // persentase pajak
            'total_tax_amount'                  => round($taxAmount), // jumlah pajak yg didapat dari ($finalTotal['total'] * ($cost['taxRate'] / 100))
            'total_insurance_amount'            => $insuranceAmount,// jumlah asuransi
            'total_price'                       => round($finalTotal['total']), // (total semua item dengan potongan diskon dan biaya service) tanpa pajak, tanpa asuransi
            'total_price_with_tax'              => round($finalWithTax), // total pajak ditambah total_price
            'total_price_with_insurance'        => round($finalWithInsurance), // total asuransi ditambah total_price
            'total_price_with_tax_insurance'    => round($finalPrice), // total asuransi, total pajak, ditambah total_price
        ];
        return $result;
    }

    public function addingPromo($total, $promo) : array
    {
        $total = intval($total);
        if ($promo) {
            $minValue = intval($promo['min_value']);
            $promoDiscount = intval($promo['discount']);
            $promoDiscountMax = intval($promo['discount_max']);
            /**
             * jika harga total biaya lebih tinggi daripada ketentuan minimum biaya untuk mendapatkan promo
             */
            if ($total >= $minValue) {
                /** perhitungan diskon (step 1) */
                $discount = ($total * $promoDiscount) / 100;
                /** jika jumlah harga diskon lebih besar daripada jumlah ketentuan maksimal diskon */
                if (intval($discount) >= $promoDiscountMax) {
                    /** hitung total biaya dikurang diskon menggunakan ketentuan maksimal diskon */
                    $totalWithDiscount = $total - $promoDiscountMax;
                    $total = ['total' => $totalWithDiscount, 'discount' => $promoDiscountMax];
                } else {
                    /** hitung total biaya dikurang diskon menggunakan diskon yang didapat dari perhitungan diskon (step 1) */
                    $totalWithDiscount = $total - intval($discount);
                    $total = ['total' => $totalWithDiscount, 'discount' => $discount];
                }
            } else {
                /**
                 * jika harga total biaya lebih kecil daripada ketentuan minimum biaya untuk mendapatkan promo,
                 * maka hapus diskon
                 */
                $total = [
                    'total' => $total,
                    'discount' => 0
                ];
            }
        } else {
            /**
             * jika tidak memakai promo,
             * maka hapus diskon
             */
            $total = [
                'total' => $total,
                'discount' => 0
            ];
        }
        return $total;
    }

    public function getPricePerItem($totalWeight, $routePrice, $servicePrice)
    {
        if ($routePrice['with_minimum']) {
            $withMinimum = true;
        } else {
            $withMinimum = false;
        }
        if ($withMinimum) {
            $price = (intval($totalWeight) * intval($routePrice['price'])) + $servicePrice;
            $clearPrice = (intval($totalWeight) * intval($routePrice['price']));
        } else {
            $price = intval($routePrice['price']) + $servicePrice;
            $clearPrice = intval($routePrice['price']);
        }
        return [
            'with_minimum' => $withMinimum,
            'price' => $price,
            'clear_price' => $clearPrice
        ];
    }

    /**
     * get total cost amount for dashboard
     */
    public function getTotalCostAmountRepo($data = [])
    {
        $cost = $this->cost->select('amount')->whereHas('pickup', function($q) use ($data) {
            $q->whereDate('created_at', '>=', Carbon::parse($data['startDate'])->toDateTimeString())
                ->whereDate('created_at', '<=', Carbon::parse($data['endDate'])->toDateTimeString())
                ->whereIn('branch_id', $data['branch'])
                ->where('status', '!=', 'canceled');
        })->get();
        if (!$cost) {
            return 0;
        }
        $extraCost = [];
        foreach ($cost as $key => $value) {
            $extraCost[] = $this->extraCost->where('cost_id', $value['id'])->get();
        }
        foreach ($extraCost as $key => $value) {
            if(count($value) == 0)
            {
                unset($extraCost[$key]);
            }
        }
        $extraCost = collect($extraCost)->values();
        $extraCost = $extraCost->flatten()->toArray();
        $extra = array_sum(array_column($extraCost, 'amount'));
        $cost = array_sum(array_column($cost->toArray(), 'amount'));
        $total = intval($extra) + intval($cost);
        $omset = intval($total) / 1000;
        return $omset;
    }

    /**
     * mendapatkan data payment method untuk dashboard
     * Cash, COD, Trasnfer, Tempo
     */
    public function getDashboardPaymentMethodRepo($data = [])
    {
        $cost = $this->cost->select('method')->whereHas('pickup', function($q) use ($data) {
            $q->whereDate('created_at', '>=', Carbon::parse($data['startDate'])->toDateTimeString())
                ->whereDate('created_at', '<=', Carbon::parse($data['endDate'])->toDateTimeString())
                ->whereIn('branch_id', $data['branch']);
        })->get();
        $cost = collect($cost);
        $cod = $cost->filter(function($q) {
           return strtolower($q->method) == 'cod';
        })->values()->count();
        $cash = $cost->filter(function($q) {
            return strtolower($q->method) == 'cash';
        })->values()->count();
        $transfer = $cost->filter(function($q) {
            return strtolower($q->method) == 'transfer';
        })->values()->count();
        $tempo = $cost->filter(function($q) {
            return strtolower($q->method) == 'tempo';
        })->values()->count();
        $noMethod = $cost->filter(function($q) {
            return $q->method == null;
        })->values()->count();
        $result = [
            ['COD', $cod, false],
            ['Cash', $cash, false],
            ['Transfer', $transfer, false],
            ['Tempo', $tempo, false],
            ['Belum Ada Metode', $noMethod, false]
        ];
        return $result;
    }

    /**
     * mendapatkan data status pembayaran untuk dashboard
     */
    public function getDashboardPaymentStatusRepo($data = [])
    {
        $cost = $this->cost->select('status')->whereHas('pickup', function($q) use ($data) {
            $q->whereDate('created_at', '>=', Carbon::parse($data['startDate'])->toDateTimeString())
                ->whereDate('created_at', '<=', Carbon::parse($data['endDate'])->toDateTimeString())
                ->whereIn('branch_id', $data['branch']);
        })->get();
        $cost = collect($cost);
        $hutang = $cost->filter(function($q) {
           return strtolower($q->status) == 'hutang';
        })->values()->count();
        $lunas = $cost->filter(function($q) {
            return strtolower($q->status) == 'lunas';
        })->values()->count();
        $noStatus = $cost->filter(function($q) {
            return $q->status == null;
        })->values()->count();
        $result = [
            ['Hutang', $hutang, false],
            ['Lunas', $lunas, false],
            ['Belum Ada Status', $noStatus, false]
        ];
        return $result;
    }

    /**
     * get total extra cost for dashboard
     */
    public function getTotalExtraCostRepo($data = [])
    {
        $extraCost = $this->extraCost->select('amount')->whereHas('cost', function($q) use ($data) {
            $q->whereHas('pickup', function($q) use ($data) {
                $q->whereDate('created_at', '>=', Carbon::parse($data['startDate'])->toDateTimeString())
                    ->whereDate('created_at', '<=', Carbon::parse($data['endDate'])->toDateTimeString())
                    ->whereIn('branch_id', $data['branch']);
            });
        })->get()->toArray();
        if (!$extraCost) {
            return 0;
        }
        foreach ($extraCost as $key => $value) {
            if(count($value) == 0)
            {
                unset($extraCost[$key]);
            }
        }
        $extraCost = collect($extraCost)->values();
        $extraCost = $extraCost->flatten()->toArray();
        $extra = array_sum(array_column($extraCost, 'amount'));
        $result = intval($extra) / 1000;
        return $result;
    }

    /**
     * get bill payable for dashboard (tagihan berjalan)
     */
    public function getBillPayableRepo($data = [])
    {
        $cost = $this->cost->where(function($q) {
            $q->where('status','!=','Lunas')->orWhere('status', null);
        })->whereHas('pickup', function($q) use ($data) {
            $q->whereDate('created_at', '>=', Carbon::parse($data['startDate'])->toDateTimeString())
                ->whereDate('created_at', '<=', Carbon::parse($data['endDate'])->toDateTimeString())
                ->whereIn('branch_id', $data['branch'])
                ->where('status', '!=', 'canceled');
        })->get()->toArray();
        if (!$cost) {
            return 0;
        }
        $cost = array_sum(array_column($cost, 'amount'));
        $result = intval($cost) / 1000;
        return $result;
    }

    /**
     * get bill paid off for dashboard
     */
    public function getBillPaidOffRepo($data = [])
    {
        $cost = $this->cost->where(function($q) {
            $q->where('status','Lunas');
        })->whereHas('pickup', function($q) use ($data) {
            $q->whereDate('created_at', '>=', Carbon::parse($data['startDate'])->toDateTimeString())
                ->whereDate('created_at', '<=', Carbon::parse($data['endDate'])->toDateTimeString())
                ->whereIn('branch_id', $data['branch'])
                ->where('status', '!=', 'canceled');
        })->get()->toArray();
        if (!$cost) {
            return 0;
        }
        $cost = array_sum(array_column($cost, 'amount'));
        $result = intval($cost) / 1000;
        return $result;
    }

    /**
     * get total margin for dashboard
     */
    public function getTotalMarginRepo($data = [])
    {
        $cost = $this->cost->select('amount','id')->whereHas('pickup', function($q) use ($data) {
            $q->whereDate('created_at', '>=', Carbon::parse($data['startDate'])->toDateTimeString())
                ->whereDate('created_at', '<=', Carbon::parse($data['endDate'])->toDateTimeString())
                ->whereIn('branch_id', $data['branch'])
                ->where('status', '!=', 'canceled');
        })->get();
        if (!$cost) {
            return 0;
        }
        $extraCost = [];
        foreach ($cost as $key => $value) {
            $extraCost[] = $this->extraCost->select('amount')->where('cost_id', $value['id'])->get();
        }
        foreach ($extraCost as $key => $value) {
            if(count($value) == 0)
            {
                unset($extraCost[$key]);
            }
        }
        $extraCost = collect($extraCost)->values();
        $extraCost = $extraCost->flatten()->toArray();
        $extra = array_sum(array_column($extraCost, 'amount'));
        $cost = array_sum(array_column($cost->toArray(), 'amount'));
        $total = intval($cost) - intval($extra);
        $margin = intval($total) / 1000;
        return $margin;
    }
}

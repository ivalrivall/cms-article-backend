<?php
namespace App\Services;

// MODELS
use App\Models\User;

// REPO
use App\Repositories\BillRepository;
use App\Repositories\RouteRepository;

// OTHER
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class BillService {

    protected $billRepository;
    protected $routeRepository;

    public function __construct(BillRepository $billRepository, RouteRepository $routeRepository)
    {
        $this->billRepository = $billRepository;
        $this->routeRepository = $routeRepository;
    }

    /**
     * Get all bill.
     *
     * @return String
     */
    public function getAll()
    {
        return $this->billRepository->getAll();
    }

    /**
     * Calculate price.
     *
     * @return mixed
     */
    public function calculatePriceService($items = [], $route, $promo, $cost = [], $savePrice)
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Item tidak ditemukan');
        }

        if (empty($route)) {
            throw new InvalidArgumentException('Rute tidak masuk dalam jangkauan');
        }

        if (empty($cost)) {
            throw new InvalidArgumentException('Biaya gagal di dapatkan');
        }

        $validator = Validator::make($cost, [
            'taxRate'   => 'bail|required|numeric|min:0',
            'insuranceAmount' => 'bail|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $validator = Validator::make($route->toArray(), [
            'origin'                => 'bail|required',
            'destination_district'  => 'bail|required',
            'destination_city'      => 'bail|required',
            // 'price'                 => 'bail|required|numeric',
            // 'price_motorcycle'      => 'bail|required|numeric',
            // 'price_car'             => 'bail|required|numeric',
            'minimum_weight'        => 'bail|required|numeric'
        ]);

        if ($validator->fails()) {
            DB::rollback();
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $validator = Validator::make($promo->toArray(), [
            'discount'      => 'bail|required|numeric',
            'discount_max'  => 'bail|required|numeric',
            'min_value'     => 'bail|required|numeric',
            'start_at'      => 'bail|required',
            'end_at'        => 'bail|required',
            'max_used'      => 'bail|required|numeric',
            'code'          => 'bail|required',
            'scope'         => 'bail|required'
        ]);

        if ($validator->fails()) {
            DB::rollback();
            throw new InvalidArgumentException($validator->errors()->first());
        }

        foreach ($items as $key => $value) {
            $validator = Validator::make($value, [
                'unit'          => 'bail|required',
                'unit_count'    => 'bail|required|numeric',
                'name'          => 'bail|required',
                'weight'        => 'bail|required|numeric',
                'volume'        => 'bail|required|numeric',
                'service_id'    => 'bail|nullable|present',
                'route_price_id'=> 'bail|required'
            ]);

            if ($validator->fails()) {
                DB::rollback();
                throw new InvalidArgumentException($validator->errors()->first());
            }
        }

        /**
         * hitung perkiraan biaya sementara
         */
        try {
            $result = $this->billRepository->calculatePriceRepo($items, $route, $promo, $savePrice, $cost);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal memperkirakan biaya');
        }

        if (!$result->success) {
            throw new InvalidArgumentException($result->message);
        }

        return $result;
    }

    /**
     * Calculate price without promo.
     *
     * @return mixed
     */
    public function calculatePriceWithoutPromoService($items = [], $route = [], $cost = [], $savePrice)
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Item tidak ditemukan');
        }
        if (empty($route)) {
            throw new InvalidArgumentException('Rute tidak masuk dalam jangkauan');
        }
        if (empty($cost)) {
            throw new InvalidArgumentException('Biaya gagal di dapatkan');
        }

        $validator = Validator::make($cost, [
            'taxRate'   => 'bail|required|numeric|min:0',
            'insuranceAmount' => 'bail|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        foreach ($items as $key => $value) {
            $validator = Validator::make(collect($value)->toArray(), [
                'unit'              => 'bail|required',
                'unit_count'        => 'bail|required|numeric',
                'route_price_id'    => 'bail|required',
                'name'              => 'bail|required',
                'weight'            => 'bail|required|numeric',
                'volume'            => 'bail|required|numeric',
                'service_id'        => 'bail|nullable|present'
            ]);

            if ($validator->fails()) {
                throw new InvalidArgumentException($validator->errors()->first());
            }

            // $validator = Validator::make($value['service'], [
            //     'name'  => 'bail|required',
            //     'id'    => 'bail|required|numeric',
            //     'price' => 'bail|required|numeric|min:0',
            // ]);

            // if ($validator->fails()) {
            //     throw new InvalidArgumentException('(service) '. $validator->errors()->first());
            // }

            // $validator = Validator::make($value['routePrice'], [
            //     'type'  => 'bail|required',
            //     'id'    => 'bail|required|numeric',
            //     'price' => 'bail|required|numeric|min:0',
            // ]);

            // if ($validator->fails()) {
            //     throw new InvalidArgumentException('(biaya rute) '. $validator->errors()->first());
            // }
        }

        DB::beginTransaction();

        try {
            $result = $this->billRepository->calculatePriceRepo($items, $route, null, $savePrice, $cost);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal memperkirakan biaya');
        }

        if (!$result->success) {
            DB::rollback();
            throw new InvalidArgumentException($result->message);
        }

        DB::commit();
        return $result;
    }

    /**
     * get total cost amount for dashboard
     */
    public function getTotalCostAmountService($data = [])
    {
        $validator = Validator::make($data['filter'], [
            'startDate' => 'bail|required',
            'endDate'   => 'bail|required',
            'branch'    => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->billRepository->getTotalCostAmountRepo($data['filter']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan omset');
        }

        return $result;
    }

    /**
     * get dashboard payment method
     */
    public function getDashboardPaymentMethodService($data = [])
    {
        $validator = Validator::make($data['filter'], [
            'startDate' => 'bail|required',
            'endDate'   => 'bail|required',
            'branch'    => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->billRepository->getDashboardPaymentMethodRepo($data['filter']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan data dashboard metode pembayaran');
        }

        return $result;
    }

    /**
     * get dashboard payment status
     */
    public function getDashboardPaymentStatusService($data = [])
    {
        $validator = Validator::make($data['filter'], [
            'startDate' => 'bail|required',
            'endDate'   => 'bail|required',
            'branch'    => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->billRepository->getDashboardPaymentStatusRepo($data['filter']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan data dashboard status pembayaran');
        }

        return $result;
    }

    /**
     * get total extra service for dashboard
     */
    public function getTotalExtraCostService($data = [])
    {
        $validator = Validator::make($data['filter'], [
            'startDate' => 'bail|required',
            'endDate'   => 'bail|required',
            'branch'    => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->billRepository->getTotalExtraCostRepo($data['filter']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan total biaya extra');
        }

        return $result;
    }

    /**
     * get cost detail by pickup
     */
    public function getCostByPickupService($data = [])
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->billRepository->getByPickupId($data['pickupId']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan data tagihan');
        }

        return $result;
    }

    /**
     * mendapatkan tagihan yang blm dibayar untuk dashboard
     */
    public function getBillPayableService($data = [])
    {
        $validator = Validator::make($data['filter'], [
            'startDate' => 'bail|required',
            'endDate'   => 'bail|required',
            'branch'    => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->billRepository->getBillPayableRepo($data['filter']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan tagihan yang belum terbayar');
        }

        return $result;
    }

    /**
     * mendapatkan tagihan yang sudah dibayar untuk dashboard
     */
    public function getBillPaidOffService($data = [])
    {
        $validator = Validator::make($data['filter'], [
            'startDate' => 'bail|required',
            'endDate'   => 'bail|required',
            'branch'    => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->billRepository->getBillPaidOffRepo($data['filter']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan tagihan yang sudah terbayar');
        }

        return $result;
    }

    /**
     * get total margin
     */
    public function getTotalMarginService($data = [])
    {
        $validator = Validator::make($data['filter'], [
            'startDate' => 'bail|required',
            'endDate'   => 'bail|required',
            'branch'    => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->billRepository->getTotalMarginRepo($data['filter']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan margin');
        }

        return $result;
    }
}

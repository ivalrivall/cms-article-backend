<?php
namespace App\Services;

use App\Repositories\ProofOfPickupRepository;
use App\Repositories\PickupRepository;
use App\Repositories\PickupPlanRepository;
use App\Repositories\ItemRepository;
use App\Repositories\TrackingRepository;
use App\Repositories\BillRepository;
use App\Repositories\PromoRepository;
use App\Repositories\RouteRepository;
use App\Repositories\CostRepository;
use App\Repositories\DriverRepository;
use App\Repositories\UserRepository;
use App\Repositories\AppContentRepository;
use App\Repositories\NotificationRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ProofOfPickupService {

    protected $proofOfPickupRepository;
    protected $pickupRepository;
    protected $itemRepository;
    protected $trackingRepository;
    protected $billRepository;
    protected $promoRepository;
    protected $routeRepository;
    protected $costRepository;
    protected $pickupPlanRepository;
    protected $driverRepository;
    protected $userRepository;
    protected $appContentRepository;
    protected $notifRepository;

    public function __construct(
        ProofOfPickupRepository $proofOfPickupRepository,
        PickupRepository $pickupRepository,
        ItemRepository $itemRepository,
        TrackingRepository $trackingRepository,
        BillRepository $billRepository,
        PromoRepository $promoRepository,
        RouteRepository $routeRepository,
        CostRepository $costRepository,
        PickupPlanRepository $pickupPlanRepository,
        DriverRepository $driverRepository,
        UserRepository $userRepository,
        AppContentRepository $appContentRepository,
        NotificationRepository $notifRepository
    )
    {
        $this->popRepository = $proofOfPickupRepository;
        $this->pickupRepository = $pickupRepository;
        $this->itemRepository = $itemRepository;
        $this->trackingRepository = $trackingRepository;
        $this->billRepository = $billRepository;
        $this->promoRepository = $promoRepository;
        $this->routeRepository = $routeRepository;
        $this->costRepository = $costRepository;
        $this->pickupPlanRepository = $pickupPlanRepository;
        $this->driverRepository = $driverRepository;
        $this->userRepository = $userRepository;
        $this->appContentRepository = $appContentRepository;
        $this->notifRepository = $notifRepository;
    }

    /**
     * create pop service
     *
     * @param array $data
     * @return String
     */
    public function createPOPService($data)
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required',
            'notes' => 'bail|present',
            'driverPick' => 'bail|boolean|required',
            'userId' => 'bail|required',
            'statusPick' => 'bail|required|string'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $this->pickupRepository->checkPickupHasPickupPlan($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        try {
            $this->popRepository->validateOrder($data['pickupId']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        DB::beginTransaction();
        // CREATE POP
        try {
            $result = $this->popRepository->createPOPRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal membuat proof of pickup');
        }

        // START CALCULATE BILL
        try {
            $route = $this->routeRepository->getRouteByPickupRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Rute gagal ditemukan');
        }

        if ($route == null) {
            throw new InvalidArgumentException('Perhitungan biaya gagal, rute pengiriman tidak ditemukan');
        }

        try {
            $promo = $this->promoRepository->getPromoByPickup($data['pickupId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Perhitungan biaya gagal, promo gagal ditemukan');
        }

        try {
            $items = $this->itemRepository->fetchItemByPickupRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Perhitungan biaya gagal, Gagal mendapatkan item');
        }

        $items = collect($items)->values()->all();

        // GET COST
        try {
            $cost = $this->costRepository->getByPickup($data['pickupId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan data biaya berdasarkan pickup');
        }

        if (!$cost) {
            $cost = [
                'taxRate' => 0,
                'insuranceAmount' => 0
            ];
        } else {
            $cost = [
                'taxRate' => $cost->tax_rate,
                'insuranceAmount' => $cost->insurance_amount
            ];
        }
        // END GET COST

        // ESTIMATE PRICE AND SAVE PRICE PER ITEM
        try {
            $bill = $this->billRepository->calculatePriceRepo($items, $route, $promo, true, $cost);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Perhitungan biaya gagal, Gagal menghitung total biaya');
        }

        if ($bill->success == false) {
            throw new InvalidArgumentException($bill->message);
        }

        $cost = [
            'pickupId' => $data['pickupId'],
            'amount' => $bill->total_price,
            'clearAmount' => $bill->total_clear_price,
            'discount' => $bill->total_discount,
            'service' => $bill->total_service,
            'amountWithService' => $bill->total_price_with_service,
            'taxRate' => $bill->total_tax_rate,
            'taxAmount' => $bill->total_tax_amount,
            'insuranceAmount' => $bill->total_insurance_amount,
            'amountWithTax' => $bill->total_price_with_tax,
            'amountWithInsurance' => $bill->total_price_with_insurance,
            'amountWithTaxInsurance' => $bill->total_price_with_tax_insurance,
        ];
        try {
            $this->costRepository->saveOrUpdateCostRepo($cost);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Perhitungan biaya gagal, Gagal menyimpan total biaya');
        }
        // END CALCULATE BILL

        // CREATE TRACKING
        if ($data['driverPick']) {
            $status = 'draft';
            $picture = $data['picture'];
            if ($data['statusPick'] == 'success') {
                $notes = 'barang berhasil dipickup';
                // GET TEMPLATE NOTIFICATION
                try {
                    $template = $this->appContentRepository->getDataNotificationRepo('submit-pop-driver');
                } catch (Exception $e) {
                    DB::rollback();
                    Log::info($e->getMessage());
                    Log::error($e);
                    throw new InvalidArgumentException('Gagal mendapat template notifikasi');
                }
            }
        } else {
            $status = $data['popStatus'];
            $picture = null;
            if ($data['statusPick'] == 'success') {
                $notes = 'barang diterima digudang/dikonter';
                // GET TEMPLATE NOTIFICATION
                try {
                    $template = $this->appContentRepository->getDataNotificationRepo('submit-pop-admin');
                } catch (Exception $e) {
                    DB::rollback();
                    Log::info($e->getMessage());
                    Log::error($e);
                    throw new InvalidArgumentException('Gagal mendapat template notifikasi');
                }
            }
        }
        if ($data['statusPick'] == 'failed') {
            $notes = 'barang gagal di pickup';
            // GET TEMPLATE NOTIFICATION
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('failed-pop');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        }
        if ($data['statusPick'] == 'updated') {
            $notes = 'barang di pickup dengan perubahan data';
            // GET TEMPLATE NOTIFICATION
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('updated-pop');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        }
        $tracking = [
            'pickupId' => $data['pickupId'],
            'docs' => 'proof-of-pickup',
            'status' => $status,
            'notes' => $notes,
            'picture' => $picture,
        ];
        try {
            $this->trackingRepository->recordTrackingByPickupRepo($tracking);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal menyimpan data tracking');
        }

        try {
            $this->driverRepository->unassignDriverPickupPlan($data['pickupId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengubah ketersediaan driver');
        }

        try {
            $pickup = $this->pickupRepository->getById($data['pickupId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapat data order');
        }

        // SEND FCM
        try {
            $fcm = $this->userRepository->getFcmUserRepo($pickup['user_id']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            throw new InvalidArgumentException('Gagal mendapatkan data fcm');
        }

        if (count($fcm) > 0) {
            try {
                $notifPayload = [
                    'fcm' => $fcm,
                    'title' => $template->title,
                    'body' => $template->body,
                    'jsonData' => collect($result['pop'])->toArray()
                ];
                $resultNotification = $this->notifRepository->sendFCMRepo($notifPayload);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                throw new InvalidArgumentException('Gagal mengirim notifikasi');
            }
        }
        // END SEND FCM

        DB::commit();
        return $result;
    }

    /**
     * get outstanding proof of pickup
     * @param array $data
     */
    public function getOutstandingService($data = [])
    {
        $validator = Validator::make($data, [
            'perPage' => 'bail|present',
            'sort' => 'bail|present',
            'page' => 'bail|present',
            'general' => 'bail|present',
            'customer' => 'bail|present',
            'pickupOrderNo' => 'bail|present',
            'requestPickupDate' => 'bail|present',
            'pickupPlanNo' => 'bail|present',
            'branchId' => 'bail|present'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->popRepository->getOutstandingPickupRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * get submitted proof of pickup
     * @param array $data
     */
    public function getSubmittedService($data = [])
    {
        $validator = Validator::make($data, [
            'perPage' => 'bail|present',
            'sort' => 'bail|present',
            'page' => 'bail|present',
            'general' => 'bail|present',
            'customer' => 'bail|present',
            'popNumber' => 'bail|present',
            'popDate' => 'bail|present',
            'poNumber' => 'bail|present',
            'popStatus' => 'bail|present',
            'poStatus' => 'bail|present',
            'poCreatedDate' => 'bail|present',
            'poPickupDate' => 'bail|present',
            'pickupPlanNumber' => 'bail|present',
            'driverPick' => 'bail|present',
            'branchId' => 'bail|present',
            'statusPick' => 'bail|present'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->popRepository->getSubmittedPickupRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * get pending and draft pickup
     */
    public function getPendingAndDraftService($branchId)
    {
        try {
            $result = $this->popRepository->getPendingAndDraftRepo($branchId);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * update pop
     * @param array $data
     */
    public function updatePOPService($data = [])
    {
        $validator = Validator::make($data, [
            'pickup' => 'bail|required',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $validator = Validator::make($data['pickup'], [
            'items' => 'bail|required',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        foreach ($data['pickup']['items'] as $key => $value) {
            $validator = Validator::make($value, [
                'id' => 'bail|required',
                'name' => 'bail|required',
                'volume' => 'bail|required',
                'weight' => 'bail|required',
                'route_price_id' => 'bail|required',
                'service_id' => 'bail|present',
                'unit_count' => 'bail|required',
                'unit' => 'bail|required',
                'price' => 'bail|required',
            ]);

            if ($validator->fails()) {
                throw new InvalidArgumentException($validator->errors()->first());
            }
        }

        DB::beginTransaction();

        // CHECK PICKUP HAVE SHIPMENT PLAN
        try {
            $this->pickupRepository->checkPickupHaveShipmentPlanByPickup($data['pickup']['id']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        try {
            $items = $this->itemRepository->updatePickupItemsRepo($data['pickup']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // START CALCULATE BILL
        $route = ['pickupId' => $data['pickup']['id']];
        try {
            $route = $this->routeRepository->getRouteByPickupRepo($route);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Perhitungan biaya gagal, rute pengiriman tidak ditemukan');
        }

        if ($route == null) {
            throw new InvalidArgumentException('Perhitungan biaya gagal, rute pengiriman tidak ditemukan');
        }

        try {
            $promo = $this->promoRepository->getPromoByPickup($data['pickup']['id']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Perhitungan biaya gagal, rute pengiriman tidak ditemukan');
        }

        try {
            $cost = $this->costRepository->getByPickup($data['pickup']['id']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan data tagihan');
        }

        $items = collect($items)->values()->all();

        // ESTIMATE PRICE AND SAVE PRICE PER ITEM
        $cost = [
            'taxRate' => $cost['tax_rate'],
            'insuranceAmount' => $cost['insurance_amount']
        ];
        try {
            $bill = $this->billRepository->calculatePriceRepo($items, $route, $promo, true, $cost);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Perhitungan biaya gagal, Gagal menghitung total biaya');
        }

        if ($bill->success == false) {
            throw new InvalidArgumentException($bill->message);
        }

        $cost = [
            'pickupId' => $data['pickup']['id'],
            'amount' => $bill->total_price,
            'clearAmount' => $bill->total_clear_price,
            'discount' => $bill->total_discount,
            'service' => $bill->total_service,
            'amountWithService' => $bill->total_price_with_service,
            'amountWithTax' => $bill->total_price_with_tax,
            'amountWithInsurance' => $bill->total_price_with_insurance,
            'amountWithTaxInsurance' => $bill->total_price_with_tax_insurance
        ];
        try {
            $this->costRepository->updateOrCreateCostByPickupIdRepo($cost);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Perhitungan biaya gagal, Gagal menyimpan total biaya');
        }
        // END CALCULATE BILL

        try {
            $pop = $this->popRepository->updatePopRepo($data['pickup']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // GET TEMPLATE NOTIFICATION
        if ($data['pickup']['proof_of_pickup']['status_pick'] == 'failed') {
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('failed-pop');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        }
        if ($data['pickup']['proof_of_pickup']['status_pick'] == 'updated') {
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('updated-pop');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        }
        if ($data['pickup']['proof_of_pickup']['status_pick'] == 'success') {
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('pop');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        }

        // SEND FCM
        try {
            $fcm = $this->userRepository->getFcmUserRepo($data['pickup']['user_id']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            throw new InvalidArgumentException('Gagal mendapatkan data fcm');
        }

        if (count($fcm) > 0) {
            try {
                $notifPayload = [
                    'fcm' => $fcm,
                    'title' => $template->title,
                    'body' => $template->body,
                    'jsonData' => collect($pop)->toArray()
                ];
                $resultNotification = $this->notifRepository->sendFCMRepo($notifPayload);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                throw new InvalidArgumentException('Gagal mengirim notifikasi');
            }
        }
        // END SEND FCM
        DB::commit();
        return ['pop' => $pop, 'items' => $items];
    }

    /**
     * get dashboard pop for driver service
     */
    public function getDashboardDriverService($data = [])
    {
        $validator = Validator::make($data, [
            'userId' => 'bail|required',
            'startDate' => 'bail|present',
            'endDate' => 'bail|present'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->pickupPlanRepository->getDashboardDriverRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        return $result;
    }

    /**
     * delete pop
     */
    public function deletePOPService($data = [])
    {
        $validator = Validator::make($data, [
            'popId' => 'bail|required',
            'userId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $this->popRepository->checkPOPHaveShipment($data['popId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // update driver_pick on pop to false
        try {
            $this->popRepository->cancelDriverPick($data['popId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // update pickup status to request
        try {
            $this->pickupRepository->updatePickupToRequestRepo($data['popId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // CANCEL POP
        try {
            $pop = $this->popRepository->deletePopRepo($data['popId'], $data['userId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // SET COST TO O
        try {
            $this->costRepository->setAmountToZeroByPopRepo($data['popId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $pop;
    }
}

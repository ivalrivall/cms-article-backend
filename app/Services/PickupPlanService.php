<?php
namespace App\Services;

use App\Repositories\PickupPlanRepository;
use App\Repositories\PickupRepository;
use App\Repositories\DriverRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\TrackingRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;
use App\Repositories\AppContentRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class PickupPlanService {

    protected $pickupPlanRepository;
    protected $pickupRepository;
    protected $driverRepository;
    protected $vehicleRepository;
    protected $trackingRepository;
    protected $notifRepository;
    protected $userRepository;
    protected $appContentRepository;

    public function __construct(
        PickupPlanRepository $pickupPlanRepository,
        DriverRepository $driverRepository,
        VehicleRepository $vehicleRepository,
        PickupRepository $pickupRepository,
        TrackingRepository $trackingRepository,
        NotificationRepository $notifRepository,
        UserRepository $userRepository,
        AppContentRepository $appContentRepository
    )
    {
        $this->pickupPlanRepository = $pickupPlanRepository;
        $this->driverRepository = $driverRepository;
        $this->vehicleRepository = $vehicleRepository;
        $this->pickupRepository = $pickupRepository;
        $this->trackingRepository = $trackingRepository;
        $this->notifRepository = $notifRepository;
        $this->userRepository = $userRepository;
        $this->appContentRepository = $appContentRepository;
    }

    /**
     * save pickup plan
     *
     * @param array $data
     * @return String
     */
    public function savePickupPlanService($data = [])
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required|array',
            'vehicleId' => 'bail|required|integer',
            'driverId' => 'bail|required|integer',
            'branchId' => 'bail|required',
            'userId' => 'bail|required|integer',
            'customersId' => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        // CHECK EVERY DATE PICKUP PLAN
        // try {
        //     $this->pickupRepository->checkPickupRequestDate($data['pickupId']);
        // } catch (Exception $e) {
        //     Log::info($e->getMessage());
        //     throw new InvalidArgumentException($e->getMessage());
        // }

        DB::beginTransaction();

        // ASSIGN DRIVER TO CURRENT VEHICLE
        try {
            $this->vehicleRepository->assignDriverRepo($data['vehicleId'], $data['driverId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // UPDATE BRANCH ID PADA PICKUP
        try {
            $this->pickupRepository->updateBranchRepo($data['pickupId'], $data['branchId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengupdate branch pada pickup order');
        }

        // SAVE PICKUP PLAN
        try {
            $result = $this->pickupPlanRepository->savePickupPlanRepo($data['pickupId'], $data['vehicleId'], $data['userId'], $data['driverId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal menyimpan pickup plan');
        }

        // GET TEMPLATE NOTIFICATION
        try {
            $template = $this->appContentRepository->getDataNotificationRepo('pickup-plan');
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapat template notifikasi');
        }

        // CREATE TRACKING
        foreach ($data['pickupId'] as $key => $value) {
            $tracking = [
                'pickupId' => $value,
                'docs' => 'pickup-plan',
                'status' => 'applied',
                'notes' => 'petugas pickup akan menuju lokasi penjemputan',
                'picture' => null,
            ];
            try {
                $this->trackingRepository->recordTrackingByPickupRepo($tracking);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal menyimpan data tracking');
                break;
            }

            $driverLog = [
                'pickupId' => $value,
                'driverId' => $data['driverId'],
                'branchFrom' => null,
                'branchTo' => $data['branchId'],
            ];
            try {
                $this->trackingRepository->recordPickupDriverLog($driverLog);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal menyimpan data tracking driver');
                break;
            }
        }

        // SEND FCM
        try {
            $fcm = $this->userRepository->getFcmUserRepo($data['customersId']);
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
                    'jsonData' => collect($result)->toArray()
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
     * delete pickup order inside pickup plan
     *
     * @param array $data
     */
    public function deletePoService($data)
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required',
            'pickupPlanId' => 'bail|required',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $this->pickupRepository->checkPickupHavePOPByPickup($data['pickupId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        try {
            $result = $this->pickupPlanRepository->deletePoRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        DB::commit();
        return $result;
    }

    /**
     * add pickup order
     *
     * @param array $data
     */
    public function addPoService($data)
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required|array',
            'pickupPlanId' => 'bail|required',
            'branchId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        // CHECK ORDER ON PICKUP PLAN HAVE POP
        try {
            $this->pickupPlanRepository->checkOrderOnPickupPlanHavePOP($data['pickupPlanId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        DB::beginTransaction();
        try {
            $result = $this->pickupPlanRepository->addPoRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        DB::commit();
        return $result;
    }

    /**
     * delete pickup plan
     *
     * @param array $data
     */
    public function deletePickupPlanService($data = [])
    {
        $validator = Validator::make($data, [
            'userId' => 'bail|required',
            'pickupPlanId' => 'bail|required',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        // DELETE PICKUP PLAN
        try {
            $pickupPlan = $this->pickupPlanRepository->deletePickupPlanRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        // UNASSIGN DRIVER TO CURRENT VEHICLE
        try {
            $this->vehicleRepository->unassignDriverRepo($pickupPlan);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        DB::commit();
        return $pickupPlan;
    }

    /**
     * cancel pickup plan
     *
     * @param array $data
     */
    public function cancelPickupPlanService($data = [])
    {
        $validator = Validator::make($data, [
            'userId' => 'bail|required',
            'pickupPlanId' => 'bail|required',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        // CHECK EVERY ORDER HAVE POP OR NOT
        try {
            $this->pickupRepository->checkPickupHavePOPByPickupPlan($data['pickupPlanId']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // CHECK EVERY ORDER HAVE SHIPMENT OR NOT
        try {
            $this->pickupRepository->checkPickupHaveShipment($data['pickupPlanId']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // CANCEL PICKUP PLAN
        try {
            $pickupPlan = $this->pickupPlanRepository->cancelPickupPlanRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        // UNASSIGN DRIVER TO CURRENT VEHICLE
        try {
            $this->vehicleRepository->unassignDriverRepo($pickupPlan);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        // REMOVE PICKUP PLAN FROM ORDER
        try {
            $this->pickupRepository->cancelPickupPlanFromOrderRepo($data['pickupPlanId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        DB::commit();
        return $pickupPlan;
    }

    /**
     * get history pickup plan driver
     */
    public function getHistoryPickupPlanService($data = [])
    {
        $validator = Validator::make($data, [
            'userId' => 'required',
            'startDate' => 'bail|present|string',
            'endDate' => 'bail|present|string'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->pickupPlanRepository->getHistoryPickupPlanRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }
}

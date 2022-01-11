<?php
namespace App\Services;

use App\Repositories\TransitRepository;
use App\Repositories\PickupRepository;
use App\Repositories\ItemRepository;
use App\Repositories\TrackingRepository;
use App\Repositories\ShipmentPlanRepository;
use App\Repositories\AppContentRepository;
use App\Repositories\UserRepository;
use App\Repositories\NotificationRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class TransitService {

    protected $transitRepository;
    protected $pickupRepository;
    protected $itemRepository;
    protected $trackingRepository;
    protected $shipmentPlanRepository;
    protected $appContentRepository;
    protected $userRepository;
    protected $notifRepository;

    public function __construct(
        TransitRepository $transitRepository,
        PickupRepository $pickupRepository,
        ItemRepository $itemRepository,
        TrackingRepository $trackingRepository,
        ShipmentPlanRepository $shipmentPlanRepository,
        AppContentRepository $appContentRepository,
        UserRepository $userRepository,
        NotificationRepository $notifRepository
    )
    {
        $this->transitRepository = $transitRepository;
        $this->pickupRepository = $pickupRepository;
        $this->itemRepository = $itemRepository;
        $this->trackingRepository = $trackingRepository;
        $this->shipmentPlanRepository = $shipmentPlanRepository;
        $this->appContentRepository = $appContentRepository;
        $this->userRepository = $userRepository;
        $this->notifRepository = $notifRepository;
    }

    /**
     * submit transit service
     *
     * @param array $data
     * @return String
     */
    public function submitTransitService($data)
    {
        $validator = Validator::make($data, [
            'received' => 'bail|required|boolean',
            'notes' => 'bail|required',
            'status' => 'bail|required',
            'userId' => 'bail|required',
            'transitId' => 'bail|required',
            'pickupId' => 'bail|required',
            'statusTransit' => 'bail|required',
            'failedNotes' => 'bail|present'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        // CREATE SUBMIT TRANSIT
        try {
            $result = $this->transitRepository->submitTransitRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal membuat transit pickup');
        }

        // REMOVE ORDER FROM OLD SHIPMENT PLAN
        try {
            $oldShipmentPlan = $this->shipmentPlanRepository->removeOrderFromShipmentPlanRepo($data['pickupId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // IF NO ORDER MORE IN OLD SHIPMENT PLAN, DELETE SHIPMENT PLAN
        try {
            $this->shipmentPlanRepository->deleteShipmentPlanHaveZeroOrder($oldShipmentPlan['shipmentPlanId'], $data['userId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // CREATE TRACKING
        $tracking = [
            'pickupId' => $data['pickupId'],
            'docs' => 'transit',
            'status' => $data['status'],
            'notes' => $data['notes'],
            'picture' => null,
        ];
        try {
            $this->trackingRepository->recordTrackingByPickupRepo($tracking);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal menyimpan data tracking');
        }

        // GET USER ID
        try {
            $pickup = $this->pickupRepository->getById($data['pickupId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal menyimpan data tracking');
        }
        $customerId = $pickup->user_id;
        // GET TEMPLATE NOTIFICATION FOR SUCCESS TRANSIT
        if ($data['statusTransit'] == 'success') {
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('success-incoming-order');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        }
        // GET TEMPLATE NOTIFICATION FOR FAILED TRANSIT
        if ($data['statusTransit'] == 'failed') {
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('failed-incoming-order');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        }

        // SEND FCM
        try {
            $fcm = $this->userRepository->getFcmUserRepo($customerId);
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
                    'jsonData' => [
                        'transitId' => json_encode($result)
                    ]
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
            'transitNumber' => 'bail|present',
            'pickupOrderNo' => 'bail|present',
            'branchId' => 'bail|present',
            'vehicleNumber' => 'bail|present',
            'senderCity' => 'bail|present',
            'receiverCity' => 'bail|present'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->transitRepository->getOutstandingPickupRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
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
            'transitNumber' => 'bail|present',
            'pickupOrderNo' => 'bail|present',
            'branchId' => 'bail|present',
            'vehicleNumber' => 'bail|present',
            'senderCity' => 'bail|present',
            'receiverCity' => 'bail|present'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->transitRepository->getSubmittedPickupRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * get pending and draft transit pickup
     */
    public function getPendingAndDraftService($branchId)
    {
        try {
            $result = $this->transitRepository->getPendingAndDraftRepo($branchId);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * update transit
     * @param array $data
     */
    public function updateTransitService($data = [])
    {
        $validator = Validator::make($data, [
            'transitId' => 'bail|required',
            'status' => 'bail|required',
            'userId' => 'bail|required',
            'statusTransit' => 'bail|required',
            'failedNotes' => 'bail|present'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        if ($data['statusTransit'] == 'failed') {
            $validator = Validator::make($data, [
                'failedNotes' => 'bail|required|string'
            ]);

            if ($validator->fails()) {
                throw new InvalidArgumentException($validator->errors()->first());
            }
        }

        try {
            $this->transitRepository->checkTransitHaveShipment($data['transitId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        DB::beginTransaction();
        try {
            $transit = $this->transitRepository->updateTransitRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        // $tracking = [
        //     'pickupId' => $data['pickupId'],
        //     'docs' => 'transit',
        //     'status' => $data['status'],
        //     'notes' => $data['notes'],
        //     'picture' => null,
        // ];
        // try {
        //     $this->trackingRepository->recordTrackingByPickupRepo($tracking);
        // } catch (Exception $e) {
        //     DB::rollback();
        //     Log::info($e->getMessage());
        //     Log::error($e);
        //     throw new InvalidArgumentException('Gagal menyimpan data tracking');
        // }

        // GET USER ID
        try {
            $pickup = $this->pickupRepository->getById($transit->pickup_id);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal menyimpan data tracking');
        }
        $customerId = $pickup->user_id;

        // GET TEMPLATE NOTIFICATION FOR SUCCESS TRANSIT
        if ($data['statusTransit'] == 'success') {
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('success-incoming-order');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        }
        // GET TEMPLATE NOTIFICATION FOR FAILED TRANSIT
        if ($data['statusTransit'] == 'failed') {
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('failed-incoming-order');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        }

        // SEND FCM
        try {
            $fcm = $this->userRepository->getFcmUserRepo($customerId);
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
                    'jsonData' => [
                        'transitId' => json_encode($transit)
                    ]
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
        return ['transit' => $transit];
    }
}

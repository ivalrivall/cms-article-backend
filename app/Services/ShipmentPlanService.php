<?php
namespace App\Services;

use App\Repositories\ShipmentPlanRepository;
use App\Repositories\PickupRepository;
use App\Repositories\DriverRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\TrackingRepository;
use App\Repositories\BranchRepository;
use App\Repositories\TransitRepository;
use App\Repositories\AppContentRepository;
use App\Repositories\UserRepository;
use App\Repositories\NotificationRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class ShipmentPlanService {

    protected $shipmentPlanRepository;
    protected $pickupRepository;
    protected $driverRepository;
    protected $vehicleRepository;
    protected $trackingRepository;
    protected $branchRepository;
    protected $transitRepository;
    protected $appContentRepository;
    protected $userRepository;
    protected $notifRepository;

    public function __construct(
        ShipmentPlanRepository $shipmentPlanRepository,
        DriverRepository $driverRepository,
        VehicleRepository $vehicleRepository,
        PickupRepository $pickupRepository,
        TrackingRepository $trackingRepository,
        BranchRepository $branchRepository,
        TransitRepository $transitRepository,
        AppContentRepository $appContentRepository,
        UserRepository $userRepository,
        NotificationRepository $notifRepository
    )
    {
        $this->shipmentPlanRepository = $shipmentPlanRepository;
        $this->driverRepository = $driverRepository;
        $this->vehicleRepository = $vehicleRepository;
        $this->pickupRepository = $pickupRepository;
        $this->trackingRepository = $trackingRepository;
        $this->branchRepository = $branchRepository;
        $this->transitRepository = $transitRepository;
        $this->appContentRepository = $appContentRepository;
        $this->userRepository = $userRepository;
        $this->notifRepository = $notifRepository;
    }

    /**
     * save shipment plan
     *
     * @param array $data
     * @return String
     */
    public function saveShipmentPlanService($data)
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required|array',
            'vehicleId' => 'bail|required|integer',
            'driverId' => 'bail|required|integer',
            'userId' => 'bail|required|integer',
            'fleet' => 'bail|present',
            'withFleet' => 'bail|present',
            'branchFrom' => 'bail|present|integer',
            'customersId' => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        // CHECK EVERY DATE SHIPMENT PLAN
        // try {
        //     $this->pickupRepository->checkPickupRequestDate($data['pickupId']);
        // } catch (Exception $e) {
        //     Log::info($e->getMessage());
        //     throw new InvalidArgumentException($e->getMessage());
        // }

        DB::beginTransaction();

        // ASSIGN DRIVER TO CURRENT VEHICLE
        try {
            $vehicle = $this->vehicleRepository->assignDriverRepo($data['vehicleId'], $data['driverId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // UPDATE RESHIPMENT ON TRANSIT TO TRUE IF PICKUP HAVE TRANSIT
        try {
            $this->transitRepository->reshipmentOrderRepo($data['pickupId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal reshipment transit');
        }

        // SAVE SHIPMENT PLAN
        try {
            $payload = [
                'pickupId' => $data['pickupId'],
                'vehicleId' => $data['vehicleId'],
                'userId' => $data['userId'],
                'isTransit' => $data['isTransit'],
                'branchFrom' => $data['branchFrom'],
                'driverId' => $data['driverId']
            ];
            $shipmentPlan = $this->shipmentPlanRepository->saveShipmentPlanRepo($payload);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal menyimpan shipment plan');
        }

        if ($data['withFleet']) {
            $fleetData = [
                'shipmentPlanId' => $shipmentPlan['id'],
                'fleetName' => $data['fleet']['name'],
                'fleetDeparture' => $data['fleet']['departureDate'],
            ];
            try {
                $this->shipmentPlanRepository->updateFleetDataRepo($fleetData);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal menyimpan data armada pada shipment plan');
            }
        }

        // CHECK TRANSIT
        if ($data['isTransit']) {

            $validator = Validator::make($data, [
                'transitBranch' => 'bail|required',
            ]);

            if ($validator->fails()) {
                DB::rollback();
                throw new InvalidArgumentException($validator->errors()->first());
            }

            $notes = 'paket ditransit ke cabang: '.$data['transitBranch']['name'];
            $docs = 'transit';
            $status = 'pending';

            // UPDATE PICKUP BRANCH
            try {
                $branchFrom = $this->pickupRepository->updateBranchRepo($data['pickupId'], $data['transitBranch']['id']);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mengupdate data cabang pada pickup order');
            }

            // UPDATE IS TRANSIT BRANCH
            try {
                $this->pickupRepository->updateIsTransitBranchRepo($data['pickupId'], true);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mengupdate data cabang pada pickup order');
            }

            // TRANSIT HISTORY
            $resultTransit = [];
            foreach ($data['pickupId'] as $key => $value) {
                // $branchFrom = $this->branchRepository->checkBranchByPickupRepo($value);
                try {
                    $transitData = [
                        'pickupId' => $value,
                        'status' => 'pending',
                        'received' => false,
                        'notes' => $notes,
                        'userId' => $data['userId'],
                        'vehicleId' => $data['vehicleId'],
                        'driverId' => $data['driverId']
                    ];
                    $transit = $this->transitRepository->saveTransitRepo($transitData);
                } catch (Exception $e) {
                    DB::rollback();
                    Log::info($e->getMessage());
                    Log::error($e);
                    throw new InvalidArgumentException('Gagal menyimpan transit data');
                }
                if ($data['withFleet']) {
                    $fleetData = [
                        'transitId' => $transit['id'],
                        'fleetName' => $data['fleet']['name'],
                        'fleetDeparture' => $data['fleet']['departureDate'],
                    ];
                    try {
                        $this->transitRepository->updateFleetDataRepo($fleetData);
                    } catch (Exception $e) {
                        DB::rollback();
                        Log::info($e->getMessage());
                        Log::error($e);
                        throw new InvalidArgumentException('Gagal menyimpan data armada pada transit');
                    }
                }
                $resultTransit[] = $transit;
            }

            // GET TEMPLATE NOTIFICATION FOR INCOMING ORDER
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('success-incoming-order');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        } else {
            $notes = 'paket dikirim ke alamat tujuan';
            $docs = 'shipment-plan';
            $status = 'applied';

            // UPDATE IS TRANSIT BRANCH
            try {
                $this->pickupRepository->updateIsTransitBranchRepo($data['pickupId'], false);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mengupdate data cabang pada pickup order');
            }

            // GET PICKUP BRANCH
            try {
                $branchFrom = $this->pickupRepository->getPickupBranchRepo($data['pickupId']);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mengupdate data cabang pada pickup order');
            }

            // GET TEMPLATE NOTIFICATION FOR SHIPMENT PLAN
            try {
                $template = $this->appContentRepository->getDataNotificationRepo('shipment-plan');
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal mendapat template notifikasi');
            }
        }

        // CREATE TRACKING
        foreach ($data['pickupId'] as $key => $value) {
            $tracking = [
                'pickupId' => $value,
                'docs' => $docs,
                'status' => $status,
                'notes' => $notes,
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

            $branch = collect($branchFrom)->firstWhere('id', $value);
            $driverLog = [
                'pickupId' => $value,
                'driverId' => $data['driverId'],
                'branchFrom' => $branch['branch_id'],
                'branchTo' => $data['isTransit'] ? $data['transitBranch']['id'] : null,
            ];
            try {
                $this->trackingRepository->recordPickupDriverLog($driverLog);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal menyimpan data tracking driver');
            }

            if ($data['withFleet']) {
                $fleetName = $data['fleet']['name'];
                $fleetDepartureDate = $data['fleet']['departureDate'];
                $tracking = [
                    'pickupId' => $value,
                    'docs' => $docs,
                    'status' => $status,
                    'notes' => "Paket dikirim dengan armada ($fleetName) dan akan berangkat pada ($fleetDepartureDate)",
                    'picture' => null,
                ];
                try {
                    $this->trackingRepository->recordTrackingByPickupRepo($tracking);
                } catch (Exception $e) {
                    DB::rollback();
                    Log::info($e->getMessage());
                    Log::error($e);
                    throw new InvalidArgumentException('Gagal menyimpan data tracking armada');
                }
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
                    'jsonData' => [
                        'shipmentPlanId' => $shipmentPlan['id']
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
        return (object)[
            'pickupId' => $data['pickupId'],
            'shipmentPlanId' => $shipmentPlan['id'],
            'transit' => $data['isTransit'] ? $resultTransit : null
        ];
    }

    /**
     * delete pickup order inside shipment plan
     *
     * @param array $data
     */
    public function deletePoService($data)
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required',
            'shipmentPlanId' => 'bail|required',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $this->pickupRepository->checkPickupHavePODByPickup($data['pickupId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        try {
            $result = $this->shipmentPlanRepository->deletePoRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
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
            'shipmentPlanId' => 'bail|required',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        // CHECK ORDER ON SHIPMENT PLAN HAVE POD
        try {
            $this->shipmentPlanRepository->checkOrderHavePOD($data['shipmentPlanId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        try {
            $result = $this->shipmentPlanRepository->addPoRepo($data);
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
    // DEPRECATED
    // public function cancelPickupPlanService($data = [])
    // {
    //     $validator = Validator::make($data, [
    //         'userId' => 'bail|required',
    //         'pickupPlanId' => 'bail|required',
    //     ]);

    //     if ($validator->fails()) {
    //         throw new InvalidArgumentException($validator->errors()->first());
    //     }

    //     DB::beginTransaction();
    //     // CANCEL PICKUP PLAN
    //     try {
    //         $pickupPlan = $this->pickupPlanRepository->cancelPickupPlanRepo($data);
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         Log::info($e->getMessage());
    //         throw new InvalidArgumentException($e->getMessage());
    //     }

    //     // UNASSIGN DRIVER TO CURRENT VEHICLE
    //     try {
    //         $this->vehicleRepository->unassignDriverRepo($pickupPlan);
    //     } catch (Exception $e) {
    //         DB::rollback();
    //         Log::info($e->getMessage());
    //         throw new InvalidArgumentException($e->getMessage());
    //     }

    //     DB::commit();
    //     return $pickupPlan;
    // }

    /**
     * cancel shipment plan
     *
     * @param array $data
     */
    public function cancelShipmentPlanService($data = [])
    {
        $validator = Validator::make($data, [
            'userId' => 'bail|required',
            'shipmentPlanId' => 'bail|required',
            'branchId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        // CHECK PICKUPS ON SHIPMENT PLAN HAVE POD
        try {
            $shipmentPlan = $this->shipmentPlanRepository->getShipmentPlanById($data['shipmentPlanId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        $pickups = collect($shipmentPlan->pickups)->toArray();
        foreach ($pickups as $key => $value) {
            try {
                $this->pickupRepository->checkPickupHavePODByPickup($value['id']);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException($e->getMessage());
            }
        }
        // END CHECK PICKUPS ON SHIPMENT PLAN HAVE POD

        // CHECK PICKUP HAVE INCOMING ORDER
        foreach ($pickups as $key => $value) {
            try {
                $this->pickupRepository->checkPickupHaveSubmittedIncomingByPickup($value['id']);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException($e->getMessage());
            }
        }
        // END CHECK PICKUP HAVE INCOMING ORDER

        // CANCEL SHIPMENT PLAN
        try {
            $shipmentPlan = $this->shipmentPlanRepository->cancelShipmentPlanRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // UNASSIGN PICKUP WITH CURRENT SHIPMENT PLAN
        try {
            $this->pickupRepository->cancelShipmentPlanRepo($data['shipmentPlanId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // UNASSIGN DRIVER TO CURRENT VEHICLE
        try {
            $this->vehicleRepository->unassignDriverRepo($shipmentPlan);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // CANCEL TRANSIT
        foreach ($pickups as $key => $value) {
            try {
                $this->transitRepository->cancelTransitRepo($value['id'], $data['userId']);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException($e->getMessage());
            }
        }

        // UPDATE PICKUP BRANCH
        $pickupIds = collect($pickups)->map(function($q) {
            return $q['id'];
        })->values()->all();
        try {
            $this->pickupRepository->updateBranchRepo($pickupIds, $data['branchId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengupdate data cabang pada order');
        }

        DB::commit();
        return $shipmentPlan;
    }

    /**
     * get shipment plan driver
     */
    public function getDriverShipmentPlanListService($data = [])
    {
        $validator = Validator::make($data, [
            'userId' => 'required',
            'startDate' => 'bail|present',
            'endDate' => 'bail|present'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->shipmentPlanRepository->getDriverShipmentPlanListRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * get list po in shipment plan by driver
     */
    public function getPickupOrderDriverShipmentPlanListService($data = [])
    {
        $validator = Validator::make($data, [
            'userId' => 'required',
            'shipmentPlanId' => 'required',
            'filter' => 'bail|present'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        // SHIPMENT PLAN
        try {
            $result = $this->shipmentPlanRepository->getPickupOrderDriverShipmentPlanListRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * get dashboard shipment plan driver
     */
    public function getDashboardDriverService($data = [])
    {
        try {
            $result = $this->shipmentPlanRepository->getDashboardDriverRepo($data['shipmentPlanId']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
	 * Get all pickup where shipment plan id
	 * @param array $data
	 */
	public function getPickupListService($data = [])
	{
		try {
			$result = $this->shipmentPlanRepository->getPickupListRepo($data['shipmentPlanNumber']);
		} catch (Exception $e) {
			Log::info($e->getMessage());
			throw new InvalidArgumentException('Gagal mendapatkan data pickup');
		}
		return $result;
	}

    /**
     * update shipment plan data
     */
    public function updateShipmentPlanService($data = [])
    {
        $validator = Validator::make($data, [
            'id' => 'bail|required|numeric',
            'fleet_name' => 'bail|required|string',
            'fleet_departure' => 'bail|required|string',
            'pickups' => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        // CHECK ORDER ON SHIPMENT PLAN HAVE POD
        try {
            $shipmentPlan = $this->shipmentPlanRepository->checkOrderHavePOD($data['id']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // CHECK SHIPMENT PLAN HAVE SUBMITTED INCOMING/TRANSIT
        try {
            $this->shipmentPlanRepository->checkOrderHaveTransit($shipmentPlan);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // UPDATE FLEET DATA OF SHIPMENT PLAN
        try {
            $payload = [
                'shipmentPlanId' => $data['id'],
                'fleetName' => $data['fleet_name'],
                'fleetDeparture' => $data['fleet_departure']
            ];
            $shipmentPlan = $this->shipmentPlanRepository->updateFleetDataRepo($payload);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        // CREATE TRACKING AND UPDATE FLEET DATA ON TRANSIT
        foreach ($data['pickups'] as $key => $value) {
            $fleetName = $data['fleet_name'];
            $fleetDepartureDate = $data['fleet_departure'];
            $tracking = [
                'pickupId' => $value['id'],
                'docs' => 'shipment-plan',
                'status' => 'applied',
                'notes' => "Perubahan jadwal, paket akan dikirim dengan armada ($fleetName) dan akan berangkat pada ($fleetDepartureDate)",
                'picture' => null,
            ];
            try {
                $this->trackingRepository->recordTrackingByPickupRepo($tracking);
            } catch (Exception $e) {
                DB::rollback();
                Log::info($e->getMessage());
                Log::error($e);
                throw new InvalidArgumentException('Gagal menyimpan data tracking armada');
            }

            if ($shipmentPlan['is_transit']) {
                // UPDATE FLEET DATA OF TRANSIT
                $fleetData = [
                    'pickupId' => $value['id'],
                    'fleetName' => $fleetName,
                    'fleetDeparture' => $fleetDepartureDate,
                ];
                try {
                    $this->transitRepository->updateFleetDataByPickupRepo($fleetData);
                } catch (Exception $e) {
                    DB::rollback();
                    Log::info($e->getMessage());
                    Log::error($e);
                    throw new InvalidArgumentException('Gagal menyimpan data armada pada transit');
                }
            }
        }

        DB::commit();
        return $shipmentPlan;
    }

    /**
     * get history shipment plan driver
     */
    public function getHistoryShipmentService($data = [])
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
            $result = $this->shipmentPlanRepository->getHistoryShipmentRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }
}

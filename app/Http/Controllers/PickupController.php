<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Services\AddressService;
use App\Services\PickupService;
use App\Http\Controllers\BaseController;
use Exception;
use DB;

class PickupController extends BaseController
{
    protected $addressService;
    protected $pickupService;

    public function __construct(AddressService $addressService, PickupService $pickupService)
    {
        $this->addressService = $addressService;
        $this->pickupService = $pickupService;
    }

    /**
     * Create Pickup customer
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'userId',
            'fleetId',
            'promoId',
            'name',
            'phone',
            'senderId',
            'receiverId',
            'debtorId',
            'notes',
            'items',
            'origin',
            'destination_city',
            'destination_district',
            'picktime',
        ]);
        DB::beginTransaction();
        try {
            $result = $this->pickupService->createPickupService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function paginate(Request $request)
    {
        $data = $request->only([
            'perPage',
            'page',
            'name',
            'city',
            'id',
            'district',
            'village',
            'picktime',
            'sort',
            'number',
            'isDrop',
            'status'
        ]);
        try {
            $result = $this->pickupService->getAllPaginate($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get pickup order by pickup plan
     * admin only
     */
    public function getByPickupPlan(Request $request)
    {
        $data = $request->only([
            'perPage',
            'page',
            'name',
            'city',
            'id',
            'district',
            'village',
            'pickupPlanId',
            'sort'
        ]);
        try {
            $result = $this->pickupService->getPickupByPickupPlanService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get list pickup untuk customer
     */
    public function listPickupCustomer(Request $request)
    {
        $data = $request->only([
            'perPage',
            'page',
            'userId',
            'name',
            'city',
            'id',
            'district',
            'village',
            'picktime',
            'sort'
        ]);
        try {
            $result = $this->pickupService->getPickupPaginateByUserId($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get pickup order by pickup plan
     * driver only
     */
    public function getByPickupPlanDriver(Request $request)
    {
        $data = $request->only([
            'perPage',
            'page',
            'userId',
            'name',
            'city',
            'id',
            'district',
            'village',
            'street',
            'pickupPlanId',
            'sort'
        ]);
        try {
            $result = $this->pickupService->getPickupByPickupPlanDriverService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get total volume and kilo of pickup in pickup plan
     * driver only
     */
    public function getTotalVolumeAndKiloPickup(Request $request)
    {
        $data = $request->only([
            'pickupPlanId',
        ]);
        try {
            $result = $this->pickupService->getTotalVolumeAndKiloService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get detail pickup
     * driver only
     */
    public function getDetailPickup(Request $request)
    {
        $data = $request->only([
            'pickupId',
        ]);
        try {
            $result = $this->pickupService->getDetailPickup($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * create pickup admin.
     */
    public function createPickupAdmin(Request $request)
    {
        $data = $request->only([
            'userId',
            'items',
            'form',
            'customer',
            'branchId',
            'isDrop',
            'marketing',
            'cost'
        ]);
        DB::beginTransaction();
        try {
            $result = $this->pickupService->createPickupAdminService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * delete pickup
     */
    public function deletePickup(Request $request)
    {
        $data = $request->only([
            'userId',
            'pickupId',
        ]);
        DB::beginTransaction();
        try {
            $result = $this->pickupService->cancelPickupService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * get pickup order by shipment plan
     * admin only
     */
    public function getByShipmentPlan(Request $request)
    {
        $data = $request->only([
            'perPage',
            'page',
            'name',
            'province',
            'city',
            'id',
            'district',
            'village',
            'shipmentPlanId',
            'sort',
            'number'
        ]);
        try {
            $result = $this->pickupService->getPickupByShipmentPlanService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * edit pickup admin.
     */
    public function editPickupAdmin(Request $request)
    {
        $data = $request->only([
            'userId',
            'items',
            'form',
            'customer',
            'branchId',
            'isDrop',
            'cost'
        ]);
        DB::beginTransaction();
        try {
            $result = $this->pickupService->editPickupAdminService($data);
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * get printed data order
     * only admin
     */
    public function getPrintedData(Request $request)
    {
        $data = $request->only([
            'pickupNumber'
        ]);
        try {
            $result = $this->pickupService->getPrintedPickupOrderService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * check state order
     */
    public function checkState(Request $request)
    {
        $data = $request->only([
            'pickupId'
        ]);
        try {
            $result = $this->pickupService->getPickupStateService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }
}

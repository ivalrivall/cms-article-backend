<?php

namespace App\Http\Controllers;

// SERVICE
use App\Services\ItemService;

// OTHER
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use Exception;
use DB;

class ItemController extends BaseController
{
    protected $itemService;

    public function __construct(ItemService $itemService)
    {
        $this->itemService = $itemService;
    }

    /**
     * update item of pickup
     */
    public function update(Request $request)
    {
        $data = $request->only([
            'itemId',
            'name',
            'count',
            'serviceId',
            'volume',
            'weight',
            'unit',
            'routePriceId'
        ]);
        try {
            $result = $this->itemService->updateItemService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get item of pickup
     */
    public function getByPickup(Request $request)
    {
        $data = $request->only([
            'pickupId'
        ]);
        try {
            $result = $this->itemService->fetchItemByPickupService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * delete item of pickup
     */
    public function delete(Request $request)
    {
        $data = $request->only([
            'itemId'
        ]);
        try {
            $result = $this->itemService->deleteItemService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * add item of pickup for driver and admin
     */
    public function addPickupItem(Request $request)
    {
        $data = $request->only([
            'pickupId',
            'serviceId',
            'name',
            'weight',
            'volume',
            'count',
            'unit',
            'routePriceId'
        ]);
        try {
            $result = $this->itemService->addItemService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }
}

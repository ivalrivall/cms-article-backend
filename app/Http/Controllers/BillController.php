<?php

namespace App\Http\Controllers;

// OTHER
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;

// MODELS
use App\Services\BillService;
use App\Services\RouteService;
use App\Services\PromoService;

class BillController extends BaseController
{
    protected $billService;
    protected $routeService;
    protected $promoService;

    public function __construct(
        BillService $billService,
        RouteService $routeService,
        PromoService $promoService
    )
    {
        $this->billService = $billService;
        $this->routeService = $routeService;
        $this->promoService = $promoService;
    }

    /**
     * Calculate Price based on origin and destination
     *
     * @param Request $request
     */
    public function calculatePrice(Request $request)
    {
        $data = $request->only([
            'items',
            'origin',
            'destination',
            'fleetId',
            'promoId',
            'cost'
        ]);

        try {
            $route = $this->routeService->getByCityService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            return $this->sendError($e->getMessage());
        }

        if (empty($data['promoId'])) {
            try {
                $result = $this->billService->calculatePriceWithoutPromoService($data['items'], $route, $data['cost'], false);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                return $this->sendError($e->getMessage());
            }
        } else {
            try {
                $promo = $this->promoService->getPromoByIdService($data['promoId']);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                return $this->sendError($e->getMessage());
            }

            try {
                $result = $this->billService->calculatePriceService($data['items'], $route, $promo, $data['cost'], false);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                return $this->sendError($e->getMessage());
            }
        }


        return $this->sendResponse(null, $result);
    }

    /**
     * Calculate Price based on origin and destination
     *
     * @param Request $request
     */
    public function calculatePriceFinal(Request $request)
    {
        $data = $request->only([
            'items',
            'origin',
            'destination_district',
            'destination_city',
            'fleetId',
            'promoId',
            'cost'
        ]);

        try {
            $route = $this->routeService->getByFleetOriginDestinationService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return $this->sendError($e->getMessage());
        }

        if (empty($data['promoId'])) {
            try {
                $result = $this->billService->calculatePriceWithoutPromoService($data['items'], $route, $data['cost'], false);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                return $this->sendError($e->getMessage());
            }
        } else {
            try {
                $promo = $this->promoService->getPromoByIdService($data['promoId']);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                return $this->sendError($e->getMessage());
            }

            try {
                $result = $this->billService->calculatePriceService($data['items'], $route, $promo, $data['cost'], false);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                return $this->sendError($e->getMessage());
            }
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * get cost detail
     */
    public function getCostDetailByPickup(Request $request)
    {
        $data = $request->only([
            'pickupId'
        ]);

        try {
            $result = $this->billService->getCostByPickupService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }
}

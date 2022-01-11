<?php

namespace App\Http\Controllers;

// OTHER
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;

// SERVICE
use App\Services\FinanceService;
use App\Services\ItemService;
use App\Services\BillService;
use App\Services\RouteService;
use App\Services\PickupService;

class FinanceController extends BaseController
{
    protected $financeService;
    protected $itemService;
    protected $billService;
    protected $routeService;
    protected $pickupService;

    public function __construct(
        FinanceService $financeService,
        ItemService $itemService,
        BillService $billService,
        RouteService $routeService,
        PickupService $pickupService
    )
    {
        $this->financeService = $financeService;
        $this->itemService = $itemService;
        $this->billService = $billService;
        $this->routeService = $routeService;
        $this->pickupService = $pickupService;
    }

    /**
     * get finance pickup paginate
     *
     * @param Request $request
     */
    public function getFinancePickupPaginate(Request $request)
    {
        $data = $request->only([
            'perPage',
            'page',
            'sort',
            'number',
            'name',
            'receiver',
            'debtor',
            'paymentMethod',
            'createdAt',
            'dateFrom',
            'dateTo',
            'branchName',
            'dueDateFrom',
            'dueDateTo'
        ]);

        try {
            $result = $this->financeService->getFinancePickupService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * update cost on finance
     */
    public function updateCost(Request $request)
    {
        $data = $request->only([
            'cost',
            'userId',
            'extraCosts'
        ]);

        try {
            $result = $this->financeService->updateFinanceCostService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get invoice user
     */
    public function getInvoice(Request $request)
    {
        $data = $request->only([
            'number'
        ]);

        try {
            $result = $this->financeService->getInvoiceService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * add item on finance
     */
    public function addItem(Request $request)
    {
        $data = $request->only([
            'pickupId',
            'serviceId',
            'name',
            'weight',
            'volume',
            'count',
            'unit',
            'routePriceId',
            'cost'
        ]);

        DB::beginTransaction();

        // ADD ITEM FINANCE
        try {
            $result = $this->itemService->addItemFinanceService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        // GET ITEMS
        try {
            $items = $this->itemService->fetchItemByPickupService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        $items = collect($items)->values()->toArray();
        // END GET ITEMS

        // GET ROUTE
        try {
            $routePrice = $this->routeService->getRoutePriceByIdService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        try {
            $route = $this->routeService->getRouteService($routePrice->route_id);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        // END GET ROUTE

        // GET PROMO AND CALCULATE
        try {
            $pickup = $this->pickupService->getPickupByIdService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        $cost = [
            'taxRate' => $data['cost']['tax_rate'],
            'insuranceAmount' => $data['cost']['insurance_amount']
        ];

        if ($pickup->promo_id !== null) {
            try {
                $promo = $this->promoService->getPromoByIdService($pickup->promo_id);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
            try {
                $result = $this->billService->calculatePriceService($items, $route, $promo, $cost, true);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
        } else {
            // DB::rollback();
            // return $this->sendResponse($items);
            try {
                $result = $this->billService->calculatePriceWithoutPromoService($items, $route, $cost, true);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
        }
        // END GET PROMO AND CALCULATE

        // SAVE OR UPDATE COST
        $cost = [
            'pickupId' => $data['pickupId'],
            'amount' => $result->total_price,
            'clearAmount' => $result->total_clear_price,
            'discount' => $result->total_discount,
            'service' => $result->total_service,
            'amountWithService' => $result->total_price_with_service,
            'taxRate' => $result->total_tax_rate,
            'taxAmount' => $result->total_tax_amount,
            'insuranceAmount' => $result->total_insurance_amount,
            'amountWithTax' => $result->total_price_with_tax,
            'amountWithInsurance' => $result->total_price_with_insurance,
            'amountWithTaxInsurance' => $result->total_price_with_tax_insurance,
        ];
        try {
            $this->financeService->saveOrUpdateCostService($cost);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
        }
        // END SAVE OR UPDATE COST

        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * edit item on finance
     */
    public function editItem(Request $request)
    {
        $data = $request->only([
            'itemId',
            'name',
            'count',
            'serviceId',
            'volume',
            'weight',
            'unit',
            'routePriceId',
            'pickupId',
            'cost'
        ]);
        // EDIT ITEM
        try {
            $this->itemService->updateItemService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        // GET ITEMS
        try {
            $items = $this->itemService->fetchItemByPickupService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        $items = collect($items)->values()->toArray();
        // END GET ITEMS

        // GET ROUTE
        try {
            $routePrice = $this->routeService->getRoutePriceByIdService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        try {
            $route = $this->routeService->getRouteService($routePrice->route_id);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        // END GET ROUTE

        // GET PROMO AND CALCULATE
        try {
            $pickup = $this->pickupService->getPickupByIdService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        $cost = [
            'taxRate' => $data['cost']['tax_rate'],
            'insuranceAmount' => $data['cost']['insurance_amount']
        ];

        if ($pickup->promo_id !== null) {
            try {
                $promo = $this->promoService->getPromoByIdService($pickup->promo_id);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
            try {
                $result = $this->billService->calculatePriceService($items, $route, $promo, $cost, true);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
        } else {
            try {
                $result = $this->billService->calculatePriceWithoutPromoService($items, $route, $cost, true);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
        }
        // END GET PROMO AND CALCULATE

        // SAVE OR UPDATE COST
        $cost = [
            'pickupId' => $data['pickupId'],
            'amount' => $result->total_price,
            'clearAmount' => $result->total_clear_price,
            'discount' => $result->total_discount,
            'service' => $result->total_service,
            'amountWithService' => $result->total_price_with_service,
            'taxRate' => $result->total_tax_rate,
            'taxAmount' => $result->total_tax_amount,
            'insuranceAmount' => $result->total_insurance_amount,
            'amountWithTax' => $result->total_price_with_tax,
            'amountWithInsurance' => $result->total_price_with_insurance,
            'amountWithTaxInsurance' => $result->total_price_with_tax_insurance,
        ];
        try {
            $this->financeService->saveOrUpdateCostService($cost);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
        }
        // END SAVE OR UPDATE COST
        DB::commit();
        return $this->sendResponse(null, $result);
    }

    /**
     * delete item on finance
     */
    public function deleteItem(Request $request)
    {
        $data = $request->only([
            'itemId',
            'routePriceId',
            'pickupId',
            'cost'
        ]);

        // DELETE ITEM
        try {
            $deletedItem = $this->itemService->deleteItemService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        // GET ITEMS
        try {
            $items = $this->itemService->fetchItemByPickupService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        $items = collect($items)->values()->toArray();
        // END GET ITEMS

        // GET ROUTE
        try {
            $routePrice = $this->routeService->getRoutePriceByIdService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        try {
            $route = $this->routeService->getRouteService($routePrice->route_id);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        // END GET ROUTE

        // GET PROMO AND CALCULATE
        try {
            $pickup = $this->pickupService->getPickupByIdService($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            return $this->sendError($e->getMessage());
        }

        $cost = [
            'taxRate' => $data['cost']['tax_rate'],
            'insuranceAmount' => $data['cost']['insurance_amount']
        ];

        if ($pickup->promo_id !== null) {
            try {
                $promo = $this->promoService->getPromoByIdService($pickup->promo_id);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
            try {
                $result = $this->billService->calculatePriceService($items, $route, $promo, $cost, true);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
        } else {
            try {
                $result = $this->billService->calculatePriceWithoutPromoService($items, $route, $cost, true);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                return $this->sendError($e->getMessage());
            }
        }
        // END GET PROMO AND CALCULATE

        // SAVE OR UPDATE COST
        $cost = [
            'pickupId' => $data['pickupId'],
            'amount' => $result->total_price,
            'clearAmount' => $result->total_clear_price,
            'discount' => $result->total_discount,
            'service' => $result->total_service,
            'amountWithService' => $result->total_price_with_service,
            'taxRate' => $result->total_tax_rate,
            'taxAmount' => $result->total_tax_amount,
            'insuranceAmount' => $result->total_insurance_amount,
            'amountWithTax' => $result->total_price_with_tax,
            'amountWithInsurance' => $result->total_price_with_insurance,
            'amountWithTaxInsurance' => $result->total_price_with_tax_insurance,
        ];
        try {
            $costResult = $this->financeService->saveOrUpdateCostService($cost);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
        }
        // END SAVE OR UPDATE COST
        DB::commit();
        $pickupData = $pickup->with(['receiver','debtor','sender','proofOfPickup'])->first();
        return $this->sendResponse(null, [
            'cost' => $costResult,
            'deletedItem' => $deletedItem,
            'pickup' => $pickupData
        ]);
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
            $result = $this->financeService->getPrintedFinancePickupService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;

use App\Http\Controllers\BaseController;

use App\Services\PickupService;
use App\Services\VehicleService;
use App\Services\UserService;
use App\Services\RouteService;
use App\Services\BillService;
use App\Services\ItemService;

class DashboardController extends BaseController
{
    protected $pickupService;
    protected $vehicleService;
    protected $userService;
    protected $routeService;
    protected $billService;
    protected $itemService;

    public function __construct(
        PickupService $pickupService,
        VehicleService $vehicleService,
        UserService $userService,
        RouteService $routeService,
        BillService $billService,
        ItemService $itemService
    )
    {
        $this->pickupService = $pickupService;
        $this->vehicleService = $vehicleService;
        $this->userService = $userService;
        $this->routeService = $routeService;
        $this->billService = $billService;
        $this->itemService = $itemService;
    }

    /**
     * get omset dashboard.
     * - [x] total amount(tanpa asuransi dan pajak) + total extra cost dan di filter berdasarkan rentang tanggal dan cabang
     * - Tanggal (terhitung dari tanggal order pickup atau drop , cuma untuk pickup order amount akan terupdate ketika order sudah di POP)
     * - cabang (bukan cabang user yang login tapi cabang yang di filter)
     * - untuk omset pickup order akan terupdate di dashboard ketika POP applied
     * - untuk omset drop order akan terupdate di dashboard ketika Drop order terbuat

     * @param Request $request
     */
    public function getOmset(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        /**
         * get total omset
         * keseluruhan total tagihan ditambah biaya extra
         */
        try {
            $omset = $this->billService->getTotalCostAmountService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $omset);
    }

    /**
     * get load dashboard.
     * - [x] Total muatan adalah dalam satuan kilogram di tampilkanya dari keseluruan berat semua barang yang *dikirim* *(bukan yang sudah di kirim)* muatau sudah di tampilkan di dashboard ketika ada orderan masuk.
     * - Drop order dengan status request/applied
     * - Pickup order dengan status request/applied
     * - untuk status cancel tidak trhitung di dashboard muatan
     * @param Request $request
     */
    public function getLoad(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        /**
         * get total loads / total muatan
         *  */
        try {
            $load = $this->itemService->getTotalLoadService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $load);
    }

    /**
     * get margin dashboard.
     * total tagihan - total biaya extra (saat ini rumusnya masih total tagihan + total biaya extra) seharusnya sesuai rumus margin
     * @param Request $request
     */
    public function getMargin(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        /**
         * get total amount
         * keseluruhan total tagihan dikurangi biaya extra
         */
        try {
            $margin = $this->billService->getTotalMarginService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        // /**
        //  * get total extra service
        //  */
        // try {
        //     $extraCost = $this->billService->getTotalExtraCostService($data);
        // } catch (Exception $e) {
        //     return $this->sendError($e->getMessage());
        // }

        // $margin = $amount - $extraCost;

        return $this->sendResponse(null, $margin);
    }

    /**
     * get created order dashboard.
     * semua orderan yang terbuat akan di hitung baik status request, applied, atau cancel
     * @param Request $request
     */
    public function getOrderCreated(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        /**
         * get total order created
         *  */
        try {
            $result = $this->pickupService->getTotalOrderCreatedService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * get payment method dashboard.
     * Menampilkan total dari masing2 metode pembayaran
     * untuk orderan dari app customer itu masuknya ke pembayaran tempo
     * @param Request $request
     */
    public function getPaymentMethod(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        /**
         * get payment method
         *  */
        try {
            $paymentMethod = $this->billService->getDashboardPaymentMethodService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $paymentMethod);
    }

    /**
     * get payment status dashboard.
     * Menampilkan jumlah dari masing2 status pembayarana seperti lunas, hutang, belum ada status
     * @param Request $request
     */
    public function getPaymentStatus(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        try {
            $result = $this->billService->getDashboardPaymentStatusService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * get canceled order dashboard.
     * semua orderan yang masuk dengan status cancel (kata gagal di ganti dengan batal)
     * @param Request $request
     */
    public function getOrderCancelled(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        try {
            $result = $this->pickupService->getTotalOrderCancelledService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * get fleet performance dashboard.
     * menampilkan jumlah dari total order di masing2 jenis armada. data yang di tampilkan di dashboard terhitung setelah order dibuat POP
     * @param Request $request
     */
    public function getFleetPerformance(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        try {
            $result = $this->pickupService->getFleetPerformanceService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * get order per month dashboard.
     * Orderan yang masuk baik yang batal atau sukses
     * @param Request $request
     */
    public function getOrderPerMonth(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        try {
            $result = $this->pickupService->getOrderPerMonthService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * mendapatkan total tagihan yang tertunggak/berjalan.
     * Total tagihan yang belum lunas dari orderan yang masuk
     * @param Request $request
     */
    public function getBillPayable(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        try {
            $result = $this->billService->getBillPayableService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }

    /**
     * mendapatkan total tagihan yang lunas.
     * Total tagihan order yang sudah lunas, Jika order di cancel nominal tagihan lunas tidak terhitung di dashboard
     * @param Request $request
     */
    public function getBillPaidOff(Request $request)
    {
        $data = $request->only([
            'filter',
        ]);

        try {
            $result = $this->billService->getBillPaidOffService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse(null, $result);
    }
}

<?php

namespace App\Http\Controllers;

// OTHER
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use Exception;

// SERVICE
use App\Services\UnitService;

class UnitController extends BaseController
{
    protected $unitService;

    public function __construct(UnitService $unitService)
    {
        $this->unitService = $unitService;
    }

    /**
     * get all units
     */
    public function index()
    {
        try {
            $result = $this->unitService->getAll();
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get service paginate.
     *
     */
    public function getPaginate(Request $request)
    {
        $data = $request->only([
            'perPage',
            'page',
            'name',
            'sort'
        ]);
        try {
            $result = $this->unitService->paginateUnitService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * create unit.
     *
     */
    public function create(Request $request)
    {
        $data = $request->only([
            'name'
        ]);
        try {
            $result = $this->unitService->createUnitService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * update unit.
     *
     */
    public function update(Request $request)
    {
        $data = $request->only([
            'name',
            'id'
        ]);
        try {
            $result = $this->unitService->updateUnitService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * delete unit.
     *
     */
    public function delete(Request $request)
    {
        $data = $request->only([
            'unitId'
        ]);
        try {
            $result = $this->unitService->deleteUnitService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }
}

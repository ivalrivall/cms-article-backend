<?php
namespace App\Services;

use App\Repositories\UnitRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class UnitService {

    protected $unitRepository;

    public function __construct(UnitRepository $unitRepository)
    {
        $this->unitRepository = $unitRepository;
    }

    /**
     * Get all unit.
     *
     * @return String
     */
    public function getAll()
    {
        try {
            $result = $this->unitRepository->getAll();
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapat data satuan');
        }
        return $result;
    }

    /**
     * get paginate
     */
    public function paginateUnitService($data = [])
    {
        try {
            $result = $this->unitRepository->getPaginateRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * create unit
     */
    public function createUnitService($data = [])
    {
        $validator = Validator::make($data, [
            'name' => 'bail|required',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->unitRepository->createUnitRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * update unit
     */
    public function updateUnitService($data = [])
    {
        $validator = Validator::make($data, [
            'name' => 'bail|required',
            'id' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->unitRepository->updateUnitRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * delete unit
     */
    public function deleteUnitService($data = [])
    {
        $validator = Validator::make($data, [
            'unitId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->unitRepository->deleteUnitRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }
}

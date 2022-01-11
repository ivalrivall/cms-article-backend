<?php
namespace App\Services;

use App\Repositories\ItemRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ItemService {

    protected $itemRepository;

    public function __construct(ItemRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    /**
     * update item service
     *
     * @param array $data
     * @return String
     */
    public function updateItemService($data = [])
    {
        $validator = Validator::make($data, [
            'itemId' => 'bail|required',
            'name' => 'bail|required',
            'unit' => 'bail|required',
            'count' => 'bail|required',
            'serviceId' => 'bail|present',
            'weight' => 'bail|required',
            'volume' => 'bail|required',
            'routePriceId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->itemRepository->updateItemRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * get item by pickup service
     *
     * @param array $data
     * @return String
     */
    public function fetchItemByPickupService($data = [])
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->itemRepository->fetchItemByPickupRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * mendapatkan total muatan dalam kg berdasarkan rentang filter dan cabang di dashboard
     */
    public function getTotalLoadService($data = [])
    {
        $validator = Validator::make($data['filter'], [
            'startDate' => 'bail|required',
            'endDate'   => 'bail|required',
            'branch'    => 'bail|required|array'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->itemRepository->getTotalLoadKilogramRepo($data['filter']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan total muatan');
        }

        return $result;
    }

    /**
     * delete item service
     *
     * @param array $data
     * @return String
     */
    public function deleteItemService($data = [])
    {
        $validator = Validator::make($data, [
            'itemId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->itemRepository->delete($data['itemId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * add item service
     *
     * @param array $data
     * @return String
     */
    public function addItemService($data = [])
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required',
            'serviceId' => 'bail|present',
            'name' => 'bail|required',
            'weight' => 'bail|required',
            'volume' => 'bail|required',
            'count' => 'bail|required',
            'unit' => 'bail|required',
            'routePriceId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->itemRepository->saveItemRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * add item finance
     */
    public function addItemFinanceService($data = [])
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required',
            'serviceId' => 'bail|present',
            'name' => 'bail|required',
            'weight' => 'bail|required',
            'volume' => 'bail|required',
            'count' => 'bail|required',
            'unit' => 'bail|required',
            'routePriceId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->itemRepository->saveItemFinanceRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }
}

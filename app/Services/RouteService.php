<?php
namespace App\Services;

use App\Models\User;
use App\Repositories\RouteRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class RouteService {

    protected $routeRepository;

    public function __construct(RouteRepository $routeRepository)
    {
        $this->routeRepository = $routeRepository;
    }

    /**
     * Get route by fleetId, origin, destination.
     *
     * @param Array $data
     * @return mixed
     */
    public function getByFleetOriginDestinationService($data = [])
    {
        $validator = Validator::make($data, [
            'origin'                    => 'bail|required|max:50',
            'destination_city'          => 'bail|required|max:50',
            'destination_district'     => 'bail|required|max:50',
            'fleetId'                   => 'bail|required|integer'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->routeRepository->getRouteRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        if (!$result) {
            throw new InvalidArgumentException('Mohon maaf, untuk saat ini kota tujuan yang Anda mau belum masuk kedalam jangkauan kami');
        }

        return $result;
    }

    /**
     * Get route by city of destination
     *
     * @param array $data
     * @return Route
     */
    public function getByCityService($data)
    {
        $validator = Validator::make($data, [
            'origin'                    => 'bail|required|max:50',
            'destination'               => 'bail|required|max:50',
            'fleetId'                   => 'bail|required|integer'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->routeRepository->getRouteByCityRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        if (!$result) {
            throw new InvalidArgumentException('Mohon maaf, untuk saat ini kota tujuan yang Anda mau belum masuk kedalam jangkauan kami');
        }

        return $result;
    }

    /**
     * Get all routes pagination
     */
    public function getAllPaginateService($data)
    {
        try {
            $result = $this->routeRepository->getAllPaginateRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * Get route service by city
     *
     * @param array $data
     */
    // public function getRouteByCityService($data)
    // {
    //     try {
    //         $result = $this->routeRepository->getAllPaginate($data);
    //     } catch (Exception $e) {
    //         Log::info($e->getMessage());
    //         throw new InvalidArgumentException($e->getMessage());
    //     }
    //     return $result;
    // }

    /**
     * Get route destination island
     *
     * @param array $data
     */
    public function getDestinationIslandService()
    {
        try {
            $result = $this->routeRepository->getDestinationIslandRepo();
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * Create route service
     *
     * @param array $data
     */
    public function createRouteService($data = [])
    {
        $validator = Validator::make($data, [
            'origin' => 'bail|required|max:50',
            'destinationCity' => 'bail|required|max:50',
            'destinationDistrict' => 'bail|required|max:50',
            'destinationIsland' => 'bail|required|max:50',
            'fleet' => 'bail|required',
            'prices' => 'bail|required|array',
            'minWeight' => 'bail|required|min:0',
            'minPrice' => 'bail|required|numeric|min:0',
            'estimate' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        foreach ($data['prices'] as $key => $value) {
            $validator = Validator::make($value, [
                'type' => 'bail|required',
                'price' => 'bail|required|min:0|numeric',
                'with_minimum' => 'bail|required|boolean',
            ]);

            if ($validator->fails()) {
                throw new InvalidArgumentException($validator->errors()->first());
            }
        }

        DB::beginTransaction();
        try {
            $result = $this->routeRepository->createRouteRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * edit route service
     *
     * @param array $data
     */
    public function editRouteService($data = [])
    {
        $validator = Validator::make($data, [
            'origin' => [
                'bail','required','max:50',
            ],
            'destinationCity' => [
                'bail','required','max:50',
            ],
            'destinationDistrict' => [
                'bail','required','max:50',
            ],
            'destinationIsland' => 'bail|required|max:50',
            'fleet' => 'bail|required',
            'minWeight' => 'bail|required|min:0|numeric',
            'estimate' => 'bail|required|min:1|numeric',
            'minPrice' => 'bail|required|min:0|numeric'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->routeRepository->editRouteRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * delete route service
     *
     * @param array $data
     */
    public function deleteRouteService($data = [])
    {
        $validator = Validator::make($data, [
            'routeId' => 'bail|required|max:50',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->routeRepository->deleteRouteRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * import route data
     */
    public function importRouteService($request)
    {
        $validator = Validator::make($request->all(), [
            'route' => 'required|max:5000|mimes:xlsx',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $result = $this->routeRepository->importRouteRepo($request);
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * get total route service
     */
    public function getTotalRouteService()
    {
        try {
            $result = $this->routeRepository->getTotalRouteRepo();
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }

    /**
     * add price route service
     */
    public function addPriceRouteService($data = [])
    {
        $validator = Validator::make($data, [
            'routeId' => 'required|bail',
            'price' => 'required|bail|min:0|numeric',
            'type' => 'required|bail',
            'withMinimum' => 'required|bail|boolean',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->routeRepository->addPriceRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * delete route price service
     */
    public function deleteRoutePriceService($id, $userId)
    {
        DB::beginTransaction();
        try {
            $result = $this->routeRepository->deleteRoutePriceRepo($id, $userId);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * get route detail
     */
    public function getRouteService($id)
    {
        DB::beginTransaction();
        try {
            $result = $this->routeRepository->getReuteDetailRepo($id);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * get price route detail
     */
    public function getRoutePriceService($data = [])
    {
        $validator = Validator::make($data, [
            'routeId' => 'required|bail',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->routeRepository->getRoutePriceRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * import route data price
     */
    public function importRoutePriceService($request)
    {
        $validator = Validator::make($request->all(), [
            'route_price' => 'required|file|max:5000|mimes:xlsx',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $result = $this->routeRepository->importRoutePriceRepo($request);
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }

    /**
     * check price
     */
    public function checkPriceService($data = [])
    {
        $validator = Validator::make($data, [
            'origin'                    => 'bail|required|string|max:100',
            'destination_city'          => 'bail|required|string|max:100',
            'destination_district'     => 'bail|required|string|max:100',
            'fleetId'                   => 'bail|required|integer'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->routeRepository->checkPriceRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        if (!$result) {
            throw new InvalidArgumentException('Mohon maaf, untuk saat ini kota tujuan yang Anda mau belum masuk kedalam jangkauan kami');
        }

        return $result;
    }

    /**
     * get route price
     */
    public function getRoutePriceByIdService($data = [])
    {
        $validator = Validator::make($data, [
            'routePriceId' => 'required|bail',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->routeRepository->getRoutePriceByIdRepo($data['routePriceId']);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $result;
    }
}

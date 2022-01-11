<?php
namespace App\Services;

use App\Repositories\TrackingRepository;
use App\Repositories\RouteRepository;
use App\Repositories\ItemRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class TrackingService {

    protected $trackingRepository;
    protected $routeRepository;
    protected $itemRepository;

    public function __construct(
        TrackingRepository $trackingRepository,
        RouteRepository $routeRepository,
        ItemRepository $itemRepository
    )
    {
        $this->trackingRepository = $trackingRepository;
        $this->routeRepository = $routeRepository;
        $this->itemRepository = $itemRepository;
    }

    /**
     * Get tracking by pickup
     *
     * @param array $data
     * @return String
     */
    public function getTrackingByPickupService($data)
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required|string',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->trackingRepository->getTrackingByPickupRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Gagal mendapat data tracking');
        }
        return $result;
    }

    /**
     * search tracking by pickup number
     * @param array $data
     */
    public function searchTrackingService($data = [])
    {
        $validator = Validator::make($data, [
            'query' => 'bail|required|string'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $pickup = $this->trackingRepository->getTrackingByPickupNumberRepo($data['query']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapat data tracking ('. $e->getMessage(). ')');
        }

        try {
            $payload = [
                'fleetId' => $pickup->fleet_id,
                'origin' => $pickup->sender->city,
                'destination_district' => $pickup->receiver->district,
                'destination_city' => $pickup->receiver->city
            ];
            $route = $this->routeRepository->getRouteRepo($payload);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Gagal mendapat data tracking');
        }

        try {
            $items = $this->itemRepository->getTotalLoadKilogramByPickupRepo($pickup->id);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Gagal mendapat data tracking');
        }

        $result = [
            'pickup' => $pickup,
            'estimate' => $route->estimate,
            'totalWeight' => $items
        ];

        return $result;
    }

    /**
     * record tracking by pickup
     *
     * @param array $data
     * @return String
     */
    public function recordTrackingByPickupService($data)
    {
        $validator = Validator::make($data, [
            'pickupId' => 'bail|required',
            'docs' => 'bail|required',
            'status' => 'bail|required',
            'notes' => 'bail|required',
            'picture' => 'bail',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $result = $this->trackingRepository->recordTrackingByPickupRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal menyimpan data tracking');
        }
        DB::commit();
        return $result;
    }

    /**
     * upload tracking picture
     */
    public function uploadTrackingPictureService($request)
    {
        $validator = Validator::make($request->all(), [
            'picture' => 'required|file|max:1024|mimes:jpg,png,jpeg',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $filename = $request->file('picture')->getClientOriginalName();

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if (!in_array($extension, ['jpeg','jpg','png','PNG','JPEG','JPG'])) {
            throw new InvalidArgumentException("Ekstensi $extension tidak diperbolehkan");
        }

        DB::beginTransaction();

        try {
            $result = $this->trackingRepository->uploadTrackingPicture($request);
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
     * get picture
     */
    public function getPictureService($data = [])
    {
        $validator = Validator::make($data, [
            'docs' => 'required|string',
            'pickupId' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $result = $this->trackingRepository->getTrackingPictureRepo($data);
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
     * search resi
     */
    public function searchResiService($data)
    {
        $validator = Validator::make($data, [
            'number' => 'required|string'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $result = $this->trackingRepository->searchResiRepo($data['number']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }

        return $result;
    }
}

<?php
namespace App\Services;

use App\Repositories\FleetRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class FleetService {

    protected $fleetRepository;

    public function __construct(FleetRepository $fleetRepository)
    {
        $this->fleetRepository = $fleetRepository;
    }

    /**
     * Get all fleets.
     *
     * @return String
     */
    public function getAll()
    {
        try {
            $fleet = $this->fleetRepository->getAll();
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Unable to get fleet data');
        }
        return $fleet;
    }
}

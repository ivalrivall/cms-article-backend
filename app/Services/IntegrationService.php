<?php
namespace App\Services;

use App\Repositories\IntegrationRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class IntegrationService {

    protected $integrationRepository;

    public function __construct(IntegrationRepository $integrationRepository)
    {
        $this->integrationRepository = $integrationRepository;
    }

    /**
     * integrate service
     */
    public function integrateService($data = [])
    {
        $validator = Validator::make($data, [
            'status' => 'bail|required',
            'integrationData' => 'bail|required',
            'service' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->integrationRepository->integrateRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            throw new InvalidArgumentException('Gagal mengintegrasikan');
        }
        DB::commit();
        return $result;
    }
}

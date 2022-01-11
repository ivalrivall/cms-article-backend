<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IntegrationService;
use App\Http\Controllers\BaseController;
use Exception;

class IntegrationController extends BaseController
{
    protected $integrationService;
    public function __construct(IntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    /**
     * integrate papandayan
     */
    public function integratePapandayan(Request $request)
    {
        $data = $request->only([
            'status',
            'integrationData',
            'service'
        ]);
        try {
            $result = $this->integrationService->integrateService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }
}

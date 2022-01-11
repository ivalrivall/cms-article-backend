<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AccurateService;
use App\Services\IntegrationService;
use App\Http\Controllers\BaseController;
use Exception;

class AccurateController extends BaseController
{
    protected $accurateService;
    protected $integrationService;
    public function __construct(AccurateService $accurateService, IntegrationService $integrationService)
    {
        $this->accurateService = $accurateService;
        $this->integrationService = $integrationService;
    }

    /**
     * integrate accurate
     */
    public function integrateAccurate()
    {
        try {
            $result = $this->accurateService->integrateAccurateService();
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * get integraion credential
     */
    public function getIntegrationCredential()
    {
        try {
            $result = $this->accurateService->getIntegrationCredentialService();
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, $result);
    }

    /**
     * authorization token accurate
     */
    public function authorizationToken(Request $request)
    {
        $data = $request->only([
            'code',
            'redirectUri'
        ]);
        // authorization token
        try {
            $auth = $this->accurateService->authorizationTokenService($data);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        // save access token
        try {
            $payload = [
                'status' => true,
                'service' => 'accurate',
                'integrationData' => $auth
            ];
            $this->integrationService->integrateService($payload);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse(null, true);
    }
}

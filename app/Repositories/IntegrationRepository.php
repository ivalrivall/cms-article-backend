<?php

namespace App\Repositories;

use App\Models\Integration;
use Carbon\Carbon;
use InvalidArgumentException;

class IntegrationRepository
{
    protected $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * integration
     *
     * @param array $data
     */
    public function integrateRepo($data = [])
    {
        $data = $this->integration->updateOrCreate(
            ['service' => $data['service']],
            ['integration_data' => $data['integrationData'], 'status' => $data['status']]
        );
        return $data;
    }
}

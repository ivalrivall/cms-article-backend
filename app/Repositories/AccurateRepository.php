<?php

namespace App\Repositories;

use App\Models\Integration;
use Carbon\Carbon;
use InvalidArgumentException;

class AccurateRepository
{
    protected $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * create integration
     *
     * @param array $data
     */
    public function integrateAccurateRepo($data)
    {
        $data = $this->base->where('name', 'ilike', '%'.$data['value'].'%')->get();
        return $data;
    }
}

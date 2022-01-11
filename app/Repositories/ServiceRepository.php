<?php

namespace App\Repositories;

use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ServiceRepository
{
    protected $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * Get all Service
     *
     * @return Service
     */
    public function getAll()
    {
        if (Cache::has('services')) {
            $services = Cache::get('services');
        } else {
            $services = $this->service->get();
            Cache::add('services', $services, env('CACHE_EXP'));
        }
        return $services;
    }

    /**
     * get paginate service
     */
    public function getPaginateRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $name = $data['name'];
        $price = $data['price'];
        $sort = $data['sort'];

        $result = $this->service;
        Cache::put('services', $result->get(), env('CACHE_EXP'));

        if (empty($perPage)) {
            $perPage = 10;
        }

        if (!empty($sort['field'])) {
            $order = $sort['order'];
            if ($order == 'ascend') {
                $order = 'asc';
            } else if ($order == 'descend') {
                $order = 'desc';
            } else {
                $order = 'desc';
            }
            switch ($sort['field']) {
                case 'id':
                    $result = $result->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'name':
                    $result = $result->sortable([
                        'name' => $order
                    ]);
                    break;
                case 'price':
                    $result = $result->sortable([
                        'price' => $order
                    ]);
                    break;
                case 'created_at':
                    $result = $result->sortable([
                        'created_at' => $order
                    ]);
                    break;
                default:
                    $result = $result->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $result = $result->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($name)) {
            $result = $result->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($price)) {
            $result = $result->where('price', 'ilike', '%'.$price.'%');
        }

        $result = $result->paginate($perPage);

        return $result;
    }

    /**
     * create service
     */
    public function createServiceRepo($data = [])
    {
        $service = new $this->service;
        $service->name = $data['name'];
        $service->price = $data['price'];
        $service->save();
        Cache::put('service:'.$service['id'], $service, env('CACHE_EXP'));
        Cache::forget('services');
        return $service;
    }

    /**
     * update service
     */
    public function updateServiceRepo($data = [])
    {
        $service = $this->service->find($data['id']);
        $service->name = $data['name'];
        $service->price = $data['price'];
        $service->save();
        Cache::put('service:'.$service['id'], $service, env('CACHE_EXP'));
        Cache::forget('services');
        return $service;
    }

    /**
     * delete service
     */
    public function deleteServiceRepo($data = [])
    {
        $service = $this->service->find($data['serviceId']);
        $service->delete();
        Cache::forget('service:'.$data['serviceId']);
        Cache::forget('services');
        return $service;
    }
}

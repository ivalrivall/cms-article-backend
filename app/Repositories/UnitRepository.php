<?php

namespace App\Repositories;

use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class UnitRepository
{
    protected $unit;

    public function __construct(Unit $unit)
    {
        $this->unit = $unit;
    }

    /**
     * Get all unit
     *
     * @return Unit
     */
    public function getAll()
    {
        if (Cache::has('units')) {
            $units = Cache::get('units');
        } else {
            $units = $this->unit->get()->pluck('name');
            Cache::put('units', $units, env('CACHE_EXP'));
        }
        return $units;
    }

    /**
     * get paginate service
     */
    public function getPaginateRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $name = $data['name'];
        $sort = $data['sort'];

        $result = $this->unit;
        Cache::put('units', $result->get()->pluck('name'), env('CACHE_EXP'));

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

        $result = $result->paginate($perPage);

        return $result;
    }

    /**
     * create unit
     */
    public function createUnitRepo($data = [])
    {
        $unit = new $this->unit;
        $unit->name = $data['name'];
        $unit->save();
        Cache::put('unit:'.$unit['id'], $unit, env('CACHE_EXP'));
        Cache::forget('units');
        return $unit;
    }

    /**
     * update unit
     */
    public function updateUnitRepo($data = [])
    {
        $unit = $this->unit->find($data['id']);
        $unit->name = $data['name'];
        $unit->save();
        Cache::put('unit:'.$unit['id'], $unit, env('CACHE_EXP'));
        Cache::forget('units');
        return $unit;
    }

    /**
     * delete unit
     */
    public function deleteUnitRepo($data = [])
    {
        $unit = $this->unit->find($data['unitId']);
        $unit->delete();
        Cache::forget('unit:'.$data['unitId']);
        Cache::forget('units');
        return $unit;
    }
}

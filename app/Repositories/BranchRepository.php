<?php

namespace App\Repositories;

use App\Models\Branch;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Facades\Cache;

class BranchRepository
{
    protected $branch;

    public function __construct(Branch $branch)
    {
        $this->branch = $branch;
    }

    /**
     * Get All Branch
     *
     * @return Branch
     */
    public function getAllBranchRepo()
    {
        if (Cache::has('branches')) {
            $branches = Cache::get('branches');
        } else {
            $branches = $this->branch->select('name', 'id')->get();
            Cache::put('branches', $branches, env('CACHE_EXP'));
        }
        return $branches;
    }

    /**
     * Get all branch paginate
     *
     * @param array $data
     * @return mixed
     */
    public function getAllPaginateRepo($data = [])
    {
        $sort = $data['sort'];
        $perPage = $data['perPage'];

        $name = $data['name'];
        $id = $data['id'];
        $province = $data['province'];
        $city = $data['city'];
        $district = $data['district'];

        $branch = $this->branch;
        Cache::put('branches', $branch->select('name', 'id')->get(), env('CACHE_EXP'));

        if (empty($perPage)) {
            $perPage = 15;
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
                case 'name':
                    $branch = $branch->sortable([
                        'name' => $order
                    ]);
                    break;
                case 'province':
                    $branch = $branch->sortable([
                        'province' => $order
                    ]);
                    break;
                case 'city':
                    $branch = $branch->sortable([
                        'city' => $order
                    ]);
                    break;
                case 'district':
                    $branch = $branch->sortable([
                        'district' => $order
                    ]);
                    break;
                case 'id':
                    $branch = $branch->sortable([
                        'id' => $order
                    ]);
                    break;
                default:
                    $branch = $branch->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($name)) {
            $branch = $branch->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($id)) {
            $branch = $branch->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($province)) {
            $branch = $branch->where('province', 'ilike', '%'.$province.'%');
        }

        if (!empty($district)) {
            $branch = $branch->where('district', 'ilike', '%'.$district.'%');
        }

        if (!empty($city)) {
            $branch = $branch->where('city', 'like', '%'.$city.'%');
        }

        $branch = $branch->paginate($perPage);

        return $branch;
    }

    /**
     * Get branch by id
     *
     * @param int $id
     * @return Branch
     */
    public function getById($id)
    {
        if (Cache::has('branch:'.$id)) {
            $branch = Cache::get('branch:'.$id);
        } else {
            $branch = $this->branch->findOrFail($id);
            Cache::put('branch:'.$branch['id'], $branch, env('CACHE_EXP'));
        }
        return $branch;
    }

    /**
     * Get branch by slug
     *
     * @param string $slug
     * @return Branch
     */
    public function getBySlug($slug)
    {
        if (Cache::has('branch.slug:'.$slug)) {
            $branch = Cache::get('branch.slug:'.$slug);
        } else {
            $branch = $this->branch->where('slug', $slug)->first();
            Cache::put('branch.slug:'.$branch['id'], $branch, env('CACHE_EXP'));
        }
        return $branch;
    }

    /**
     * Save Branch
     *
     * @param $data
     * @return Branch
     */
    public function saveBranchRepo($data = [])
    {
        $branch = new $this->branch;

        $branch->name = $data['name'];
        $slug = Str::of($data['name'])->slug('-');
        $branch->slug = $slug;
        $branch->province = $data['province'];
        $branch->city = $data['city'];
        $branch->district = $data['district'];
        $branch->village = $data['village'];
        $branch->postal_code = $data['postalCode'];
        $branch->street = $data['street'];
        $branch->save();
        Cache::put('branch:'.$branch['id'], $branch, env('CACHE_EXP'));
        Cache::put('branch.slug:'.$branch['slug'], $branch, env('CACHE_EXP'));
        Cache::forget('branches');
        return $branch;
    }

    /**
     * Delete data Branch
     *
     * @param array $data
     * @return Branch
     */
    public function delete($id)
    {
        $branch = $this->branch->findOrFail($id);
        $branch->delete();
        Cache::forget('branch:'.$id);
        Cache::forget('branch.slug:'.$branch['slug']);
        Cache::forget('branches');
        return $branch;
    }

    /**
     * edit Branch
     *
     * @param $data
     * @return Branch
     */
    public function updateBranchRepo($data = [])
    {
        $branch = $this->branch->find($data['id']);

        if (!$branch) {
            throw new InvalidArgumentException('Cabang tidak ditemukan');
        }

        $branch->name = $data['name'];
        $slug = Str::slug($data['name'], '-');
        $branch->slug = $slug;
        $branch->province = $data['province'];
        $branch->city = $data['city'];
        $branch->district = $data['district'];
        $branch->village = $data['village'];
        $branch->postal_code = $data['postalCode'];
        $branch->street = $data['street'];
        $branch->save();
        Cache::put('branch:'.$branch['id'], $branch, env('CACHE_EXP'));
        Cache::put('branch.slug:'.$branch['slug'], $branch, env('CACHE_EXP'));
        Cache::forget('branches');
        return $branch;
    }

    /**
     * Get default Branchs list
     *
     * @return Branch
     */
    public function getDefaultBranchRepo()
    {
        $branch = $this->branch->select('name', 'id')->get()->take(10);
        return $branch;
    }

    /**
     * check branch by pickup
     *
     * @param $pickupId
     * @return Branch
     */
    public function checkBranchByPickupRepo($pickupId)
    {
        $branch = $this->branch->whereHas('pickups', function($q) use ($pickupId) {
            $q->where('id', $pickupId);
        })->first();
        return $branch;
    }
}

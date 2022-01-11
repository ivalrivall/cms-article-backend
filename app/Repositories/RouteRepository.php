<?php

namespace App\Repositories;

// MODELS
use App\Models\Route;
use App\Models\Pickup;
use App\Models\RoutePrice;

// OTHER
use InvalidArgumentException;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RouteImport;
use App\Imports\RoutePriceImport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Validators\ValidationException;

class RouteRepository
{
    protected $route;
    protected $pickup;
    protected $routePrice;

    public function __construct(Route $route, Pickup $pickup, RoutePrice $routePrice)
    {
        $this->route = $route;
        $this->pickup = $pickup;
        $this->routePrice = $routePrice;
    }

    /**
     * Get route by fleet / armada,
     * origin, destination city, and destination district
     *
     * @param array $data
     * @return Route
     */
    public function getRouteRepo($data)
    {
        $route = $this->route->where([
            ['fleet_id', '=', $data['fleetId']],
            ['origin', '=', $data['origin']],
            ['destination_district', '=', $data['destination_district']],
            ['destination_city', '=', $data['destination_city']],
        ])->first();
        return $route;
    }

    /**
     * Get all route paginate
     *
     * @param $pickupId
     * @return mixed
     */
    public function getAllPaginateRepo($data = [])
    {
        $origin = $data['origin'];
        $sort = $data['sort'];
        $perPage = $data['perPage'];
        $destinationCity = $data['destinationCity'];
        $destinationDistrict = $data['destinationDistrict'];
        $estimate = $data['estimate'];
        $minWeight = $data['minWeight'];
        $minPrice = $data['minPrice'];
        $fleet = $data['fleet'];

        $route = $this->route->with(['fleet','routePrices']);

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
                case 'fleet.type':
                    $route = $route->sortable([
                        'fleet.type' => $order
                    ]);
                    break;
                case 'origin':
                    $route = $route->sortable([
                        'origin' => $order
                    ]);
                    break;
                case 'destination_city':
                    $route = $route->sortable([
                        'destination_city' => $order
                    ]);
                    break;
                case 'destination_district':
                    $route = $route->sortable([
                        'destination_district' => $order
                    ]);
                    break;
                case 'minimum_weight':
                    $route = $route->sortable([
                        'minimum_weight' => $order
                    ]);
                    break;
                case 'minimum_price':
                    $route = $route->sortable([
                        'minimum_price' => $order
                    ]);
                    break;
                case 'estimate':
                    $route = $route->sortable([
                        'estimate' => $order
                    ]);
                    break;
                case 'created_at':
                    $route = $route->sortable([
                        'created_at' => $order
                    ]);
                    break;
                case 'id':
                    $route = $route->sortable([
                        'id' => $order
                    ]);
                    break;
                default:
                    $route = $route->sortable([
                        'created_at' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($origin)) {
            $route = $route->where('origin', 'ilike', '%'.$origin.'%');
        }

        if (!empty($destinationDistrict)) {
            $route = $route->where('destination_district', 'ilike', '%'.$destinationDistrict.'%');
        }

        if (!empty($destinationCity)) {
            $route = $route->where('destination_city', 'ilike', '%'.$destinationCity.'%');
        }

        if (!empty($minWeight)) {
            $route = $route->where('minimum_weight', 'like', '%'.$minWeight.'%');
        }

        if (!empty($fleet)) {
            $route = $route->whereHas('fleet', function($q) use ($fleet) {
                $q->where('type', 'ilike', '%'.$fleet.'%');
            });
        }

        if (!empty($estimate)) {
            $route = $route->where('estimate', 'like', '%'.$estimate.'%');
        }

        if (!empty($minPrice)) {
            $route = $route->where('minimum_price', 'like', '%'.$minPrice.'%');
        }

        $route = $route->paginate($perPage);

        return $route;
    }

    /**
     * Get route by fleet / armada,
     * origin, and destination city
     *
     * @param array $data
     * @return Route
     */
    public function getRouteByCityRepo($data)
    {
        $route = $this->route->where([
            ['fleet_id', '=', $data['fleetId']],
            ['origin', '=', $data['origin']],
            ['destination_city', '=', $data['destination']],
        ])->with('routePrices')->first();
        return $route;
    }

    /**
     * Get route destination island,
     *
     * @return Route
     */
    public function getDestinationIslandRepo()
    {
        $island = $this->route->select('destination_island')->get();
        $route = [];
        foreach ($island as $key => $value) {
            if (!in_array($value, $route)) {
                $route[] = $value;
            }
        }
        return $route;
    }

    /**
     * create route,
     *
     * @param array $data
     * @return Route
     */
    public function createRouteRepo($data = [])
    {
        $route = $this->route->where('origin', $data['origin'])
                ->where('destination_city', $data['destinationCity'])
                ->where('destination_district', $data['destinationDistrict'])
                ->where('fleet_id', $data['fleet'])->first();

        if ($route) {
            throw new InvalidArgumentException('rute asal sampai tujuan dengan armada yang ini sudah ada');
        }

        $route = new $this->route;
        $route->fleet_id = $data['fleet'];
        $route->origin = $data['origin'];
        $route->destination_island = $data['destinationIsland'];
        $route->destination_city = $data['destinationCity'];
        $route->destination_district = $data['destinationDistrict'];
        $route->minimum_weight = $data['minWeight'];
        $route->estimate = $data['estimate'];
        $route->minimum_price = $data['minPrice'];
        $route->save();

        foreach ($data['prices'] as $key => $value) {
            $routePrice = new $this->routePrice;
            $routePrice->route_id = $route['id'];
            $routePrice->type = $value['type'];
            $routePrice->with_minimum = $value['with_minimum'];
            $routePrice->price = $value['price'];
            $routePrice->save();
            $routePrices[] = $routePrice;
        }
        $result = [
            'routePrices' => $routePrices,
            'route' => $route
        ];
        return $result;
    }

    /**
     * edit route,
     *
     * @param array $data
     * @return Route
     */
    public function editRouteRepo($data = [])
    {
        $route = $this->route->where('id', '!=', $data['id'])->where('origin', $data['origin'])
                ->where('destination_city', $data['destinationCity'])
                ->where('destination_district', $data['destinationDistrict'])
                ->where('fleet_id', $data['fleet'])->first();

        if ($route) {
            throw new InvalidArgumentException('rute asal sampai tujuan dengan armada yang ini sudah ada');
        }

        $route = $this->route->find($data['id']);
        if (!$route) {
            throw new InvalidArgumentException('Rute tidak ditemukan');
        }
        $route->fleet_id = $data['fleet'];
        $route->origin = $data['origin'];
        $route->destination_island = $data['destinationIsland'];
        $route->destination_city = $data['destinationCity'];
        $route->destination_district = $data['destinationDistrict'];
        $route->minimum_weight = $data['minWeight'];
        $route->estimate = $data['estimate'];
        $route->minimum_price = $data['minPrice'];
        $route->save();
        return $route;
    }

    /**
     * Delete route repository
     *
     * @param array $data
     */
    public function deleteRouteRepo($data = [])
    {
        $route = $this->route->find($data['routeId']);
        if (!$route) {
            throw new InvalidArgumentException('Rute tidak ditemukan');
        }
        $route->delete();
        $this->routePrice->where('route_id', $data['routeId'])->delete();
        return $route;
    }

    /**
     * import data rute
     * @param Request $request
     */
    public function importRouteRepo($request)
    {
        try {
            $route = $request->file('route');
            Excel::import(new RouteImport, $route);
        } catch (ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                $row = 'pada baris '.$failure->row(); // row that went wrong
                // $attr = 'error pada attribut '.$failure->attribute(); // either heading key (if using heading row concern) or column index
                $msg = $failure->errors()[0]; // Actual error messages from Laravel validator
                // $val = 'nilai yang salah adalah '.$failure->values(); // The values of the row that has failed.
                $errorMsg = $msg.", ".$row;
                throw new InvalidArgumentException($errorMsg);
                break;
            }
        }
    }

    /**
     * Get route by pickup,
     * origin, destination city, and destination district
     *
     * @param array $data
     * @return Route
     */
    public function getRouteByPickupRepo($data = [])
    {
        $pickup = $this->pickup->with(['sender', 'receiver'])->find($data['pickupId']);
        $route = $this->route->where([
            ['fleet_id', '=', $pickup['fleet_id']],
            ['origin', '=', $pickup['sender']['city']],
            ['destination_district', '=', $pickup['receiver']['district']],
            ['destination_city', '=', $pickup['receiver']['city']],
        ])->first();
        return $route;
    }

    /**
     * get total route
     */
    public function getTotalRouteRepo()
    {
        $route = $this->route->get()->count();
        return $route;
    }

    /**
     * add route price
     */
    public function addPriceRepo($data = [])
    {
        $routePrice = new $this->routePrice;
        $routePrice->route_id = $data['routeId'];
        $routePrice->type = $data['type'];
        $routePrice->with_minimum = $data['withMinimum'];
        $routePrice->price = $data['price'];
        $routePrice->save();
        return $routePrice;
    }

    /**
     * delete route price
     */
    public function deleteRoutePriceRepo($id, $userId)
    {
        $routePrice = $this->routePrice->find($id);
        $routePrice->deleted_by = $userId;
        $routePrice->save();
        $routePrice = $this->routePrice->destroy($id);
        return $routePrice;
    }

    /**
     * get route detail
     */
    public function getReuteDetailRepo($id)
    {
        $route = $this->route->with(['fleet','routePrices'])->find($id);
        return $route;
    }

    /**
     * get route price detail
     */
    public function getRoutePriceRepo($data = [])
    {
        $routePrice = $this->routePrice->where('route_id', $data['routeId'])->get();
        return $routePrice;
    }

    /**
     * import data rute price
     * @param Request $request
     */
    public function importRoutePriceRepo($request)
    {
        try {
            $route = $request->file('route_price');
            Excel::import(new RoutePriceImport, $route);
        } catch (ValidationException $e) {
            $failures = $e->failures();
            foreach ($failures as $failure) {
                $row = 'pada baris '.$failure->row(); // row that went wrong
                // $attr = 'error pada attribut '.$failure->attribute(); // either heading key (if using heading row concern) or column index
                $msg = $failure->errors()[0]; // Actual error messages from Laravel validator
                // $val = 'nilai yang salah adalah '.$failure->values(); // The values of the row that has failed.
                $errorMsg = $msg.", ".$row;
                throw new InvalidArgumentException($errorMsg);
                break;
            }
        }
    }

    /**
     * check price
     */
    public function checkPriceRepo($data = [])
    {
        $route = $this->route->with(['routePrices' => function($q) {
            $q->select('route_id','type','price');
        }])->where([
            ['fleet_id', '=', $data['fleetId']],
            ['origin', 'ilike', '%'.$data['origin'].'%'],
            ['destination_district', 'ilike', '%'.$data['destination_district'].'%'],
            ['destination_city', 'ilike', '%'.$data['destination_city'].'%']
        ])->select(
            'fleet_id',
            'origin',
            'destination_island',
            'destination_city',
            'destination_district',
            'minimum_weight',
            'estimate',
            'minimum_price',
            'id'
        )->get();
        return $route;
    }

    /**
     * get route price by id
     */
    public function getRoutePriceByIdRepo($id)
    {
        $routePrice = $this->routePrice->find($id);
        if (!$routePrice) {
            throw new InvalidArgumentException('Biaya tidak ditemukan');
        }
        return $routePrice;
    }
}

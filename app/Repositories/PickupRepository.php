<?php

namespace App\Repositories;

use App\Models\Address;
use App\Models\Pickup;
use App\Models\PickupPlan;
use App\Models\ShipmentPlan;
use App\Models\Item;
use App\Models\User;
use App\Models\Fleet;
use Indonesia;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class PickupRepository
{
    protected $pickup;
    protected $pickupPlan;
    protected $shipmentPlan;
    protected $item;
    protected $fleet;

    public function __construct(Pickup $pickup, PickupPlan $pickupPlan, Item $item, ShipmentPlan $shipmentPlan, Fleet $fleet)
    {
        $this->pickup = $pickup;
        $this->pickupPlan = $pickupPlan;
        $this->item = $item;
        $this->shipmentPlan = $shipmentPlan;
        $this->fleet = $fleet;
    }

    /**
     * Save Pickup
     *
     * @param array $data
     * @param Promo $promo
     * @return Pickup
     */
    public function createPickupRepo($data, $promo)
    {
        $config = [
            'table' => 'pickups',
            'length' => 12,
            'field' => 'number',
            'prefix' => 'P'.Carbon::now('Asia/Jakarta')->format('ymd'),
            'reset_on_prefix_change' => true
        ];
        $pickup = new $this->pickup;

        $pickup->fleet_id           = $data['fleetId'];
        $pickup->user_id            = $data['userId'];
        $pickup->promo_id           = $promo['id'] ?? null;
        $pickup->name               = $data['name'];
        $pickup->phone              = $data['phone'];
        $pickup->sender_id          = $data['senderId'];
        $pickup->receiver_id        = $data['receiverId'];
        $pickup->debtor_id          = $data['debtorId'];
        $pickup->notes              = $data['notes'];
        $pickup->picktime           = $data['picktime'];
        $pickup->created_by         = $data['userId'];
        $pickup->status             = 'request';
        $pickup->number             = IdGenerator::generate($config);
        $pickup->save();

        return $pickup;
    }

    /**
     * Save get pickup by userId
     *
     * @param Pickup $pickup
     */
    public function getByUserId($pickup)
    {
        return $this->user->find($id)->pickups()->get();
    }

    /**
     * get all pickup pagination
     *
     * @param Pickup $pickup
     */
    public function getAllPickupPaginate($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $number = $data['number'];
        $name = $data['name'];
        $city = $data['city'];
        $district = $data['district'];
        $village = $data['village'];
        $picktime = $data['picktime'];
        $isDrop = $data['isDrop'];
        $sort = $data['sort'];
        $status = $data['status'] ?? null;

        $pickup = $this->pickup->where('is_drop', $data['isDrop'])->with(['user','sender','receiver','debtor','fleet','promo','items','items.service','cost','marketing']);

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
                case 'number':
                    $pickup = $pickup->sortable([
                        'number' => $order
                    ]);
                    break;
                case 'user.name':
                    $pickup = $pickup->sortable([
                        'user.name' => $order
                    ]);
                    break;
                case 'sender.city':
                    $pickup = $pickup->sortable([
                        'sender.city' => $order
                    ]);
                    break;
                case 'sender.district':
                    $pickup = $pickup->sortable([
                        'sender.district' => $order
                    ]);
                    break;
                case 'sender.village':
                    $pickup = $pickup->sortable([
                        'sender.village' => $order
                    ]);
                    break;
                case 'picktime':
                    $pickup = $pickup->sortable([
                        'picktime' => $order
                    ]);
                    break;
                case 'status':
                    $pickup = $pickup->sortable([
                        'status' => $order
                    ]);
                    break;
                default:
                    $pickup = $pickup->sortable([
                        'number' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $pickup = $pickup->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($number)) {
            $pickup = $pickup->where('number', 'ilike', '%'.$number.'%');
        }

        if (!empty($name)) {
            $pickup = $pickup->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($city)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($city) {
                $q->where('city', 'ilike', '%'.$city.'%');
            });
        }

        if (!empty($district)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($district) {
                $q->where('district', 'ilike', '%'.$district.'%');
            });
        }

        if (!empty($village)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($village) {
                $q->where('village', 'ilike', '%'.$village.'%');
            });
        }

        if (!empty($picktime)) {
            $pickup = $pickup->where('picktime', 'ilike', '%'.$picktime.'%');
        }

        if (!empty($status)) {
            $pickup = $pickup->where('status', 'ilike', '%'.$status.'%');
        }

        $result = $pickup->orderBy('created_at', 'DESC')->paginate($perPage);

        return $result;
    }

    /**
     * get ready to pickup pagination
     *
     * @param array $data
     */
    public function getReadyToPickupRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $name = $data['name'];
        $city = $data['city'];
        $district = $data['district'];
        $village = $data['village'];
        $picktime = $data['picktime'];
        $sort = $data['sort'];
        $number = $data['number'];

        $pickup = $this->pickup->whereNull('pickup_plan_id')->where('status','request')->with(['sender' => function($q) {
            $q->select('id','city','district','village');
        },'items' => function($q) {
            $q->select('id','weight','volume','pickup_id');
        }])->select('name','id','sender_id','picktime','number','user_id');

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
                    $pickup = $pickup->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'user.name':
                    $pickup = $pickup->sortable([
                        'user.name' => $order
                    ]);
                    break;
                case 'sender.city':
                    $pickup = $pickup->sortable([
                        'sender.city' => $order
                    ]);
                    break;
                case 'sender.district':
                    $pickup = $pickup->sortable([
                        'sender.district' => $order
                    ]);
                    break;
                case 'sender.village':
                    $pickup = $pickup->sortable([
                        'sender.village' => $order
                    ]);
                    break;
                case 'picktime':
                    $pickup = $pickup->sortable([
                        'picktime' => $order
                    ]);
                    break;
                case 'number':
                    $pickup = $pickup->sortable([
                        'number' => $order
                    ]);
                    break;
                default:
                    $pickup = $pickup->sortable([
                        'number' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $pickup = $pickup->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($name)) {
            $pickup = $pickup->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($city)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($city) {
                $q->where('city', 'ilike', '%'.$city.'%');
            });
        }

        if (!empty($district)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($district) {
                $q->where('district', 'ilike', '%'.$district.'%');
            });
        }

        if (!empty($village)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($village) {
                $q->where('village', 'ilike', '%'.$village.'%');
            });
        }

        if (!empty($picktime)) {
            $pickup = $pickup->where('picktime', 'ilike', '%'.$picktime.'%');
        }

        if (!empty($number)) {
            $pickup = $pickup->where('number', 'ilike', '%'.$number.'%');
        }

        $result = $pickup->paginate($perPage);

        return $result;
    }

    /**
     * get list pickup plan
     *
     * @param array $data
     */
    public function getListPickupPlanRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $number = $data['number'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        $status = $data['status'];
        $driver = $data['driver'];
        $licenseNumber = $data['licenseNumber'];
        $vehicleType = $data['vehicleType'];
        $sort = $data['sort'];
        $branchId = $data['branchId'];

        $pickupPlan = $this->pickupPlan->with(['driver.user','vehicle', 'pickups' => function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        }])->whereHas('pickups', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->where('vehicle_id', '!=', 0)->where('driver_id', '!=', 0);

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
                    $pickupPlan = $pickupPlan->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'number':
                    $pickupPlan = $pickupPlan->sortable([
                        'number' => $order
                    ]);
                    break;
                case 'status':
                    $pickupPlan = $pickupPlan->sortable([
                        'status' => $order
                    ]);
                    break;
                case 'vehicle.license_plate':
                    $pickupPlan = $pickupPlan->sortable([
                        'vehicle.license_plate' => $order
                    ]);
                    break;
                case 'vehicle.type':
                    $pickupPlan = $pickupPlan->sortable([
                        'vehicle.type' => $order
                    ]);
                    break;
                case 'created_at':
                    $pickupPlan = $pickupPlan->sortable([
                        'created_at' => $order
                    ]);
                    break;
                default:
                    $pickupPlan = $pickupPlan->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $pickupPlan = $pickupPlan->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($startDate) && !empty($endDate)) {
            $pickupPlan = $pickupPlan->whereHas('pickups', function ($q) use ($startDate, $endDate){
                $q->whereDate('picktime', '>=', date($startDate))
                    ->whereDate('picktime', '<=', date($endDate));
            });
        }

        if (!empty($status)) {
            $pickupPlan = $pickupPlan->where('status', 'ilike', '%'.$status.'%');
        }

        if (!empty($driver)) {
            $pickupPlan = $pickupPlan->whereHas('driver', function($d) use ($driver) {
                $d->whereHas('user', function($u) use ($driver) {
                    $u->where('name', 'ilike', '%'.$driver.'%');
                });
            });
        }

        if (!empty($licenseNumber)) {
            $pickupPlan = $pickupPlan->whereHas('vehicle', function($q) use ($licenseNumber) {
                $q->where('license_plate', 'ilike', '%'.$licenseNumber.'%');
            });
        }

        if (!empty($vehicleType)) {
            $pickupPlan = $pickupPlan->whereHas('vehicle', function($q) use ($vehicleType) {
                $q->where('type', 'ilike', '%'.$vehicleType.'%');
            });
        }

        $result = $pickupPlan->paginate($perPage);

        return $result;
    }

    /**
     * get list pickup inside pickup plan
     *
     * @param array $data
     */
    public function getPickupByPickupPlanRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $name = $data['name'];
        $city = $data['city'];
        $district = $data['district'];
        $village = $data['village'];
        $sort = $data['sort'];

        $pickup = $this->pickup->with(['user','sender','proofOfPickup' => function ($q) {
            $q->select('id','pickup_id');
        },'pickupPlan' => function($q) {
            $q->select('id','created_at');
        }])->where('pickup_plan_id', $data['pickupPlanId']);

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
                    $pickup = $pickup->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'name':
                    $pickup = $pickup->sortable([
                        'name' => $order
                    ]);
                    break;
                case 'sender.city':
                    $pickup = $pickup->sortable([
                        'sender.city' => $order
                    ]);
                    break;
                case 'sender.district':
                    $pickup = $pickup->sortable([
                        'sender.district' => $order
                    ]);
                    break;
                case 'sender.village':
                    $pickup = $pickup->sortable([
                        'sender.village' => $order
                    ]);
                    break;
                default:
                    $pickup = $pickup->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $pickup = $pickup->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($name)) {
            $pickup = $pickup->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($city)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($city) {
                $q->where('city', 'ilike', '%'.$city.'%');
            });
        }

        if (!empty($district)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($district) {
                $q->where('district', 'ilike', '%'.$district.'%');
            });
        }

        if (!empty($village)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($village) {
                $q->where('village', 'ilike', '%'.$village.'%');
            });
        }

        $result = $pickup->paginate($perPage);

        return $result;
    }

    /**
     * check pickups request date
     *
     * @param array $data
     */
    public function checkPickupRequestDate($data = [])
    {
        $pickup = Pickup::select('picktime')->whereIn('id', $data)->get()->pluck('picktime');
        $pickup = collect($pickup)->toArray();
        $result = [];
        foreach ($pickup as $key => $value) {
            $result[] = Carbon::parse($value)->format('Y-m-d');
        }
        if (count(array_unique($result)) === 1) {
            return $result;
        }
        throw new InvalidArgumentException('Maaf, ada permintaan tanggal pickup yang berbeda');
    }

    /**
     * get pickup pagination by customer id
     *
     * @param Pickup $pickup
     */
    public function getPickupByCustomerRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $name = $data['name'];
        $city = $data['city'];
        $district = $data['district'];
        $village = $data['village'];
        $picktime = $data['picktime'];
        $sort = $data['sort'];

        $pickup = $this->pickup->where('user_id', $data['userId'])->with(['user','sender','receiver','debtor','fleet','promo']);

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
                    $pickup = $pickup->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'user.name':
                    $pickup = $pickup->sortable([
                        'user.name' => $order
                    ]);
                    break;
                case 'sender.city':
                    $pickup = $pickup->sortable([
                        'sender.city' => $order
                    ]);
                    break;
                case 'sender.district':
                    $pickup = $pickup->sortable([
                        'sender.district' => $order
                    ]);
                    break;
                case 'sender.village':
                    $pickup = $pickup->sortable([
                        'sender.village' => $order
                    ]);
                    break;
                case 'picktime':
                    $pickup = $pickup->sortable([
                        'picktime' => $order
                    ]);
                    break;
                default:
                    $pickup = $pickup->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $pickup = $pickup->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($name)) {
            $pickup = $pickup->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($city)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($city) {
                $q->where('city', 'ilike', '%'.$city.'%');
            });
        }

        if (!empty($district)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($district) {
                $q->where('district', 'ilike', '%'.$district.'%');
            });
        }

        if (!empty($village)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($village) {
                $q->where('village', 'ilike', '%'.$village.'%');
            });
        }

        if (!empty($picktime)) {
            $pickup = $pickup->where('picktime', 'ilike', '%'.$picktime.'%');
        }

        $result = $pickup->simplePaginate($perPage);

        return $result;
    }

    /**
     * get list pickup plan driver
     * @param array $data
     */
    public function getListPickupPlanDriverRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $userId = $data['userId'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        $status = $data['status'];
        $licenseNumber = $data['licenseNumber'];
        $vehicleType = $data['vehicleType'];
        $sort = $data['sort'];

        // $pickupPlan = $this->pickupPlan->where('status', 'applied')->whereHas('vehicle', function($q) use ($userId) {
        //     $q->whereHas('driver', function($o) use ($userId) {
        //         $o->whereHas('user', function($p) use ($userId) {
        //             $p->where('id', $userId);
        //         });
        //     });
        // })->with(['pickups:id,status,pickup_plan_id']);

        $pickupPlan = $this->pickupPlan->where('status', 'applied')->whereHas('driver', function($d) use ($userId) {
            $d->where('user_id', $userId);
        })->with(['pickups:id,status,pickup_plan_id','pickups.proofOfPickups']);

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
                    $pickupPlan = $pickupPlan->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'user.name':
                    $pickupPlan = $pickupPlan->sortable([
                        'user.name' => $order
                    ]);
                    break;
                case 'sender.city':
                    $pickupPlan = $pickupPlan->sortable([
                        'sender.city' => $order
                    ]);
                    break;
                case 'sender.district':
                    $pickupPlan = $pickupPlan->sortable([
                        'sender.district' => $order
                    ]);
                    break;
                case 'sender.village':
                    $pickupPlan = $pickupPlan->sortable([
                        'sender.village' => $order
                    ]);
                    break;
                case 'picktime':
                    $pickupPlan = $pickupPlan->sortable([
                        'picktime' => $order
                    ]);
                    break;
                default:
                    $pickupPlan = $pickupPlan->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $pickupPlan = $pickupPlan->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($startDate) && !empty($endDate)) {
            $pickupPlan = $pickupPlan->whereHas('pickups', function ($q) use ($startDate, $endDate){
                $q->whereDate('picktime', '>=', date($startDate))
                    ->whereDate('picktime', '<=', date($endDate));
            });
        }

        if (!empty($status)) {
            $pickupPlan = $pickupPlan->where('status', 'ilike', '%'.$status.'%');
        }

        if (!empty($licenseNumber)) {
            $pickupPlan = $pickupPlan->whereHas('vehicle', function($q) use ($licenseNumber) {
                $q->where('license_plate', 'ilike', '%'.$licenseNumber.'%');
            });
        }

        if (!empty($vehicleType)) {
            $pickupPlan = $pickupPlan->whereHas('vehicle', function($q) use ($vehicleType) {
                $q->where('type', 'ilike', '%'.$vehicleType.'%');
            });
        }

        $result = $pickupPlan->simplePaginate($perPage);

        return $result;
    }

    /**
     * get ready to pickup order inside pickup plan pagination
     * driver only
     *
     * @param array $data
     */
    public function getReadyToPickupDriverRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $userId = $data['userId'];
        $name = $data['name'];
        $city = $data['city'];
        $district = $data['district'];
        $village = $data['village'];
        $picktime = $data['picktime'];
        $sort = $data['sort'];

        $pickup = $this->pickup->whereNull('pickup_plan_id')->with(['user','sender','receiver','debtor','fleet','promo']);

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
                    $pickup = $pickup->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'user.name':
                    $pickup = $pickup->sortable([
                        'user.name' => $order
                    ]);
                    break;
                case 'sender.city':
                    $pickup = $pickup->sortable([
                        'sender.city' => $order
                    ]);
                    break;
                case 'sender.district':
                    $pickup = $pickup->sortable([
                        'sender.district' => $order
                    ]);
                    break;
                case 'sender.village':
                    $pickup = $pickup->sortable([
                        'sender.village' => $order
                    ]);
                    break;
                case 'picktime':
                    $pickup = $pickup->sortable([
                        'picktime' => $order
                    ]);
                    break;
                default:
                    $pickup = $pickup->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $pickup = $pickup->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($name)) {
            $pickup = $pickup->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($city)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($city) {
                $q->where('city', 'ilike', '%'.$city.'%');
            });
        }

        if (!empty($district)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($district) {
                $q->where('district', 'ilike', '%'.$district.'%');
            });
        }

        if (!empty($village)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($village) {
                $q->where('village', 'ilike', '%'.$village.'%');
            });
        }

        if (!empty($picktime)) {
            $pickup = $pickup->where('picktime', 'ilike', '%'.$picktime.'%');
        }

        $result = $pickup->paginate($perPage);

        return $result;
    }

    /**
     * get list pickup inside pickup plan
     * driver only
     *
     * @param array $data
     */
    public function getPickupByPickupPlanDriverRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $userId = $data['userId'];
        $name = $data['name'];
        $city = $data['city'];
        $district = $data['district'];
        $village = $data['village'];
        $street = $data['street'];
        $sort = $data['sort'];

        $pickup = $this->pickup->select('id', 'name', 'phone','sender_id','number')->with([
            'sender' => function ($q) {
                $q->select('id','street');
            },
            'proofOfPickup' => function ($q) {
                $q->select('id','pickup_id','status','driver_pick','status_pick','number');
            }
        ])->where('pickup_plan_id', $data['pickupPlanId']);

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
                    $pickup = $pickup->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'name':
                    $pickup = $pickup->sortable([
                        'name' => $order
                    ]);
                    break;
                case 'sender.city':
                    $pickup = $pickup->sortable([
                        'sender.city' => $order
                    ]);
                    break;
                case 'sender.district':
                    $pickup = $pickup->sortable([
                        'sender.district' => $order
                    ]);
                    break;
                case 'sender.village':
                    $pickup = $pickup->sortable([
                        'sender.village' => $order
                    ]);
                    break;
                default:
                    $pickup = $pickup->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $pickup = $pickup->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($name)) {
            $pickup = $pickup->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($city)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($city) {
                $q->where('city', 'ilike', '%'.$city.'%');
            });
        }

        if (!empty($district)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($district) {
                $q->where('district', 'ilike', '%'.$district.'%');
            });
        }

        if (!empty($village)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($village) {
                $q->where('village', 'ilike', '%'.$village.'%');
            });
        }

        if (!empty($street)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($street) {
                $q->where('street', 'ilike', '%'.$street.'%');
            });
        }

        $result = $pickup->simplePaginate($perPage);

        return $result;
    }

    /**
     * get total volume and kilo of pickup inside pickup plan
     *
     * @param array $data
     */
    public function getTotalVolumeAndKiloPickupRepo($data = [])
    {
        $pickups = $this->pickup->select('id')->where('pickup_plan_id', $data['pickupPlanId'])->get();
        $sumVol = 0;
        $sumKilo = 0;
        foreach ($pickups as $key => $value) {
            $items = $this->item->where('pickup_id', $value['id'])->get();
            foreach ($items as $k => $v) {
                $sumVol += $v['volume'];
                $sumKilo += $v['weight'];
            }
        }
        $data = [
            'volume' => $sumVol,
            'kilo' => $sumKilo
        ];
        return $data;
    }

    /**
     * get detail pickup order for driver
     * @param array $data
     */
    public function getDetailPickupRepo($data = [])
    {
        $pickup = $this->pickup->select('id','name','phone','picktime','sender_id','receiver_id','fleet_id')->where('id', $data['pickupId'])->with(
            [
                'sender' => function($q) {
                    $q->select('id', 'province','city','district','village','postal_code','street');
                },
                'receiver' => function($q) {
                    $q->select('id','province','city','district');
                },
                'items',
                'items.service' => function($q) {
                    $q->select('id','name');
                }
            ])->first();

        if (!$pickup) {
            throw new InvalidArgumentException('Maaf, ada pickup order tidak ditemukan');
        }

        return $pickup;

    }

    /**
     * check pickup have pickup plan
     * @param array $data
     */
    public function checkPickupHasPickupPlan($data = [])
    {
        $pickup = $this->pickup->find($data['pickupId']);
        if (!$pickup) {
            throw new InvalidArgumentException('Pickup tidak ditemukan');
        }
        if ($pickup['pickup_plan_id'] == null) {
            throw new InvalidArgumentException('Pickup ini tidak memiliki pickup plan');
        }
    }

    /**
     * get detail pickup order for web
     * @param array $data
     */
    public function getDetailPickupAdminRepo($data = [])
    {
        $pickup = $this->pickup->select('id','name','phone','picktime','sender_id','receiver_id','pickup_plan_id','status','number','is_drop','fleet_id','promo_id','user_id')
            ->where('id', $data['pickupId'])
            ->whereHas('proofOfPickup', function($q) use ($data) {
                $q->where('id', $data['popId']);
            })->with(
            [
                'sender' => function($q) {
                    $q->select('id', 'province','city','district','village','postal_code','street');
                },
                'receiver' => function($q) {
                    $q->select('id','city','district');
                },
                'items' => function($q) {
                    $q->select('id','name','pickup_id','unit_count','service_id','weight','volume','price','unit','route_price_id');
                },
                'items.routePrice',
                'cost',
                // 'items.unit' => function($q) {
                //     $q->select('id','name');
                // },
                'items.service' => function($q) {
                    $q->select('id','name');
                },
                'pickupPlan' => function($q) {
                    $q->select('id','vehicle_id','number','driver_id');
                },
                'pickupPlan.vehicle' => function($q) {
                    $q->select('id','driver_id');
                },
                'pickupPlan.driver' => function($q) {
                    $q->select('id','user_id');
                },
                'pickupPlan.driver.user' => function($q) {
                    $q->select('id','name');
                },
                'proofOfPickup' => function($q) {
                    $q->select('id', 'pickup_id', 'notes', 'status_pick');
                }
            ])->first();

        if (!$pickup) {
            throw new InvalidArgumentException('Maaf, ada pickup order tidak ditemukan');
        }
        return $pickup;
    }

    /**
     * create pickup plan
     * @param array $data
     */
    public function updatePickupRepo($data = [])
    {
        $pickup = $this->pickup->find($data['pickup']['id']);
        if (!$pickup) {
            throw new InvalidArgumentException('Gagal merubah status pickup');
        }
        $pickup->status           = $data['pickup']['status'];
        $pickup->save();

        return $pickup;
    }

    /**
     * update branch in pickup order
     * @param array $pickupId
     * @param int $branchid
     */
    public function updateBranchRepo($pickupId = [], $branchId)
    {
        try {
            $branchFrom = $this->pickup->select('branch_id', 'id')->whereIn('id', $pickupId)->get();
            $this->pickup->whereIn('id', $pickupId)->update(['branch_id' => $branchId]);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengupdate cabang pada order');
        }
        return $branchFrom;
    }

    /**
     * get branch in pickup order
     * @param array $pickupId
     */
    public function getPickupBranchRepo($pickupId)
    {
        try {
            $branchFrom = $this->pickup->select('branch_id', 'id')->whereIn('id', $pickupId)->get();
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapat cabang pada pickup');
        }
        return $branchFrom;
    }

    /**
     * get ready to shipment pagination
     *
     * @param array $data
     */
    public function getReadyToShipmentRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $name = $data['name'];
        $city = $data['city'];
        $district = $data['district'];
        $village = $data['village'];
        $picktime = $data['picktime'];
        $sort = $data['sort'];
        $number = $data['number'];
        $branchId = $data['branchId'];
        $isTransit = $data['isTransit'];

        $pickup = $this->pickup
            ->where('status', 'applied')
            ->where('branch_id', $branchId)
            ->whereNotNull('pickup_plan_id')
            ->whereHas('proofOfPickup', function($q) {
                $q->where('status', 'applied')->where(function($q) {
                    $q->where('status_pick', 'updated')->orWhere('status_pick', 'success');
                });
            })
            ->whereNull('shipment_plan_id')
            ->where(function($q) {
                $q->doesnthave('transit')->orWhereHas('transit', function($q) {
                    $q->where('status', 'applied')->where('status_transit', 'success')->whereNull('deleted_at');
                });
            })
            ->whereHas('pickupPlan', function($q) {
                $q->where('status', 'applied');
            })
            ->with(['sender' => function($q) {
                $q->select('id','city','district','village');
            },'items' => function($q) {
                $q->select('id','weight','volume','pickup_id');
            },'cost' => function($q) {
                $q->select('id','status','pickup_id');
            }])->select('name','id','sender_id','picktime','number','is_transit','user_id');

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
                    $pickup = $pickup->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'number':
                    $pickup = $pickup->sortable([
                        'number' => $order
                    ]);
                    break;
                case 'user.name':
                    $pickup = $pickup->sortable([
                        'user.name' => $order
                    ]);
                    break;
                case 'sender.city':
                    $pickup = $pickup->sortable([
                        'sender.city' => $order
                    ]);
                    break;
                case 'sender.district':
                    $pickup = $pickup->sortable([
                        'sender.district' => $order
                    ]);
                    break;
                case 'sender.village':
                    $pickup = $pickup->sortable([
                        'sender.village' => $order
                    ]);
                    break;
                case 'picktime':
                    $pickup = $pickup->sortable([
                        'picktime' => $order
                    ]);
                    break;
                case 'updated_at':
                    $pickup = $pickup->sortable([
                        'updated_at' => $order
                    ]);
                    break;
                default:
                    $pickup = $pickup->sortable([
                        'updated_at' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($isTransit)) {
            $pickup = $pickup->where('is_transit', $isTransit);
        }

        if (!empty($id)) {
            $pickup = $pickup->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($number)) {
            $pickup = $pickup->where('number', 'ilike', '%'.$number.'%');
        }

        if (!empty($name)) {
            $pickup = $pickup->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($city)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($city) {
                $q->where('city', 'ilike', '%'.$city.'%');
            });
        }

        if (!empty($district)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($district) {
                $q->where('district', 'ilike', '%'.$district.'%');
            });
        }

        if (!empty($village)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($village) {
                $q->where('village', 'ilike', '%'.$village.'%');
            });
        }

        if (!empty($picktime)) {
            $pickup = $pickup->where('picktime', 'ilike', '%'.$picktime.'%');
        }

        $result = $pickup->paginate($perPage);

        return $result;
    }

    /**
     * get list shipment plan
     *
     * @param array $data
     */
    public function getListShipmentPlanRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        $status = $data['status'];
        $driver = $data['driver'];
        $licenseNumber = $data['licenseNumber'];
        $vehicleType = $data['vehicleType'];
        $sort = $data['sort'];
        $branchId = $data['branchId'];
        $number = $data['number'];
        $branchName = $data['branchName'];

        $shipmentPlan = $this->shipmentPlan->where(function($q) use ($branchId) {
            $q->whereHas('pickups', function($q) use ($branchId) {
                $q->whereHas('branch', function($o) use ($branchId) {
                    $o->where('id', $branchId);
                });
            })->orWhere('branch_from', $branchId);
        })->with(['vehicle', 'pickups.branch', 'driver.user', 'pickups.proofOfDelivery' => function($q) {
            $q->select('id','pickup_id');
        }, 'pickups.transit' => function($q) {
            $q->select('id','pickup_id');
        }]);

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
                    $shipmentPlan = $shipmentPlan->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'status':
                    $shipmentPlan = $shipmentPlan->sortable([
                        'status' => $order
                    ]);
                    break;
                case 'vehicle.license_plate':
                    $shipmentPlan = $shipmentPlan->sortable([
                        'vehicle.license_plate' => $order
                    ]);
                    break;
                case 'vehicle.type':
                    $shipmentPlan = $shipmentPlan->sortable([
                        'vehicle.type' => $order
                    ]);
                    break;
                case 'created_at':
                    $shipmentPlan = $shipmentPlan->sortable([
                        'created_at' => $order
                    ]);
                    break;
                default:
                    $shipmentPlan = $shipmentPlan->sortable([
                        'updated_at' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $shipmentPlan = $shipmentPlan->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($startDate) && !empty($endDate)) {
            $shipmentPlan = $shipmentPlan->whereDate('created_at', '>=', date($startDate))->whereDate('created_at', '<=', date($endDate));
        }

        if (!empty($status)) {
            $shipmentPlan = $shipmentPlan->where('status', 'ilike', '%'.$status.'%');
        }

        if (!empty($driver)) {
            $shipmentPlan = $shipmentPlan->whereHas('driver', function($d) use ($driver) {
                $d->whereHas('user', function($u) use ($driver) {
                    $u->where('name', 'ilike', '%'.$driver.'%');
                });
            });
        }

        if (!empty($licenseNumber)) {
            $shipmentPlan = $shipmentPlan->whereHas('vehicle', function($q) use ($licenseNumber) {
                $q->where('license_plate', 'ilike', '%'.$licenseNumber.'%');
            });
        }

        if (!empty($vehicleType)) {
            $shipmentPlan = $shipmentPlan->whereHas('vehicle', function($q) use ($vehicleType) {
                $q->where('type', 'ilike', '%'.$vehicleType.'%');
            });
        }

        if (!empty($number)) {
            $shipmentPlan = $shipmentPlan->where('number', 'ilike', '%'.$number.'%');
        }

        if (!empty($branchName)) {
            $shipmentPlan = $shipmentPlan->where(function($q) use ($branchName) {
                $q->whereHas('pickups', function($q) use ($branchName) {
                    $q->whereHas('branch', function($o) use ($branchName) {
                        $o->where('name', 'ilike', "%$branchName%");
                    });
                });
            });
        }

        $result = $shipmentPlan->paginate($perPage);

        return $result;
    }

    /**
     * get list pickup inside shipment plan
     *
     * @param array $data
     */
    public function getPickupByShipmentPlanRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $id = $data['id'];
        $name = $data['name'];
        $province = $data['province'];
        $city = $data['city'];
        $district = $data['district'];
        $village = $data['village'];
        $sort = $data['sort'];
        $number = $data['number'];

        $pickup = $this->pickup->with(['user','sender','shipmentPlan' => function($q) {
            $q->select('id','created_at');
        }])->whereNotNull('pickup_plan_id')->where('shipment_plan_id', $data['shipmentPlanId']);

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
                    $pickup = $pickup->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'name':
                    $pickup = $pickup->sortable([
                        'name' => $order
                    ]);
                    break;
                case 'sender.city':
                    $pickup = $pickup->sortable([
                        'sender.city' => $order
                    ]);
                    break;
                case 'sender.province':
                    $pickup = $pickup->sortable([
                        'sender.province' => $order
                    ]);
                    break;
                case 'sender.district':
                    $pickup = $pickup->sortable([
                        'sender.district' => $order
                    ]);
                    break;
                case 'sender.village':
                    $pickup = $pickup->sortable([
                        'sender.village' => $order
                    ]);
                    break;
                default:
                    $pickup = $pickup->sortable([
                        'updated_at' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $pickup = $pickup->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($number)) {
            $pickup = $pickup->where('number', 'ilike', '%'.$number.'%');
        }

        if (!empty($name)) {
            $pickup = $pickup->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($province)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($province) {
                $q->where('province', 'ilike', '%'.$province.'%');
            });
        }

        if (!empty($city)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($city) {
                $q->where('city', 'ilike', '%'.$city.'%');
            });
        }

        if (!empty($district)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($district) {
                $q->where('district', 'ilike', '%'.$district.'%');
            });
        }

        if (!empty($village)) {
            $pickup = $pickup->whereHas('sender', function($q) use ($village) {
                $q->where('village', 'ilike', '%'.$village.'%');
            });
        }

        $result = $pickup->paginate($perPage);

        return $result;
    }

    /**
     * update is transit branch in pickup order
     * @param array $pickupId
     */
    public function updateIsTransitBranchRepo($pickupId, $value)
    {
        return $this->pickup->whereIn('id', $pickupId)->update(['is_transit' => $value]);
    }

    /**
     * cancel shipment plan
     * @param array $shipmentPlanId
     */
    public function cancelShipmentPlanRepo($shipmentPlanId)
    {
        $result = $this->pickup->where('shipment_plan_id', $shipmentPlanId)->update(['shipment_plan_id' => null]);
        return $result;
    }

    /**
     * check pickup have shipment by pickup plan
     */
    public function checkPickupHaveShipment($pickupPlanId)
    {
        $pickups = $this->pickup->where('pickup_plan_id', $pickupPlanId)->get();
        foreach ($pickups as $key => $value) {
            if ($value->shipment_plan_id !== null) {
                throw new InvalidArgumentException('Maaf, ada order sudah masuk ke shipment plan, sehingga tidak dapat dibatalkan');
            }
        }
    }

    /**
     * Save Drop Order
     * DEPRECATED
     * @param array $data
     * @param Promo $promo
     * @return Pickup
     */
    // public function createDropAdminRepo($data, $promo)
    // {
    //     $config = [
    //         'table' => 'pickups',
    //         'length' => 12,
    //         'field' => 'number',
    //         'prefix' => 'P'.Carbon::now('Asia/Jakarta')->format('ymd'),
    //         'reset_on_prefix_change' => true
    //     ];
    //     $pickup = new $this->pickup;

    //     $pickup->fleet_id           = $data['fleetId'];
    //     $pickup->user_id            = $data['userId'];
    //     $pickup->promo_id           = $promo['id'] ?? null;
    //     $pickup->name               = $data['name'];
    //     $pickup->phone              = $data['phone'];
    //     $pickup->sender_id          = $data['senderId'];
    //     $pickup->receiver_id        = $data['receiverId'];
    //     $pickup->debtor_id          = $data['debtorId'];
    //     $pickup->notes              = $data['notes'];
    //     $pickup->picktime           = $data['picktime'];
    //     $pickup->created_by         = $data['userId'];
    //     $pickup->status             = 'applied';
    //     $pickup->number             = IdGenerator::generate($config);
    //     $pickup->is_drop            = true;
    //     $pickup->save();

    //     return $pickup;
    // }

    /**
     * Save Pickup by admin
     *
     * @param array $data
     * @param Promo $promo
     * @param object $customer
     * @param boolean $isDrop
     * @return Pickup
     */
    public function createPickupAdminRepo($data, $promo, $customer, $isDrop)
    {
        $config = [
            'table' => 'pickups',
            'length' => 12,
            'field' => 'number',
            'prefix' => 'P'.Carbon::now('Asia/Jakarta')->format('ymd'),
            'reset_on_prefix_change' => true
        ];
        $pickup = new $this->pickup;

        $pickup->fleet_id           = $data['fleetId'];
        $pickup->user_id            = $customer['id'];
        $pickup->promo_id           = $promo['id'] ?? null;
        $pickup->name               = $data['name'];
        $pickup->phone              = $data['phone'];
        $pickup->sender_id          = $data['senderId'];
        $pickup->receiver_id        = $data['receiverId'];
        $pickup->debtor_id          = $data['debtorId'];
        $pickup->notes              = $data['notes'];
        $pickup->picktime           = $data['picktime'];
        $pickup->created_by         = $data['userId'];
        $pickup->status             = 'request';
        $pickup->number             = IdGenerator::generate($config);
        $pickup->is_drop            = $isDrop;
        $pickup->save();

        return $pickup;
    }

    /**
     * Edit Pickup by admin
     *
     * @param array $data
     * @param Promo $promo
     * @param object $customer
     * @param boolean $isDrop
     * @return Pickup
     */
    public function editPickupAdminRepo($data, $promo, $customer, $isDrop)
    {
        $pickup = $this->pickup->find($data['id']);

        $pickup->fleet_id           = $data['fleetId'];
        $pickup->user_id            = $customer['id'];
        $pickup->promo_id           = $promo['id'] ?? null;
        $pickup->name               = $data['name'];
        $pickup->phone              = $data['phone'];
        $pickup->sender_id          = $data['senderId'];
        $pickup->receiver_id        = $data['receiverId'];
        $pickup->debtor_id          = $data['debtorId'];
        $pickup->notes              = $data['notes'];
        $pickup->picktime           = $data['picktime'];
        $pickup->updated_by         = $data['userId'];
        $pickup->is_drop            = $isDrop;
        $pickup->save();

        return $pickup;
    }

    /**
     * cancel drop by admin
     */
    public function cancelDropRepo($pickupId, $userId)
    {
        $drop = $this->pickup->find($pickupId);
        if ($drop->shipment_plan_id !== null) {
            throw new InvalidArgumentException('Drop order tidak dapat dibatalkan, karena shipment plan sudah terbuat');
        }
        $drop->updated_by         = $userId;
        $drop->status             = 'canceled';
        $drop->save();
        return $drop;
    }

    /**
     * get all order in branch
     */
    public function getOrderOnBranchRepo($branchId)
    {
        $order = $this->pickup->where('branch_id', $branchId)->count();
        return $order;
    }

    /**
     * get finished pickup order paginate
     * @param array $data
     */
    public function getFinishedPickupRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $sort = $data['sort'];

        $number = $data['number'];
        $name = $data['name'];
        $receiver = $data['receiver'];
        $debtor = $data['debtor'];
        $paymentMethod = $data['paymentMethod'];

        $branchName = $data['branchName'];

        $dateFrom = $data['dateFrom'];
        $dateTo = $data['dateTo'];

        $dueDateFrom = $data['dueDateFrom'];
        $dueDateTo = $data['dueDateTo'];

        $pickup = $this->pickup
            ->whereNotNull('pickup_plan_id')
            // ->whereHas('proofOfDelivery', function($q) {
            //     $q->where('status_delivery', 'success');
            // })
            ->with(['user','sender','receiver','debtor','cost.extraCosts','branch','proofOfPickup']);

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
                case 'number':
                    $pickup = $pickup->sortable([
                        'number' => $order
                    ]);
                    break;
                case 'name':
                    $pickup = $pickup->sortable([
                        'name' => $order
                    ]);
                    break;
                case 'receiver.name':
                    $pickup = $pickup->sortable([
                        'receiver.name' => $order
                    ]);
                    break;
                case 'debtor.name':
                    $pickup = $pickup->sortable([
                        'debtor.name' => $order
                    ]);
                    break;
                case 'cost.method':
                    $pickup = $pickup->sortable([
                        'cost.method' => $order
                    ]);
                    break;
                case 'branch.name':
                    $pickup = $pickup->sortable([
                        'branch.name' => $order
                    ]);
                    break;
                case 'created_at':
                    $pickup = $pickup->sortable([
                        'created_at' => $order
                    ]);
                    break;
                default:
                    $pickup = $pickup->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($name)) {
            $pickup = $pickup->where('name', 'ilike', '%'.$name.'%');
        }

        if (!empty($number)) {
            $pickup = $pickup->where('number', 'ilike', '%'.$number.'%');
        }

        if (!empty($receiver)) {
            $pickup = $pickup->whereHas('receiver', function($q) use ($receiver) {
                $q->where('name', 'ilike', '%'.$receiver.'%');
            });
        }

        if (!empty($debtor)) {
            $pickup = $pickup->whereHas('debtor', function($q) use ($debtor) {
                $q->where('name', 'ilike', '%'.$debtor.'%');
            });
        }

        if (!empty($paymentMethod)) {
            $pickup = $pickup->whereHas('cost', function($q) use ($paymentMethod) {
                $q->where('method', 'ilike', '%'.$paymentMethod.'%');
            });
        }

        if (!empty($dateFrom) && !empty($dateTo)) {
            $pickup = $pickup
                ->whereDate('created_at', '>=', date($dateFrom))
                ->whereDate('created_at', '<=', date($dateTo));
        }

        /**
         * date : YYYY-MM-DD
         */
        if (!empty($dueDateFrom) && !empty($dueDateTo)) {
            $pickup = $pickup->whereHas('cost', function($q) use ($dueDateFrom, $dueDateTo) {
                $q->where('method', 'ilike', '%tempo%')->whereDate('due_date', '>=', date($dueDateFrom))
                ->whereDate('due_date', '<=', date($dueDateTo));
            });
        }

        if (!empty($branchName)) {
            $pickup = $pickup->whereHas('branch', function($q) use ($branchName) {
                $q->where('name', 'ilike', '%'.$branchName.'%');
            });
        }

        $result = $pickup->paginate($perPage);

        return $result;
    }

    /**
     * update marketing on order
     */
    public function updateMarketingByOrderId($orderId, $marketingId)
    {
        $pickup = $this->pickup->find($orderId);
        if (!$pickup) {
            throw new InvalidArgumentException('Order tidak ditemukan');
        }
        $pickup->marketing_id = $marketingId;
        $pickup->save();
        return $pickup;
    }

    /**
     * update fleet data
     */
    public function updateFleetDataPickupRepo($data = [])
    {
        $pickup = $this->pickup->find($data['pickupId']);
        if (!$pickup) {
            throw new InvalidArgumentException('Pickup tidak ditemukan');
        }
        $pickup->fleet_name = $data['fleetName'];
        $pickup->fleet_departure = $data['fleetDeparture'];
        $pickup->save();
        return $pickup;
    }

    /**
     * get printed pickup order
     */
    public function getPrintedPickupRepo($pickupNumber)
    {
        $pickup = $this->pickup->where('number', $pickupNumber)->with(['sender','receiver','items','shipmentPlan.vehicle','shipmentPlan.driver'])->first();
        if (!$pickup || $pickup == null) {
            throw new InvalidArgumentException('Pickup tidak ditemukan');
        }
        return $pickup;
    }

    /**
     * mendapatkan data total order terbuat
     * @param array $data
     */
    public function getTotalOrderCreatedRepo($data = [])
    {
        $result = $this->pickup
            ->whereDate('created_at', '>=', Carbon::parse($data['startDate'])->toDateTimeString())
            ->whereDate('created_at', '<=', Carbon::parse($data['endDate'])->toDateTimeString())
            ->whereIn('branch_id', $data['branch'])
            ->count();
        return $result;
    }

    /**
     * mendapatkan data total order yand dibatalkan
     * @param array $data
     */
    public function getTotalOrderCanceledRepo($data = [])
    {
        $result = $this->pickup
            ->whereDate('created_at', '>=', Carbon::parse($data['startDate'])->toDateTimeString())
            ->whereDate('created_at', '<=', Carbon::parse($data['endDate'])->toDateTimeString())
            ->whereIn('branch_id', $data['branch'])
            ->where('status', 'canceled')
            ->count();
        return $result;
    }

    /**
     * cancel pickup by admin
     * @param array $data
     */
    public function cancelPickupRepo($data = [])
    {
        $pickup = $this->pickup->find($data['pickupId']);
        if ($pickup->shipment_plan_id !== null) {
            throw new InvalidArgumentException('Pickup order tidak dapat dibatalkan, karena shipment plan sudah terbuat');
        }
        if ($pickup->pickup_plan_id !== null) {
            throw new InvalidArgumentException('Pickup order tidak dapat dibatalkan, karena pickup plan sudah terbuat');
        }
        $pickup->updated_by         = $data['userId'];
        $pickup->status             = 'canceled';
        $pickup->save();
        return $pickup;
    }

    /**
     * cancel pickup plan from order
     */
    public function cancelPickupPlanFromOrderRepo($pickupPlanId)
    {
        return $this->pickup->where('pickup_plan_id', $pickupPlanId)->update(['pickup_plan_id' => null, 'status' => 'request']);
    }

    /**
     * mendapatkan data kinerja armada
     * @param array $data
     */
    public function getFleetPerformanceRepo($data = [])
    {
        $result = $this->pickup
            ->whereDate('created_at', '>=', Carbon::parse($data['startDate'])->toDateTimeString())
            ->whereDate('created_at', '<=', Carbon::parse($data['endDate'])->toDateTimeString())
            ->whereIn('branch_id', $data['branch'])
            ->whereHas('proofOfPickup', function($q) {
                $q->select('id')->where('status', 'applied');
            })
            ->where('status', '!=', 'canceled')
            ->select('fleet_id')
            ->get()
            ->countBy(function($q) {
                return $q->fleet_id;
            })
            ->map(function($val, $key) {
                $fleet = $this->fleet->select('type')->where('id', $key)->first();
                return [
                    'fleet_type' => $fleet->type,
                    'total' => $val
                ];
            })->values();
        return $result;
    }

    /**
     * mendapatkan data order per bulan
     * @param array $data
     */
    public function getOrderPerMonthRepo($data = [])
    {
        $pickup = $this->pickup
            ->select('created_at')
            ->whereDate('created_at', '>=', Carbon::now('Asia/Jakarta')->subMonths(12)->toDateTimeString())
            ->whereDate('created_at', '<=', Carbon::now('Asia/Jakarta')->toDateTimeString())
            ->whereIn('branch_id', $data['branch'])
            ->orderBy('created_at', 'ASC')
            ->get()
            ->groupBy(function ($val) {
                return Carbon::parse($val->created_at)->format('M');
            })->map(function ($item, $key) {
                return collect($item)->count();
            });
        $month = $pickup->map(function($item, $key) {
            return $key;
        })->values();
        $count = $pickup->map(function($item, $key) {
            return $item;
        })->values();
        $result = [
            'categories' => $month,
            'series' => $count
        ];
        return $result;
    }

    /**
     * check pickup have pop by pickup plan
     */
    public function checkPickupHavePOPByPickupPlan($pickupPlanId)
    {
        $pickups = $this->pickup->where('pickup_plan_id', $pickupPlanId)->with('proofOfPickup')->get();
        foreach ($pickups as $key => $value) {
            if ($value->proofOfPickup !== null) {
                throw new InvalidArgumentException('Maaf, ada order sudah masuk proof of pickup, sehingga tidak dapat dibatalkan');
            }
        }
    }

    /**
     * get pickup by shipment plan, minified
     */
    public function getPickupByShipmentPlanMinifyRepo($shipmentPlanId)
    {
        $pickup = $this->pickup->select('id')->where('shipment_plan_id', $shipmentPlanId)->get();
        return $pickup;
    }

    /**
     * check pickup have pop by pickup id
     */
    public function checkPickupHavePOPByPickup($pickupId)
    {
        $pickup = $this->pickup->where('id', $pickupId)->with('proofOfPickup')->first();
        if ($pickup->proof_of_pickup !== null) {
            throw new InvalidArgumentException('Maaf, ada order yang sudah masuk proof of pickup, sehingga tidak dapat dibatalkan / dihapus / diubah');
        }
    }

    /**
     * check pickup have pod by pickup id
     */
    public function checkPickupHavePODByPickup($pickupId)
    {
        $pickup = $this->pickup->where('id', $pickupId)->with('proofOfDelivery')->first();
        if ($pickup->proof_of_delivery !== null || $pickup->proofOfDelivery !== null) {
            throw new InvalidArgumentException('Maaf, ada order yang sudah masuk proof of delivery, sehingga tidak dapat dibatalkan / dihapus / diubah / disubmit');
        }
    }

    /**
     * check pickup have shipment plan by pickup id
     */
    public function checkPickupHaveShipmentPlanByPickup($pickupId)
    {
        $pickup = $this->pickup->find($pickupId);
        if (!$pickup) {
            throw new InvalidArgumentException('Maaf, order tidak ditemukan');
        }
        if ($pickup->shipment_plan_id !== null) {
            throw new InvalidArgumentException('Maaf, ada order sudah masuk shipment plan, sehingga tidak dapat dibatalkan / dihapus / diubah');
        }
    }

    /**
     * get pickup by number
     */
    public function getPickupByNumberRepo($number)
    {
        $pickup = $this->pickup->where('number', $number)->first();
        return $pickup;
    }

    /**
     * check pickup have submitted incoming order by pickup id
     */
    public function checkPickupHaveSubmittedIncomingByPickup($pickupId)
    {
        $pickup = $this->pickup->find($pickupId);
        if (!$pickup) {
            throw new InvalidArgumentException("Maaf, order dengan id $pickupId tidak ditemukan");
        }
        if ($pickup->transit !== null) {
            if ($pickup->transit->status !== 'pending') {
                throw new InvalidArgumentException("Maaf, order dengan nomor $pickup->number sudah masuk submitted incoming order, sehingga shipment plan tidak dapat dibatalkan / dihapus");
            }
        }
    }

    /**
     * update pickup status to request
     */
    public function updatePickupToRequestRepo($popId)
    {
        return $this->pickup->whereHas('proofOfPickup', function($q) use ($popId) {
            $q->where('id', $popId);
        })->update(['status' => 'request']);
    }

    /**
     * get pickup by id
     */
    public function getById($id)
    {
        $pickup = $this->pickup->find($id);
        if (!$pickup) {
            throw new InvalidArgumentException("Maaf, order dengan id $id tidak ditemukan");
        }
        return $pickup;
    }

    /**
     * get data pickup state
     */
    public function getPickupStateRepo($pickupId)
    {
        $pickup = $this->getById($pickupId);
        if ($pickup->status == 'canceled') {
            throw new InvalidArgumentException("Maaf, order dengan nomor $pickup->number sudah dibatalkan");
        }
        $result = ['state' => 'Pengajuan Order', 'status' => $pickup->status];
        if ($pickup->pickupPlan) {
            $result['state'] = 'Pickup Plan';
            $result['status'] = $pickup->pickupPlan->status;
        }
        if ($pickup->proofOfPickup) {
            if ($pickup->proofOfPickups) {
                $pop = collect($pickup->proofOfPickups)->sortBy('id')->values()->last();
                $result['state'] = 'Proof of Pickup';
                $result['status'] = $pop->status;
            };
        }
        if ($pickup->shipmentPlan) {
            $result['state'] = 'Shipment Plan';
            $result['status'] = $pickup->shipmentPlan->status;
        }
        if ($pickup->transit) {
            if ($pickup->transits) {
                $transit = collect($pickup->transits)->sortBy('id')->values()->last();
                if ($transit->received) {
                    $result['state'] = 'Incoming Order';
                    $result['status'] = $transit->status;
                }
            };
        }
        if ($pickup->proofOfDelivery) {
            if ($pickup->proofOfDeliveries) {
                $pod = collect($pickup->proofOfDeliveries)->sortBy('id')->values()->last();
                $result['state'] = 'Proof of Delivery';
                $result['status'] = $pod->status;
            };
        }
        return $result;
    }

    /**
     * get printed finance order
     */
    public function getPrintedFinancePickupRepo($pickupNumber)
    {
        $pickup = $this->pickup->where('number', $pickupNumber)->with(['debtor','items','items.service','cost','fleet'])->first();
        if (!$pickup || $pickup == null) {
            throw new InvalidArgumentException('Pickup tidak ditemukan');
        }
        return $pickup;
    }
}

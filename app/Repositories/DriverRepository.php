<?php

namespace App\Repositories;

// MODEL
use App\Models\Driver;
use App\Models\User;
use App\Models\PickupPlan;
use App\Models\ProofOfPickup;
use App\Models\Pickup;
use App\Models\ShipmentPlan;
use App\Models\ProofOfDelivery;

// VENDOR
use Carbon\Carbon;
use InvalidArgumentException;

class DriverRepository
{
    protected $driver;
    protected $user;
    protected $pickupPlan;
    protected $pop;
    protected $pickup;
    protected $shipmentPlan;
    protected $pod;

    public function __construct(
        Driver $driver,
        User $user,
        PickupPlan $pickupPlan,
        ProofOfPickup $pop,
        Pickup $pickup,
        ShipmentPlan $shipmentPlan,
        ProofOfDelivery $pod
    )
    {
        $this->driver = $driver;
        $this->user = $user;
        $this->pickupPlan = $pickupPlan;
        $this->pop = $pop;
        $this->pickup = $pickup;
        $this->shipmentPlan = $shipmentPlan;
        $this->pod = $pod;
    }

    /**
     * Get Driver by vehicle
     *
     * @param array $data
     * @return Driver
     */
    public function getAvailableDriverByVehicleRepo($data)
    {
        $data = $this->driver->with('user')->where('status', 'available')->whereHas('vehicles', function($q) use ($data) {
            $q->where('id', $data);
        })->get();
        return $data;
    }

    /**
     * Get Driver by id
     *
     * @param int $data
     * @return Driver
     */
    public function getDriverById($id)
    {
        $data = $this->driver->find($id);
        if (!$data) {
            throw new InvalidArgumentException('Driver tidak ditemukan');
        }
        return $data;
    }

    /**
     * Get available driver by name
     *
     * @param string $data
     * @return Driver
     */
    public function getAvailableDriverByNameRepo($data)
    {
        $data = $this->driver->with('user')->where('status', 'available')->whereHas('user', function($q) use ($data) {
            $q->where('name', 'ilike', '%'.$data.'%');
        })->get();
        return $data;
    }

    /**
     * Get all driver paginate
     *
     * @param $pickupId
     * @return mixed
     */
    public function getAllPaginateRepo($data = [])
    {
        $perPage = $data['perPage'];
        $sort = $data['sort'];
        $page = $data['page'];
        $id = $data['id'];
        $active = $data['active'];
        $status = $data['status'];
        $type = $data['type'];
        $name = $data['name'];
        $email = $data['email'];
        $branch = $data['branch'];
        $phone = $data['phone'];

        $driver = $this->driver->with(['user', 'user.address', 'user.branch']);

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
                case 'id':
                    $driver = $driver->sortable([
                        'id' => $order
                    ]);
                    break;
                case 'active':
                    $driver = $driver->sortable([
                        'active' => $order
                    ]);
                    break;
                case 'type':
                    $driver = $driver->sortable([
                        'type' => $order
                    ]);
                    break;
                case 'status':
                    $driver = $driver->sortable([
                        'status' => $order
                    ]);
                    break;
                case 'user.name':
                    $driver = $driver->sortable([
                        'user.name' => $order
                    ]);
                    break;
                case 'user.phone':
                    $driver = $driver->sortable([
                        'user.phone' => $order
                    ]);
                    break;
                default:
                    $driver = $driver->sortable([
                        'id' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($id)) {
            $driver = $driver->where('id', 'ilike', '%'.$id.'%');
        }

        if (!empty($type)) {
            $driver = $driver->where('type', 'ilike', '%'.$type.'%');
        }

        if (!empty($active)) {
            $driver = $driver->where('active', 'ilike', '%'.$active.'%');
        }

        if (!empty($status)) {
            $driver = $driver->where('status', 'ilike', '%'.$status.'%');
        }

        if (!empty($name)) {
            $driver = $driver->whereHas('user', function($q) use ($name) {
                $q->where('name', 'ilike', '%'.$name.'%');
            });
        }

        if (!empty($email)) {
            $driver = $driver->whereHas('user', function($q) use ($email) {
                $q->where('email', 'ilike', '%'.$email.'%');
            });
        }

        if (!empty($phone)) {
            $driver = $driver->whereHas('user', function($q) use ($phone) {
                $q->where('phone', 'ilike', '%'.$phone.'%');
            });
        }

        if (!empty($branch)) {
            $driver = $driver->whereHas('user', function($q) use ($branch) {
                $q->whereHas('branch', function($x) use ($branch) {
                    $x->where('name', 'ilike', '%'.$branch.'%');
                });
            });
        }

        $driver = $driver->paginate($perPage);

        // $driver = $this->driver->sortable(['created_at' => 'desc'])->simplePaginate($perPage);
        return $driver;
    }

    /**
     * edit driver
     *
     * @param array $data
     * @return mixed
     */
    public function editDriverRepo($data = [])
    {
        $driver = $this->driver->find($data['id']);
        if (!$driver) {
            throw new InvalidArgumentException('Driver tidak ditemukan');
        }
        $driver->active = $data['active'];
        $driver->type = $data['type'];
        $driver->save();
        return $driver;
    }

    public function createDriverRepo($data = [], $userId)
    {
        $driver = new $this->driver;
        $driver->type = $data['type'];
        $driver->status = 'available';
        $driver->user_id = $userId;
        $driver->save();
        return $driver;
    }

    public function disableDriverRepo($data = [])
    {
        $driver = $this->driver->find($data['driverId']);
        if (!$driver) {
            throw new InvalidArgumentException('Driver tidak ditemukan');
        }
        if ($driver->status == 'available') {
            $driver->active = false;
            $driver->save();
            return $driver->fresh();
        } else {
            throw new InvalidArgumentException('Driver tidak dapat dinonaktifkan, karena sedang bertugas');
        }
    }

    /**
     * Get All Driver by name
     *
     * @param string $data
     * @return Driver
     */
    public function getAllDriverByNameRepo($data)
    {
        $data = $this->driver->with('user')->whereHas('user', function($q) use ($data) {
            $q->where('name', 'ilike', '%'.$data.'%');
        })->get();
        return $data;
    }

    /**
     * get default driver list
     */
    public function getDefaultDriversRepo()
    {
        $data = $this->driver->with('user')->get()->take(10);
        return $data;
    }

    /**
     * unassign driver after finish pickup plan
     */
    public function unassignDriverPickupPlan($pickupId)
    {
        $pickup = $this->pickup->find($pickupId);
        $pickupPlan = $pickup->pickupPlan;
        $pickupPlanId = $pickupPlan->id;
        $pickupsId = collect($pickupPlan->pickups)->pluck('id');
        $totalPickupOnPickupPlan = $pickupsId->count();
        $pickupHavePOP = $this->pop->whereIn('pickup_id', $pickupsId)->count();
        // jika jumlah order di pickup plan sama dengan jumlah order yang mempunyai POP / semua order di pickup plan sudah masuk pop
        if ($pickupHavePOP == $totalPickupOnPickupPlan) {
            // maka set status driver menjadi tersedia
            $driverId = $pickupPlan->driver_id;
            if ($driverId !== 0) {
                $driver = $this->getDriverById($driverId);
                $driver->status = 'available';
                $driver->save();
            }
        }
    }

    /**
     * unassign driver after finish shipment plan
     */
    public function unassignDriverShipmentPlan($pickupId)
    {
        $pickup = $this->pickup->find($pickupId);
        $shipmentPlan = $pickup->shipmentPlan;
        $shipmentPlanId = $shipmentPlan->id;
        $pickupsId = collect($shipmentPlan->pickups)->pluck('id');
        $totalPickupOnShipmentPlan = $pickupsId->count();
        $pickupHavePOD = $this->pod->whereIn('pickup_id', $pickupsId)->where('status', 'applied')->where('status_delivery', '!=', 're-delivery')->count();
        // jika jumlah order di shipment plan sama dengan jumlah order yang mempunyai POD applien dan bukan re-delivery / semua order di shipment plan sudah masuk pod applied dan bukan re-delivery
        if ($pickupHavePOD == $totalPickupOnShipmentPlan) {
            // maka set status driver menjadi tersedia
            $driverId = $shipmentPlan->driver_id;
            if ($driverId !== 0) {
                $driver = $this->getDriverById($driverId);
                $driver->status = 'available';
                $driver->save();
            }
        }
    }
}

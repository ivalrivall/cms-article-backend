<?php

namespace App\Repositories;

use App\Models\Transit;
use App\Models\Pickup;

use Carbon\Carbon;
use InvalidArgumentException;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class TransitRepository
{
    protected $transit;
    protected $pickup;

    public function __construct(Transit $transit, Pickup $pickup)
    {
        $this->transit = $transit;
        $this->pickup = $pickup;
    }

    /**
     * save transit
     *
     * @param array $data
     * @return Transit
     */
    public function saveTransitRepo($data = [])
    {
        $config = [
            'table' => 'transits',
            'length' => 12,
            'field' => 'number',
            'prefix' => 'T'.Carbon::now('Asia/Jakarta')->format('ymd'),
            'reset_on_prefix_change' => true
        ];
        $transit = new $this->transit;
        $transit->pickup_id = $data['pickupId'];
        $transit->status = $data['status'];
        $transit->received = $data['received'];
        $transit->notes = $data['notes'];
        $transit->created_by = $data['userId'];
        $transit->updated_by = $data['userId'];
        $transit->number = IdGenerator::generate($config);
        $transit->vehicle_id = $data['vehicleId'];
        $transit->driver_id = $data['driverId'];
        $transit->save();
        return $transit;
    }

    /**
     * get pending and draft transit pickup
     * Counter dashboard ini menampilkan jumlah ada berapa pickup order yang masih pending transit
     *      (belum di pickup tapi sudah di transit)
     *      dan menampilkan jumlah pickup order yang statusnya
     *      DRAFT (pickup order yang sudah di pickup
     *      dan di update via apps driver oleh driver)
     */
    public function getPendingAndDraftRepo($branchId)
    {
        $transits = collect($this->transit->whereHas('pickup', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->get());
        $pending = $transits->where('status', 'pending')->count();
        $draft = $transits->where('status', 'draft')->count();
        $data = [
            'pending' => $pending,
            'draft' => $draft
        ];
        return $data;
    }

    /**
     * get outstanding transit pickup
     * status only pending and received false
     * @param array $data
     */
    public function getOutstandingPickupRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $sort = $data['sort'];
        $customer = $data['customer'];
        $general = $data['general'];
        $pickupOrderNo = $data['pickupOrderNo'];
        $transitNumber = $data['transitNumber'];
        $branchId = $data['branchId'];
        $vehicleNumber = $data['vehicleNumber'];
        $senderCity = $data['senderCity'];
        $receiverCity = $data['receiverCity'];

        $transit = $this->transit->with(['pickup' => function($q) {
            $q->select('id','sender_id','receiver_id','number','name');
        },'pickup.sender' => function($q) {
            $q->select('id','city');
        },'pickup.receiver' => function($q) {
            $q->select('id','province','city','district','village','street','notes','postal_code','name','phone');
        },'vehicle'])->where('transits.status', 'pending')->where('received', false)->whereHas('pickup', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->where('reshipment', false);

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
                case 'pickup.name':
                    $transit = $transit->sortable([
                        'pickup.name' => $order
                    ]);
                    break;
                case 'pickup.number':
                    $transit = $transit->sortable([
                        'pickup.number' => $order
                    ]);
                    break;
                case 'number':
                    $transit = $transit->sortable([
                        'number' => $order
                    ]);
                    break;
                case 'created_at':
                    $transit = $transit->sortable([
                        'created_at' => $order
                    ]);
                    break;
                case 'vehicle.license_plate':
                    $transit = $transit->sortable([
                        'vehicle.license_plate' => $order
                    ]);
                    break;
                default:
                    $transit = $transit->sortable([
                        'updated_at' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($customer)) {
            $transit = $transit->whereHas('pickup', function($q) use ($customer) {
                $q->where('name', 'ilike', '%'.$customer.'%');
            });
        }

        if (!empty($transitNumber)) {
            $transit = $transit->where('number', 'ilike', '%'.$transitNumber.'%');
        }

        if (!empty($pickupOrderNo)) {
            $transit = $transit->whereHas('pickup', function($q) use ($pickupOrderNo) {
                $q->where('number', 'ilike', '%'.$pickupOrderNo.'%');
            });
        }

        if (!empty($general)) {
            $transit = $transit
                ->where('number', 'ilike', '%'.$general.'%')
                ->orWhereHas('pickup', function($q) use ($general) {
                    $q->where('name', 'ilike', '%'.$general.'%');
                })
                ->orWhereHas('pickup', function($q) use ($general) {
                    $q->where('number', 'ilike', '%'.$general.'%');
                });
        }

        if (!empty($vehicleNumber)) {
            $transit = $transit->whereHas('vehicle', function($q) use ($vehicleNumber) {
                $q->where('license_plate', 'ilike', '%'.$vehicleNumber.'%');
            });
        }

        if (!empty($senderCity)) {
            $transit = $transit->whereHas('pickup', function($q) use ($senderCity) {
                $q->whereHas('sender', function($q) use ($senderCity) {
                    $q->where('city', 'ilike', '%'.$senderCity.'%');
                });
            });
        }

        if (!empty($receiverCity)) {
            $transit = $transit->whereHas('pickup', function($q) use ($receiverCity) {
                $q->whereHas('receiver', function($q) use ($receiverCity) {
                    $q->where('city', 'ilike', '%'.$receiverCity.'%');
                });
            });
        }

        $result = $transit->paginate($perPage);

        return $result;
    }

     /**
     * submit transit
     *
     * @param array $data
     * @return Tracking
     */
    public function submitTransitRepo($data = [])
    {
        $transit = $this->transit->find($data['transitId']);
        $transit->status = $data['status'];
        $transit->received = $data['received'];
        $transit->notes = $data['notes'];
        $transit->updated_by = $data['userId'];
        $transit->status_transit = $data['statusTransit'];
        $transit->failed_notes = $data['failedNotes'];
        $transit->save();
        return $transit;
    }

    /**
     * get submitted transit
     * @param array $data
     */
    public function getSubmittedPickupRepo($data = [])
    {
        $perPage = $data['perPage'];
        $page = $data['page'];
        $sort = $data['sort'];
        $customer = $data['customer'];
        $general = $data['general'];
        $pickupOrderNo = $data['pickupOrderNo'];
        $transitNumber = $data['transitNumber'];
        $branchId = $data['branchId'];
        $vehicleNumber = $data['vehicleNumber'];
        $senderCity = $data['senderCity'];
        $receiverCity = $data['receiverCity'];

        $transit = $this->transit->whereHas('pickup', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->with(['pickup' => function($q) {
            $q->select('id','number','receiver_id','sender_id','name');
        },'pickup.receiver' => function($q) {
            $q->select('id','province','city','district','village','street','notes','postal_code','name','phone');
        },'pickup.sender' => function($q) {
            $q->select('id','city');
        }, 'vehicle'])
            ->where('received', true)
            ->where('reshipment', false)
            ->where('transits.status', 'draft')->orWhere('transits.status', 'applied');

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
                case 'pickup.name':
                    $transit = $transit->sortable([
                        'pickup.name' => $order
                    ]);
                    break;
                case 'pickup.number':
                    $transit = $transit->sortable([
                        'pickup.number' => $order
                    ]);
                    break;
                case 'number':
                    $transit = $transit->sortable([
                        'number' => $order
                    ]);
                    break;
                case 'created_at':
                    $transit = $transit->sortable([
                        'created_at' => $order
                    ]);
                    break;
                default:
                    $transit = $transit->sortable([
                        'updated_at' => 'desc'
                    ]);
                    break;
            }
        }

        if (!empty($customer)) {
            $transit = $transit->whereHas('pickup', function($q) use ($customer) {
                $q->where('name', 'ilike', '%'.$customer.'%');
            });
        }

        if (!empty($transitNumber)) {
            $transit = $transit->where('number', 'ilike', '%'.$transitNumber.'%');
        }

        if (!empty($pickupOrderNo)) {
            $transit = $transit->whereHas('pickup', function($q) use ($pickupOrderNo) {
                $q->where('number', 'ilike', '%'.$pickupOrderNo.'%');
            });
        }

        if (!empty($general)) {
            $transit = $transit
                ->where('number', 'ilike', '%'.$general.'%')
                ->orWhereHas('pickup', function($q) use ($general) {
                    $q->where('name', 'ilike', '%'.$general.'%');
                })
                ->orWhereHas('pickup', function($q) use ($general) {
                    $q->where('number', 'ilike', '%'.$general.'%');
                });
        }

        if (!empty($vehicleNumber)) {
            $transit = $transit->whereHas('vehicle', function($q) use ($vehicleNumber) {
                $q->where('license_plate', 'ilike', '%'.$vehicleNumber.'%');
            });
        }

        if (!empty($senderCity)) {
            $transit = $transit->whereHas('pickup', function($q) use ($senderCity) {
                $q->whereHas('sender', function($q) use ($senderCity) {
                    $q->where('city', 'ilike', '%'.$senderCity.'%');
                });
            });
        }

        if (!empty($receiverCity)) {
            $transit = $transit->whereHas('pickup', function($q) use ($receiverCity) {
                $q->whereHas('receiver', function($q) use ($receiverCity) {
                    $q->where('city', 'ilike', '%'.$receiverCity.'%');
                });
            });
        }

        $result = $transit->paginate($perPage);

        return $result;
    }

    /**
     * update transit data
     * @param array $data
     */
    public function updateTransitRepo($data = [])
    {
        $transit = $this->transit->find($data['transitId']);
        $transit->status = $data['status'];
        $transit->updated_by = $data['userId'];
        $transit->status_transit = $data['statusTransit'];
        $transit->failed_notes = $data['failedNotes'];
        $transit->save();
        return $transit;
    }

    /**
     * update fleet data
     */
    public function updateFleetDataRepo($data = [])
    {
        $transit = $this->transit->find($data['transitId']);
        if (!$transit) {
            throw new InvalidArgumentException('Transit tidak ditemukan');
        }
        $transit->fleet_name = $data['fleetName'];
        $transit->fleet_departure = $data['fleetDeparture'];
        $transit->save();
        return $transit;
    }

    /**
     * update fleet data by pickup
     */
    public function updateFleetDataByPickupRepo($data = [])
    {
        $transit = $this->transit->where('pickup_id', $data['pickupId'])->update([
            'fleet_name' => $data['fleetName'],
            'fleet_departure' => $data['fleetDeparture']
        ]);
        return $transit;
    }

    /**
     * check transit have shipment
     */
    public function checkTransitHaveShipment($transitId)
    {
        $transit = $this->transit->find($transitId);
        if (!$transit) {
            throw new InvalidArgumentException('Transit tidak ditemukan');
        }
        $pickup = $this->pickup->with('shipmentPlan')->find($transit->pickup_id);
        if ($pickup->shipment_plan_id !== null) {
            throw new InvalidArgumentException('Transit ini sudah masuk shipment plan. tidak dapat diubah');
        }
    }

    /**
     * cancel transit order by order id
     */
    public function cancelTransitRepo($pickupId, $userId)
    {
        $pickup = $this->pickup->find($pickupId);
        if (!$pickup) {
            throw new InvalidArgumentException("Pickup dengan id $pickupId tidak ditemukan");
        }
        $transitData = $pickup->transit;
        if ($transitData !== null) {
            $transit = $this->transit->find($transitData['id']);
            $transit->deleted_by = $userId;
            $transit->save();
            $transit->delete();
            return $transit;
        }
        return $pickup;
    }

    /**
     * reshipment transit
     */
    public function reshipmentOrderRepo($pickupsId)
    {
        $transit = $this->transit->whereIn('pickup_id', $pickupsId)->update(['reshipment' => true]);
        return $transit;
    }

    /**
     * delete transit order by order id
     */
    public function deleteTransitRepo($pickupId, $userId)
    {
        $transit = $this->transit->where('pickup_id', $pickupId);
        if (!$transit->first()) {
            return true;
        }
        $transit->update(['deleted_by' => $userId]);
        $transit->delete();
        return $transit;
    }
}

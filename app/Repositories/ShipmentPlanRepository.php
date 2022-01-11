<?php

namespace App\Repositories;

use App\Models\ShipmentPlan;
use App\Models\Pickup;
use Carbon\Carbon;
use InvalidArgumentException;
use Haruncpi\LaravelIdGenerator\IdGenerator;
class ShipmentPlanRepository
{
    protected $shipmentPlan;
    protected $pickup;

    public function __construct(ShipmentPlan $shipmentPlan, Pickup $pickup)
    {
        $this->shipmentPlan = $shipmentPlan;
        $this->pickup = $pickup;
    }

    /**
     * save shipment plan
     *
     * @param array $data
     * @return ShipmentPlan
     */
    public function saveShipmentPlanRepo($data = [])
    {
        $config = [
            'table' => 'shipment_plans',
            'length' => 13,
            'field' => 'number',
            'prefix' => 'SP'.Carbon::now('Asia/Jakarta')->format('ymd'),
            'reset_on_prefix_change' => true
        ];
        $shipmentPlan = new $this->shipmentPlan;
        $shipmentPlan->status = 'applied'; // applied, canceled, draft, submitted
        $shipmentPlan->vehicle_id = $data['vehicleId'];
        $shipmentPlan->created_by = $data['userId'];
        $shipmentPlan->updated_by = $data['userId'];
        $shipmentPlan->number = IdGenerator::generate($config);
        $shipmentPlan->is_transit = $data['isTransit'];
        $shipmentPlan->branch_from = $data['branchFrom'];
        $shipmentPlan->driver_id = $data['driverId'];
        $shipmentPlan->save();
        foreach ($data['pickupId'] as $key => $value) {
            // $this->pickup->where('id', $value)->update(['pickup_plan_id' => $shipmentPlan->id]);
            $pickup = $this->pickup->find($value);
            $pickup->shipmentPlan()->associate($shipmentPlan);
            $pickup->save();
        }
        $shipmentPlan->fresh();
        return $shipmentPlan;
    }

    /**
     * delete pickup order on shipment plan
     *
     * @param array $data
     */
    public function deletePoRepo($data)
    {
        $shipmentPlan = $this->shipmentPlan->find($data['shipmentPlanId'])->pickups;
        if (count($shipmentPlan) <= 1) {
            throw new InvalidArgumentException('Maaf anda tidak bisa menghapus pickup order ini');
        }
        $shipmentPlan = $shipmentPlan->where('id', $data['pickupId'])->values();
        if (count($shipmentPlan) == 1) {
            $pickup = $this->pickup->where('id', $data['pickupId'])->where('shipment_plan_id', $data['shipmentPlanId'])->update([
                'shipment_plan_id' => null
            ]);
            return $pickup;
        }
        throw new InvalidArgumentException('Pickup order tidak ditemukan');
    }

    /**
     * add pickup order on shipment plan
     *
     * @param array $data
     */
    public function addPoRepo($data)
    {
        $shipmentPlan = $this->shipmentPlan->find($data['shipmentPlanId']);
        if (!$shipmentPlan) {
            throw new InvalidArgumentException('Maaf shipment plan tidak ditemukan');
        }
        $result = [];
        foreach ($data['pickupId'] as $key => $value) {
            $pickup = $this->pickup->find($value);
            $pickup->shipmentPlan()->associate($shipmentPlan);
            $pickup->save();
            $result[] = $pickup;
            // $this->pickup->where('id', $value)->update(['pickup_plan_id' => $shipmentPlan->id]);
        }
        return $result;
    }

    /**
     * delete pickup plan
     *
     * @param array $data
     */
    public function deletePickupPlanRepo($data = [])
    {
        $pickupPlan = $this->shipmentPlan->find($data['pickupPlanId']);
        if (!$pickupPlan) {
            throw new InvalidArgumentException('Maaf, pickup plan tidak ditemukan');
        }
        $pickup = $this->pickup->where('pickup_plan_id', $data['pickupPlanId'])->update([
            'pickup_plan_id' => null
        ]);
        if ($pickup) {
            $pickupPlan->deleted_by = $data['userId'];
            $pickupPlan->save();
            $pickupPlan->delete();
            return $pickupPlan;
        }
        throw new InvalidArgumentException('Maaf, pickup order yang ada di pickup plan tidak bisa dihapus');
    }

    /**
     * cancel shipment plan
     *
     * @param array $data
     */
    public function cancelShipmentPlanRepo($data = [])
    {
        $shipmentPlan = $this->shipmentPlan->find($data['shipmentPlanId']);
        if (!$shipmentPlan) {
            throw new InvalidArgumentException('Maaf, shipment plan tidak ditemukan');
        }
        $shipmentPlan->status = 'canceled';
        $shipmentPlan->updated_by = $data['userId'];
        $shipmentPlan->driver_id = null;
        $shipmentPlan->save();
        return $shipmentPlan;
    }

    /**
     * get shipment plan driver
     */
    public function getDriverShipmentPlanListRepo($data = [])
    {
        $userId = $data['userId'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        $shipmentPlan = $this->shipmentPlan
            ->with(['pickups' => function($q) {
                $q->where('is_transit', false);
            },'pickups.proofOfDeliveries'])
            ->whereHas('driver', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->where('is_transit', false);
        if (!empty($startDate) && !empty($endDate)) {
            $shipmentPlan = $shipmentPlan
                ->whereDate('created_at', '>=', date($startDate))
                ->whereDate('created_at', '<=', date($endDate));
        }
        $result = $shipmentPlan->get();
        return $result;
    }

    /**
     * get po in shipment plan driver
     */
    public function getPickupOrderDriverShipmentPlanListRepo($data = [])
    {
        $userId = $data['userId'];
        $shipmentPlanId = $data['shipmentPlanId'];
        $filter = $data['filter'];
        $pickup = $this->pickup
            ->whereNotNull('pickup_plan_id')
            ->where('shipment_plan_id', $shipmentPlanId)
            ->where('is_transit', false)
            ->with(['receiver','proofOfDelivery'])
            ->whereHas('shipmentPlan', function ($q) use ($userId) {
                $q->whereHas('driver', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            });
        if ($filter) {
            $pickup = $pickup->where(function($q) use ($filter) {
                $q->whereHas('receiver', function($q) use ($filter) {
                    $q->where('street' , 'ilike', '%'.$filter.'%')
                        ->orWhere('province', 'ilike', '%'.$filter.'%')
                        ->orWhere('name', 'ilike', '%'.$filter.'%')
                        ->orWhere('district', 'ilike', '%'.$filter.'%')
                        ->orWhere('village', 'ilike', '%'.$filter.'%')
                        ->orWhere('postal_code', 'ilike', '%'.$filter.'%')
                        ->orWhere('city', 'ilike', '%'.$filter.'%');
                })->orWhere('number', 'ilike', '%'.$filter.'%')->orWhere('name', 'ilike', '%'.$filter.'%');
            });
        }
        $result = $pickup->paginate(10);
        return $result;
    }

    /**
     * get dashboard shipment plan repo
     */
    public function getDashboardDriverRepo($shipmentPlanId)
    {
        $shipmentPlanPickup = $this->pickup->where('shipment_plan_id', $shipmentPlanId);
        $totalPickup = $shipmentPlanPickup->count();
        $capacity = $shipmentPlanPickup->with('items')->get()->pluck('items');
        $items = collect($capacity)->flatten()->toArray();
        $volume = array_sum(array_column($items, 'volume'));
        $weight = array_sum(array_column($items, 'weight'));
        $result = [
            'volume' => $volume,
            'weight' => $weight,
            'totalOrder' => $totalPickup
        ];
        return $result;
    }

    /**
     * get landing page dashboard for app driver
     */
    public function getDashboardDriverPODRepo($data = [])
    {
        $userId = $data['userId'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        if (!empty($startDate) && !empty($endDate)) {
            $shipmentPlan = $this->shipmentPlan
                ->whereDate('created_at', '>=', date($startDate))
                ->whereDate('created_at', '<=', date($endDate))
                ->with(['pickups.proofOfDelivery'])
                ->whereHas('driver', function($o) use ($userId) {
                    $o->where('user_id', $userId);
                })
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $shipmentPlan = $this->shipmentPlan
                ->with(['pickups.proofOfDelivery'])
                ->whereHas('driver', function($o) use ($userId) {
                    $o->where('user_id', $userId);
                })
                ->orderBy('created_at', 'desc')
                ->get();
        }
        $result = $shipmentPlan->map(function($q) {
            $totalDraftPOD = $this->pickup->where('shipment_plan_id', $q->id)->whereHas('proofOfDelivery', function ($q) {
                $q->where('status', 'draft')->where('status_delivery', 'success');
            })->count();
            $totalCancelledPOD = $this->pickup->where('shipment_plan_id', $q->id)->whereHas('proofOfDelivery', function ($q) {
                $q->where('status_delivery', 'failed');
            })->count();
            $data = [
                'created_at' => $q->created_at,
                'shipment_plan_number' => $q->number,
                'shipment_plan_id' => $q->id,
                'total_order' => $q->total_pickup_order,
                'total_draft_pod' => $totalDraftPOD,
                'total_cancelled_pod' => $totalCancelledPOD,
            ];
            return $data;
        });
        return $result;
    }

    /**
     * get pickup in shipment plan
     */
    public function getPickupListRepo($shipmentPlanNumber)
    {
        // $result = $this->shipmentPlan->with(['pickups.receiver','pickups.items','vehicle.driver.user'])->find($shipmentPlanId);
        $result = $this->shipmentPlan->with(['pickups.receiver','pickups.items','vehicle','driver.user'])->where('number', $shipmentPlanNumber)->first();
        return $result;
    }

    /**
     * update fleet data
     */
    public function updateFleetDataRepo($data = [])
    {
        $shipmentPlan = $this->shipmentPlan->find($data['shipmentPlanId']);
        if (!$shipmentPlan) {
            throw new InvalidArgumentException('Shipment Plan tidak ditemukan');
        }
        $shipmentPlan->fleet_name = $data['fleetName'];
        $shipmentPlan->fleet_departure = $data['fleetDeparture'];
        $shipmentPlan->save();
        return $shipmentPlan;
    }

    /**
     * remove order in shipment plan
     */
    public function removeOrderFromShipmentPlanRepo($pickupId)
    {
        $pickup = $this->pickup->find($pickupId);
        $shipmentPlanId = $pickup->shipment_plan_id;
        if (!$pickup) {
            throw new InvalidArgumentException('Maaf order tidak ditemukan');
        }
        $pickup->shipment_plan_id = null;
        $pickup->save();
        return [
            'pickup' => $pickup,
            'shipmentPlanId' => $shipmentPlanId
        ];
    }

    /**
     * delete shipment plan have zero order
     */
    public function deleteShipmentPlanHaveZeroOrder($shipmentPlanId, $deletorId)
    {
        $shipmentPlan = $this->shipmentPlan->with('pickups')->find($shipmentPlanId);
        if (!$shipmentPlan) {
            throw new InvalidArgumentException('Maaf shipment plan tidak ditemukan');
        }
        if (count($shipmentPlan->pickups) == 0) {
            $shipmentPlan->deleted_by = $deletorId;
            $shipmentPlan->save();
            $shipmentPlan->delete();
        }
        return $shipmentPlan;
    }

    /**
     * history shipment driver
     */
    public function getHistoryShipmentRepo($data = [])
    {
        $userId = $data['userId'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        $shipmentPlan = $this->shipmentPlan->with([
            'pickups' => function($q) {
                $q->select('id','shipment_plan_id','number','receiver_id');
            },
            'pickups.receiver' => function($q) {
                $q->select('id','name','phone','province','city','district','village','postal_code','street','notes');
            },
            'pickups.proofOfDelivery' => function($q) {
                $q->select('status','status_delivery','pickup_id');
            }
        ])
        ->whereHas('driver', function($d) use ($userId) {
            $d->where('user_id', $userId);
        })
        ->whereHas('pickups', function($q) {
            $q->whereHas('proofOfDelivery');
        })
        ->select('id','number');
        if ($startDate && $endDate) {
            $shipmentPlan = $shipmentPlan
                ->where('created_at', '>=', date($startDate))
                ->where('created_at', '<=', date($endDate));
        }
        $result = $shipmentPlan->simplePaginate(10);
        return $result;
    }

    /**
     * get shipment plan by number
     */
    public function getShipmentPlanByNumber($number)
    {
        $result = $this->shipmentPlan->where('number', $number)->first();
        return $result;
    }

    /**
     * get shipment plan by id
     */
    public function getShipmentPlanById($id)
    {
        $result = $this->shipmentPlan->find($id);
        if (!$result) {
            throw new InvalidArgumentException('Shipment plan tidak ditemukan');
        }
        return $result;
    }

    /**
     * check order on shipment have pod
     */
    public function checkOrderHavePOD($shipmentPlanId)
    {
        $shipmentPlan = $this->getShipmentPlanById($shipmentPlanId);
        $pickups = $shipmentPlan->pickups;
        foreach ($pickups as $key => $value) {
            $pickup = $this->pickup->find($value['id']);
            if ($pickup->proofOfDelivery !== null) {
                throw new InvalidArgumentException('Maaf, ada order yang sudah masuk proof of delivery, sehingga tidak dapat dibatalkan / dihapus / diubah / disubmit');
            }
        }
        return $shipmentPlan;
    }

    /**
     * check shipment plan have submitted transit
     */
    public function checkOrderHaveTransit($shipmentPlan)
    {
        // $shipmentPlan = $this->getShipmentPlanById($shipmentPlanId);
        $pickups = $shipmentPlan->pickups;
        if ($shipmentPlan->is_transit) {
            foreach ($pickups as $key => $value) {
                $pickup = $this->pickup->find($value['id']);
                if (!$pickup) {
                    throw new InvalidArgumentException('Maaf, order tidak ditemukan');
                }
                $transit = $pickup->transit;
                if ($transit !== null) {
                    if ($transit->status !== 'applied' && $transit->status_transit !== null) {
                        throw new InvalidArgumentException('Maaf, ada order yang sudah submit incoming order, sehingga tidak dapat dibatalkan / dihapus / diubah / disubmit');
                    }
                }
            }
        }
    }

    /**
     * delete shipment plan
     */
    public function deleteShipmentPlanRepo($shipmentPlanId, $deletorId)
    {
        $shipmentPlan = $this->shipmentPlan->with('pickups')->find($shipmentPlanId);
        if (!$shipmentPlan) {
            throw new InvalidArgumentException('Maaf shipment plan tidak ditemukan');
        }
        $shipmentPlan->deleted_by = $deletorId;
        $shipmentPlan->save();
        $shipmentPlan->delete();
        return $shipmentPlan;
    }
}

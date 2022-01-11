<?php

namespace App\Repositories;

use App\Models\Tracking;
use App\Models\PickupDriverLog;
use App\Models\Pickup;
use App\Models\Item;
use App\Models\Route;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use InvalidArgumentException;

use Carbon\Carbon;
use Intervention\Image\Facades\Image;

class TrackingRepository
{
    protected $tracking;
    protected $pickupDriverLog;
    protected $pickup;
    protected $item;
    protected $route;

    public function __construct(Tracking $tracking, PickupDriverLog $pickupDriverLog, Pickup $pickup, Item $item, Route $route)
    {
        $this->tracking = $tracking;
        $this->pickupDriverLog = $pickupDriverLog;
        $this->pickup = $pickup;
        $this->item = $item;
        $this->route = $route;
    }

    /**
     * Get tracking by pickup
     *
     * @param array $data
     * @return Tracking
     */
    public function getTrackingByPickupRepo($data)
    {
        $data = $this->tracking->where('pickup_id', $data['pickupId'])->orderBy('created_at', 'DESC')->get();
        if (count($data) <= 0) {
            return 'Data tracking belum tersedia';
        }
        return $data;
    }

    /**
     * Get tracking by pickup number
     */
    public function getTrackingByPickupNumberRepo($number)
    {
        $data = $this->pickup->with([
            'trackings' => function($q) {
                $q->orderBy('created_at', 'DESC');
            },
            'receiver' => function($q) {
                $q->select('id','city','district');
            },
            'sender' => function($q) {
                $q->select('id','city','district');
            },
            'pickupPlan' => function($q) {
                $q->select('id','created_at');
            }
        ])
        ->select('id','number','receiver_id','sender_id','pickup_plan_id','fleet_id')
        ->where('number','ilike','%'.$number.'%')->first();
        if (count($data['trackings']) <= 0) {
            return 'Data tracking belum tersedia';
        }
        return $data;
    }

    /**
     * record tracking by pickup
     *
     * @param array $data
     * @return Tracking
     */
    public function recordTrackingByPickupRepo($data)
    {
        $tracking = new $this->tracking;
        $tracking->pickup_id = $data['pickupId'];
        $tracking->docs = $data['docs'];
        $tracking->status = $data['status'];
        $tracking->notes = $data['notes'];
        $tracking->picture = $data['picture'] ?? null;
        $tracking->save();
        return $tracking;
    }

    /**
     * Upload tracking picture
     *
     * @param Request $request
     * @return array
     */
    public function uploadTrackingPicture($request)
    {
        $file                   = $request->file('picture');
        $tracking_extension     = $file->getClientOriginalExtension();
        $timestamp              = Carbon::now('Asia/Jakarta')->timestamp;
        $file_name              = 'tracking'.$timestamp.'.'.$tracking_extension;
        $tracking = Image::make($file->path());
        $tracking->resize(null, 400, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $folder = storage_path('app/public/upload/tracking/');
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $tracking = $tracking->save(storage_path('app/public/upload/tracking/').$file_name);
        // Storage::disk('storage_tracking')->put($file_name,  File::get($tracking));
        $tracking_url           = '/upload/tracking/'.$file_name;
        return [
            'base_url' => env('APP_URL').'/public/storage',
            'path' => $tracking_url
        ];
    }

    /**
     * record tracking POD
     */
    public function recordTrackingPOD($data = [])
    {
        $tracking = new $this->tracking;
        $tracking->pickup_id = $data['pickupId'];
        $tracking->docs = $data['docs'];
        $tracking->status = $data['status'];
        $tracking->notes = $data['notes'];
        $tracking->status_delivery = $data['statusDelivery'];
        $tracking->save();
        return $tracking;
    }

    /**
     * get total redelivery
     */
    public function getTotalRedelivery($data = [])
    {
        $result = $this->tracking
            ->where('docs', 'proof-of-delivery')
            ->where('status_delivery', 're-delivery')
            ->where('pickup_id', $data['pickupId'])
            ->count();
        return $result;
    }

    /**
     * record tracking POD driver
     */
    public function recordTrackingPODDriver($data = [])
    {
        $tracking = new $this->tracking;
        $tracking->pickup_id = $data['pickupId'];
        $tracking->docs = $data['docs'];
        $tracking->status = $data['status'];
        $tracking->notes = $data['notes'];
        $tracking->status_delivery = $data['statusDelivery'];
        $tracking->picture = $data['picture'];
        $tracking->save();
        return $tracking;
    }


    /**
     * record driver pickup log
     */
    public function recordPickupDriverLog($data = [])
    {
        $log = new $this->pickupDriverLog;
        $log->pickup_id = $data['pickupId'];
        $log->driver_id = $data['driverId'];
        $log->branch_from = $data['branchFrom'] ?? null;
        $log->branch_to = $data['branchTo'] ?? null;
        $log->save();
        return $log;
    }

    /**
     * get tracking picture
     */
    public function getTrackingPictureRepo($data = [])
    {
        $result = $this->tracking
            ->where('docs', $data['docs'])
            ->where('pickup_id', $data['pickupId'])
            ->whereNotNull('picture')
            ->orderBy('id','desc')
            ->get();
        return $result;
    }

    /**
     * search resi
     */
    public function searchResiRepo($number)
    {
        $pickup = $this->pickup->where('number', $number)->first();
        if (!$pickup) {
            throw new InvalidArgumentException('Nomor resi tidak ditemukan');
        }
        $result = $pickup->with(['sender' => function($q) {
            $q->select('id','province','city','district','village','postal_code','street','notes');
        },'receiver' => function($q) {
            $q->select('id','name','province','city','district','village','postal_code','street','notes');
        },'trackings'])->first();
        $items = $this->item->select('volume','weight')->where('pickup_id', $pickup->id)->get()->toArray();
        $totalWeight = array_sum(array_column($items, 'weight'));
        $totalVolume = array_sum(array_column($items, 'volume'));
        $route = $this->route->where([
            ['fleet_id', '=', $pickup->fleet_id],
            ['origin', '=', $pickup->sender->city],
            ['destination_district', '=', $pickup->receiver->district],
            ['destination_city', '=', $pickup->receiver->city],
        ])->first();
        // $itemWithZeroWeight = collect($items)->filter(function($q) {
        //     return $q['weight'] == 0;
        // })->values()->toArray();
        // $totalVolume = array_sum(array_column($itemWithZeroWeight, 'volume'));
        $result = [
            'pickup' => $result,
            'items' => [
                'volume' => $totalVolume,
                'weight' => $totalWeight
            ],
            'estimate' => $route->estimate
        ];
        return $result;
    }
}

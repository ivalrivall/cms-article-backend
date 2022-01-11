<?php

namespace App\Http\Controllers;

// OTHER
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;

// MODEL
use App\Models\User;
use App\Models\Role;
use App\Models\Fleet;
use App\Models\Route;
use App\Models\Promo;
use App\Models\Item;
use App\Models\Pickup;
use App\Models\PickupPlan;
use App\Models\ShipmentPlan;
use App\Models\ProofOfDelivery;
use App\Models\ProofOfPickup;
use App\Models\Cost;
use App\Models\VerifyUser;

// SERVICE
use App\Services\AddressService;
use App\Services\PickupService;
use App\Services\PromoService;
use App\Services\NotificationService;

// VENDOR
use Carbon\Carbon;
use Snowfire\Beautymail\Beautymail;
use Indonesia;
use Haruncpi\LaravelIdGenerator\IdGenerator;

// UTILITIES
use App\Utilities\RandomStringGenerator;

// MAIL
use App\Mail\VerifyMail;
use Illuminate\Support\Facades\Mail;

// NOTIFICATION
use App\Notifications\PromoUpdate;

class TestController extends BaseController
{
    protected $addressService;
    protected $pickupService;
    protected $promoService;
    protected $notifService;

    public function __construct(
        AddressService $addressService,
        PickupService $pickupService,
        PromoService $promoService,
        NotificationService $notifService
    )
    {
        $this->addressService = $addressService;
        $this->pickupService = $pickupService;
        $this->promoService = $promoService;
        $this->notifService = $notifService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $VerifyUser = VerifyUser::find(1);
        $otpMaxTime = Carbon::parse($VerifyUser->otp_expired_at)->toDateTimeString();
        return $otpMaxTime;
        $user = Cost::where('id',2)->first()->extraCosts;
        return $user;
        // $pickups = Pickup::where('pickup_plan_id', 1)->with('proofOfPickup')->get();
        // foreach ($pickups as $key => $value) {
        //     if ($value->proofOfPickup !== null) {
        //         throw new InvalidArgumentException('Maaf, ada order sudah masuk proof of pickup, sehingga tidak dapat dibatalkan');
        //     }
        // }
        // // $data = Item::with('routePrice')->find(139);
        // return response()->json($pickups);
        // $items = [
        //     [
        //         'min'    => true,
        //         'volume' => '10',
        //         'weight' => '20'
        //     ],
        //     [
        //         'min'    => true,
        //         'volume' => '10',
        //         'weight' => '5'
        //     ],
        //     [
        //         'min'    => true,
        //         'volume' => '10',
        //         'weight' => '0'
        //     ],
        //     [
        //         'min'    => false,
        //         'volume' => '20',
        //         'weight' => '0'
        //     ]
        // ];
        // if (in_array(false, array_column($items, 'min'))) {
        //     return 'pass';
        // } else {
        //     return 'minimum';
        // }
        // $itemWithZeroWeight = collect($items)->filter(function($q) {
        //     return $q['weight'] == 0;
        // })->values()->toArray();
        // $totalVolume = array_sum(array_column($itemWithZeroWeight, 'volume'));
        // $totaWeight = array_sum(array_column($items, 'weight'));
        // // for ($i=0; $i < count($items); $i++) {
        // //     if ($items[$i]['weight'] == 0) {
        // //     }
        // // }
        // $totalMinimum = $totalVolume + $totaWeight;
        // return response()->json($totalMinimum);
        // $carbon = Carbon::parse('2019-05-19 15:48:19')->diffInSeconds(Carbon::now('Asia/Jakarta'), false);
        // // if ($carbon > 30) {
        //     return 'aa => '.$carbon;
        // // }
        // // return 'bb => '.$carbon;
        // $data = Pickup::find(50);
        // $pop = $data->proofOfPickup;
        // $pop->updated_at = Carbon::now('Asia/Jakarta')->toDateTimeString();
        // $pop->save();
        // return response()->json($pop);
        // $items = collect($data)->flatten()->toArray();
        // // return $items;
        // $volume = array_sum(array_column($items, 'volume'));
        // $weight = array_sum(array_column($items, 'weight'));
        // $result = [
        //     'volume' => $volume,
        //     'weight' => $weight
        // ];
        // return response()->json($result);
        // $data = ProofOfDelivery::where('pickup_id', 40)->select('redelivery_count')->first();
        // if (!$data) {
        //     return response()->json('$data');
        // }
        // return response()->json($data->redelivery_count);

        // return Carbon::now('Asia/Jakarta')->format('ymd');
        // $existRoute = Route::where([
        //     ['origin','=','KOTA SURABAYA'],
        //     ['destination_island','=','SUMATERA'],
        //     ['destination_city','=','KOTA MEDAN'],
        //     ['destination_district','=','MEDAN ']
        // ])->first();
        // if ($existRoute) {
        //     return 'exist';
        // } else {
        //     return 'not exist';
        // }
        // return $existRoute;
        // $result = Indonesia::search('jakarta')->allCities();
        // return $result;
        // $armada = collect(Fleet::all());
        // $armada = $armada->where('slug','udara')->first()->only('id');
        // return $armada;
        // $route = Route::with('fleet')->get()->take(5)->makeHidden(['id','fleet_id']);
        // $route = $route->map(function($q) {
        //     $fleet = $q->fleet->slug;
        //     $q->fleet_slug = $fleet;
        //     return $q;
        // });
        // return $route;
        // $pickup = Pickup::select('picktime')->whereIn('id', [1,2,3,4])->get()->pluck('picktime');
        // $pickup = collect($pickup)->toArray();
        // $data = [];
        // foreach ($pickup as $key => $value) {
        //     $data[] = Carbon::parse($value)->format('Y-m-d');
        // }
        // return $data;
        // foreach ($pickup as $key => $value) {
        // if (count(array_unique($data)) === 1) {
        //     return 'sama';
        // }
        // }
        // return $pickup;

        // $allvalues = array('true', 'false', 'true');
        // if (count(array_unique($allvalues)) === 1 && end($allvalues) === 'true') {
        //     return 'sama';
        // }
        // return 'berbeda';

        // $user = User::find(1);
        // $user = [
        //     'user' => $user,
        //     'role' => $user->role()->get()
        // ];
        // return response()->json($user);
        // $data = Indonesia::allProvinces();
        $user = User::findOrFail(1);
        $beautymail = app()->make(Beautymail::class);
        $beautymail->send('emails.test', ['user' => $user], function($message) use ($user)
        {
            // dd($message);
            $message
                // ->from('ival@papandayan.com')
                ->from(env('MAIL_FROM_ADDRESS'))
                ->to($user->email, $user->name)
                ->subject('Selamat bergabung!');
        });

        // Mail::to($user)->send(new VerifyMail($user));
        // $data = User::find(1)->setAppends(['features','role'])->toArray();
        // $data = collect($data)->only('id', 'email', 'name','features','role')->all();
        return $this->sendResponse('data user', $user);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->title);

        $user = User::find(2);
        return $user->notify(new PromoUpdate);
        // return $this->notifService->sendNotifFCMService($request->all());
        $items = Item::all();
        foreach ($items as $key => $value) {
            if ($value['unit_id'] == 1 || $value['unit_id'] == 2) {
                $weight = $value['unit_total'] * 0.001;
                Item::where('id',$value['id'])->update(['weight' => $weight]);
            }
            if ($value['unit_id'] == 3) {
                $volume = $value['unit_total'] * 1000;
                Item::where('id',$value['id'])->update(['volume' => $volume]);
            }
        }
        return response()->json($items);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $config = [
            'table' => 'costs',
            'length' => 12,
            'field' => 'number',
            'prefix' => 'I'.Carbon::now('Asia/Jakarta')->format('ymd'),
            'reset_on_prefix_change' => true
        ];
        $collect = collect(Cost::all());
        $result = [];
        foreach ($collect as $key => $value) {
            $data = Cost::find($value['id']);
            $data->number = IdGenerator::generate($config);
            $result[] = $data->save();
        }
        // $collect = collect(User::all());
        // $result = [];
        // foreach ($collect as $key => $value) {
        //     $data = User::find($value['id']);
        //     if ($data['role_id'] == 2) {
        //         $customAlphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //         $generator = new RandomStringGenerator($customAlphabet);
        //         $generator->setAlphabet($customAlphabet);
        //         $refferal = $generator->generate(7);
        //         $data->refferal = $refferal;
        //         $result[] = $data->save();
        //     } else {
        //         $result[] = $data;
        //     }
        // }
        return response()->json($result);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getVersion()
    {
        return $this->sendResponse('version', env('VERSION', '0.0.1'));
        // return env('VERSION', '0.0.1');
    }

    public function sendFcm(Request $request)
    {
        // $url = 'https://fcm.googleapis.com/fcm/send';
        // $DeviceToken = User::whereNotNull('fcm')->pluck('fcm')->all();

        // $FcmKey = 'AAAAFa9OYWQ:APA91bFqRIi40m8kKTxBP80jKRWtKRBtNZT-Q-6M06DopXhtGtw4skFrD5SrST9o0RxS6SooKqJqdusfVcaZ5GaSqEkgg1ABhozQpG1d1PaT_YbjXy1AtFOR8cDH35YA8ua6t6xNC8Rf';

        // $data = [
        //     "registration_ids" => $DeviceToken,
        //     "notification" => [
        //         "title" => $request->title,
        //         "body" => $request->body,
        //     ]
        // ];

        // $RESPONSE = json_encode($data);

        // $headers = [
        //     'Authorization:key=' . $FcmKey,
        //     'Content-Type: application/json',
        // ];

        // // CURL
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $RESPONSE);

        // $output = curl_exec($ch);
        // if ($output === FALSE) {
        //     die('Curl error: ' . curl_error($ch));
        // }
        // curl_close($ch);
        // dd($output);
        try {
            $result = $this->notifService->sendNotifFCMService($request->all());
        } catch (Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage());
        }
        return $this->sendResponse('berhasil', $result);
    }
}

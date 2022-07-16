<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
// MODEL
use App\Models\User;
use App\Models\Role;
use App\Models\Fleet;
use App\Models\Route as RouteModel;
use App\Models\Promo;
use App\Models\Driver;
use App\Models\Branch;
use App\Models\Debtor;
use App\Models\Item;
// use App\Models\Pickup;
// use App\Models\PickupPlan;
use App\Models\VerifyUser;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/user/verify/{token}', function (Request $request) {
    $token = $request->token;
    // $verifyUser = VerifyUser::where('token', $request->token)->first();
    $user = User::whereHas('verifyUser', function($q) use ($token) {
        $q->where('token', $token);
    });
    \Log::info('token =>'. $request->token);
    if ($user->first() !== null) {
        $user->update(['email_verified_at' => Carbon::now('Asia/Jakarta')->toDateTimeString()]);
    } else {
        return view('verify', [
            'message' => 'Mohon maaf, alamat url tidak valid',
            'success' => false,
        ]);
    }
    return view('verify', [
        'message' => 'Selamat, email anda berhasil di verifikasi, silahkan klik tombol berikut untuk login',
        'success' => true,
        'role' => $user->first()->role->slug
    ]);
});

Route::get('/flush-file-cache', function() {
    Cache::store("file")->flush();
});

Route::get('/test', function() {
    $debtor = Debtor::find(1);
    $a = $debtor->replicate();
    $a->temporary = true;
    $debtor = $a->toArray();
    $debtor = Debtor::firstOrCreate($debtor);
    return $debtor;
    return $pickup['receiver']['city'];
    $branch = Branch::whereHas('pickups', function($q) {
        $q->where('id', 7);
    })->first();
    if (!$branch) {
        return response()->json('nulls');
    }
    return $branch['id'];
});

Route::get('/test-email', function()
{
	$beautymail = app()->make(Snowfire\Beautymail\Beautymail::class);
	$beautymail->send('emails.verify-email', [], function($message)
	{
		$message
			->from('ival@allstar.com')
			->to('ivalrival95@gmail.com', 'Ival')
			->subject('Welcome!');
	});

});

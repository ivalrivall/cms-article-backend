<?php

namespace App\Repositories;

// MODEL
use App\Models\User;
use App\Models\VerifyUser;

// OTHER
use App\Utilities\ProxyRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use DB;
use InvalidArgumentException;

// VENDOR
use Carbon\Carbon;
use Google_Client;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\RefreshTokenRepository;
class AuthRepository
{
    protected $user;
    protected $proxy;
    protected $verifyUser;

    public function __construct(User $user, VerifyUser $verifyUser, ProxyRequest $proxy)
    {
        $this->user = $user;
        $this->proxy = $proxy;
        $this->verifyUser = $verifyUser;
    }

    /**
     * get access token user login
     *
     * @param String $email
     * @param String $pass
     * @return object
     */
    public function getAccessToken($email, $pass)
    {
        // abort_unless($user, 404, 'This combination does not exists');
        // abort_unless(
        //     \Hash::check($request->password, $user->password),
        //     403,
        //     'This combination does not exists'
        // );
        $resp = $this->proxy->grantPasswordToken($email, $pass);
        $success = [
            'token' => $resp->access_token,
            'refreshToken' => $resp->refresh_token,
            'expiresIn' => Carbon::now()->addSecond($resp->expires_in)->toDateTimeString(),
        ];
        return $success;
    }

    /**
     * Refresh token
     *
     * @param String $refreshToken
     * @return mixed
     */
    public function refreshToken($refreshToken)
    {
        $resp = $this->proxy->refreshAccessToken($refreshToken);
        $success = [
            'token' => $resp->access_token,
            'refreshToken' => $resp->refresh_token,
            'expiresIn' => Carbon::now()->addSecond($resp->expires_in)->toDateTimeString(),
        ];
        return $success;
    }

    /**
     * Create Verify Email Token
     *
     * @param String $userId
     * @return VerifyUser
     */
    public function createVerifyUser($userId)
    {
        $twoDays = Carbon::now('Asia/Jakarta')->addHours(24)->toDateTimeString();
        $oneWeek = Carbon::now('Asia/Jakarta')->addDays(7)->toDateTimeString();
        $verify = new $this->verifyUser;
        $verify->user_id = $userId;
        $verify->token = sha1(time());
        $verify->save();
        return $verify;
    }

    /**
     * Revoke token
     *
     */
    public function revoke($tokenId)
    {
        $tokenRepository = app(TokenRepository::class);
        $refreshTokenRepository = app(RefreshTokenRepository::class);

        // Revoke an access token...
        $tokenRepository->revokeAccessToken($tokenId);

        // Revoke all of the token's refresh tokens...
        $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($tokenId);
    }

    /**
     * verify token google repo
     *
     * @param array $data
     */
    public function verifyTokenGoogleRepo($data = [])
    {
        $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($data['tokenId']);
        if ($payload) {
            return $payload;
        }
        throw new InvalidArgumentException('Verifikasi google tidak berhasil');
    }

    /**
     * update otp number
     */
    public function updateOTPUserByPhoneNumberRepo($phone)
    {
        $otp = rand(1111,9999);
        $twoDays = Carbon::now('Asia/Jakarta')->addHours(24)->toDateTimeString();
        $result = $this->verifyUser->whereHas('user', function($q) use ($phone) {
            $q->where('phone', $phone);
        })->update(['otp' => $otp,'otp_expired_at' => $twoDays]);
        return $result;
    }

    /**
     * verify OTP user
     */
    public function verifyOTPUserRepo($data)
    {
         // check OTP
        $verify = $this->verifyUser->where('user_id', $data['userId'])->first();
        if ($verify->otp !== $data['otp']) {
            throw new InvalidArgumentException('Kode OTP tidak valid');
            return;
        }
        $expired = Carbon::parse($verify->otp_expired_at)->diffInSeconds(Carbon::now('Asia/Jakarta'), false);
        if ($expired > 0) {
            $otpMaxTime = Carbon::parse($verify->otp_expired_at)->toDateTimeString();
            throw new InvalidArgumentException("Kode OTP hanya berlaku sebelum $otpMaxTime WIB");
            return;
        }

        // update otp expired on verified user
        $verify = $this->verifyUser->where('user_id', $data['userId'])->update(['otp_expired_at' => Carbon::now('Asia/Jakarta')->toDateTimeString()]);

        // update verified number phone
        $user = $this->user->find($data['userId']);
        $user->phone_verified_at = Carbon::now('Asia/Jakarta')->toDateTimeString();
        $user->save();
        return $user;
    }

    /**
     * check verified phone user
     */
    public function checkVerifiedPhoneUserRepo($phone)
    {
        $user = $this->user->where('phone', $phone)->first();
        if (!$user) {
            throw new InvalidArgumentException('Nomor handphone tidak ditemukan');
        }
        if ($user->phone_verified_at !== null) {
            throw new InvalidArgumentException('Nomor handphone telah terverifikasi');
        }
    }
}

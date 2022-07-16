<?php
namespace App\Services;

use App\Models\User;
use App\Repositories\AuthRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class AuthService {

    protected $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        $this->authRepository = $authRepository;
        $guzzleProp = ['base_uri' => 'https://api.smsviro.com', 'verify' => false];
        $this->client = new Client($guzzleProp);
    }

    /**
     * get access token.
     *
     * @param String $email
     * @param String $pass
     * @return String
     */
    public function getAccessToken($email, $pass)
    {
        DB::beginTransaction();
        try {
            $data = $this->authRepository->getAccessToken($email, $pass);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Unable to get access token');
        }
        DB::commit();
        return $data;
    }

    /**
     * Get all address.
     *
     * @param String $refreshToken
     * @return Mixed
     */
    public function refreshToken($refreshToken)
    {
        DB::beginTransaction();
        try {
            $data = $this->authRepository->refreshToken($refreshToken);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Unable to get refresh token');
        }
        DB::commit();
        return $data;
    }

    /**
     * create verify user.
     *
     * @param String $id
     * @return Mixed
     */
    public function createVerifyUser($id)
    {
        DB::beginTransaction();
        try {
            $data = $this->authRepository->createVerifyUser($id);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Unable to create verify user');
        }
        DB::commit();
        return $data;
    }

    /**
     * Revoke token
     */
    public function revoke($tokenId)
    {
        DB::beginTransaction();
        try {
            $data = $this->authRepository->revoke($tokenId);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Gagal revoke token');
        }
        DB::commit();
        return $data;
    }

    /**
     * verify token id google
     * @param array $data
    */
    public function verifyIdTokenService($data = [])
    {
        $validator = Validator::make($data, [
            'tokenId' => 'bail|required|string'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        // verify user or registered user
        DB::beginTransaction();
        try {
            $data = $this->authRepository->verifyTokenGoogleRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException($e->getMessage());
        }
        DB::commit();
        return $data;
    }

    /**
     * send OTP
     */
    public function sendOTP($data = [])
    {
        $validator = Validator::make($data, [
            'phone' => 'bail|required|string',
            'otp' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $txt = str_replace('%otp%', $data['otp'], env('SMS_VIRO_OTP_TEXT'));
            $formData = [
                'from' => 'ALLSTAR',
                'to' => $data['phone'],
                'text' => $txt
            ];
            $result = $this->client->request(
                'POST',
                'restapi/sms/1/text/single',
                [
                    'headers' => [
                        'Accept'        => 'application/json',
                        'Authorization' => "App ".env('SMS_VIRO_API_KEY')
                    ],
                    'json' => $formData,
                ]
            );
            $result = json_decode($result->getBody()->getContents());
        } catch (RequestException $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal Mengirim OTP');
        } catch (ServerException $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal Mengirim OTP.');
        } catch (ClientException $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal Mengirim OTP..');
        }
        return $result;
    }

    /**
     * update OTP user.
     */
    public function updateOTPUserByPhoneNumberService($phone)
    {
        DB::beginTransaction();
        try {
            $data = $this->authRepository->updateOTPUserByPhoneNumberRepo($phone);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Gagal update OTP');
        }
        DB::commit();
        return $data;
    }

    /**
     * verify OTP
     */
    public function verifyOTP($data = [])
    {
        $validator = Validator::make($data, [
            'userId' => 'bail|required',
            'otp' => 'bail|required',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $result = $this->authRepository->verifyOTPUserRepo($data);
        } catch (Exception $e) {
            DB::rollback();
            Log::info($e->getMessage());
            throw new InvalidArgumentException("Gagal verifikasi OTP (".$e->getMessage().")");
        }
        DB::commit();
        return $result;
    }

    /**
     * check verified phone
     */
    public function checkVerifiedPhoneUserService($phone)
    {
        try {
            $result = $this->authRepository->checkVerifiedPhoneUserRepo($phone);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }
        return $result;
    }
}

<?php
namespace App\Services;

use App\Repositories\AccurateRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class AccurateService {

    protected $accurateRepository;

    public function __construct(AccurateRepository $accurateRepository)
    {
        $this->accurateRepository = $accurateRepository;
        $guzzleProp = ['base_uri' => 'https://account.accurate.id', 'verify' => false];
        $this->client = new Client($guzzleProp);
    }

    /**
     * integrate accurate service
     */
    public function integrateAccurateService()
    {
        try {
            $formData = [
                'client_id' => env('ACCURATE_CLIENT_ID'),
                'response_type' => 'code',
                'redirect_uri' => env('ACCURATE_OAUTH_CALLBACK'),
                'scope' => env('ACCURATE_SCOPE')
            ];
            $result = $this->client->request(
                'POST',
                'oauth/authorize',
                [
                    'json' => $formData
                ]
            );
            $result = json_decode($result->getBody()->getContents());
        } catch (RequestException $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengintegrasikan accurate');
        } catch (ServerException $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengintegrasikan accurate');
        } catch (ClientException $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengintegrasikan accurate');
        }
        return $result;
    }

    /**
     * get integration credential
     */
    public function getIntegrationCredentialService()
    {
        $formData = [
            'client_id' => env('ACCURATE_CLIENT_ID'),
            'response_type' => 'code',
            'redirect_uri' => env('ACCURATE_OAUTH_CALLBACK'),
            'scope' => env('ACCURATE_SCOPE')
        ];
        return $formData;
    }

    /**
     * authorization token service
     */
    public function authorizationTokenService($data = [])
    {

        $validator = Validator::make($data, [
            'code' => 'bail|required',
            'redirectUri' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $json = [
                'code' => $data['code'],
                'redirect_uri' => $data['redirectUri'],
                'grant_type' => 'authorization_code'
            ];
            $base64 = base64_encode(env('ACCURATE_CLIENT_ID').":".env('ACCURATE_CLIENT_SECRET'));
            $headers = [
                'Authorization' => "Basic $base64"
            ];
            $result = $this->client->request(
                'POST',
                'oauth/token',
                [
                    'form_params' => $json,
                    'headers' => $headers
                ]
            );
            $result = json_decode($result->getBody()->getContents());
        } catch (RequestException $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengintegrasikan accurate');
        } catch (ServerException $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengintegrasikan accurate');
        } catch (ClientException $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mengintegrasikan accurate');
        }
        return $result;
    }
}

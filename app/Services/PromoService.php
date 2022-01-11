<?php
namespace App\Services;

use App\Models\Promo;
use App\Repositories\PromoRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Carbon\Carbon;
class PromoService {

    protected $promoRepository;
    protected $notifRepository;
    protected $userRepository;

    public function __construct(PromoRepository $promoRepository, NotificationRepository $notifRepository, UserRepository $userRepository)
    {
        $this->promoRepository = $promoRepository;
        $this->notifRepository = $notifRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Get promo for current user.
     *
     * @return Promo
     */
    public function getPromoUser($data)
    {
        $validator = Validator::make($data, [
            'userId' => 'bail|required|max:19',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $promo = $this->promoRepository->getUserId($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Gagal mendapatkan promo untuk pengguna ini');
        }
        return $promo;
    }

    /**
     * Get creator promo.
     *
     * @return Promo
     */
    public function getPromoCreator($data)
    {
        $validator = Validator::make($data, [
            'userId'                => 'bail|required|max:19',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $promo = $this->promoRepository->getCreatedBy($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan promo yang telah dibuat pengguna ini');
        }
        return $promo;
    }

    /**
     * Select promo
     *
     * @param array $data
     * @return mixed
     */
    public function selectPromo($data)
    {
        $validator = Validator::make($data, [
            'userId' => 'bail|required|max:19',
            'promoId' => 'bail|required|max:19',
            'value' => 'bail|required|max:10',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        // get promo
        try {
            $promo = $this->promoRepository->getById($data['promoId']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Promo tidak ditemukan');
        }

        try {
            $this->promoRepository->validatePromo($promo, $data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException($e->getMessage());
        }

        try {
            $result = $this->promoRepository->selectPromo($promo, $data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Gagal menggunakan promo ini');
        }

        return $result;
    }

    /**
     * Get all promo paginate.
     * @param array $data
     * @return mixed
     */
    public function getPromoPaginateService($data)
    {
        try {
            $branch = $this->promoRepository->getAllPaginateRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Gagal mendapat semua promo');
        }
        return $branch;
    }

    /**
     * create promo service
     *
     * @param array $data
     */
    public function createPromoService($data)
    {
        $validator = Validator::make($data, [
            'code' => 'bail|required',
            'customerId' => 'bail|present',
            'description' => 'bail|required',
            'discount' => 'bail|required',
            'discountMax' => 'bail|required',
            'endAt' => 'bail|required',
            'maxUsed' => 'bail|required',
            'minValue' => 'bail|required',
            'startAt' => 'bail|required',
            'terms' => 'bail|required',
            'userId' => 'bail|required',
            'scope' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $date = Carbon::parse($data['startAt'])->diffInSeconds($data['endAt'], false);
        if ($date < 0) {
            throw new InvalidArgumentException('Tanggal selesai harus lebih lama dari tanggal mulai');
        }

        DB::beginTransaction();
        if ($data['scope'] == 'general') {
            $data['customerId'] = null;
        }
        try {
            $promo = $this->promoRepository->save($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            throw new InvalidArgumentException('Gagal membuat promo');
        }

        // SEND FCM
        try {
            $fcm = $this->userRepository->getFcmUserRepo($data['customerId']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            throw new InvalidArgumentException('Gagal mendapatkan data fcm');
        }

        if (count($fcm) > 0) {
            try {
                $notifPayload = [
                    'fcm' => $fcm,
                    'title' => $data['description'],
                    'body' => $data['terms'],
                    // 'imageUrl' => 'imageUrl',
                    'jsonData' => collect($data)->toArray()
                ];
                $resultNotification = $this->notifRepository->sendFCMRepo($notifPayload);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                throw new InvalidArgumentException('Gagal mengirim notifikasi');
            }

            // SAVE FCM
            try {
                $saveNotifPayload = [
                    'userId' => $data['customerId'],
                    'data' => [
                        'title' => $data['description'],
                        'body' => $data['terms'],
                        'data' => collect($data)->toArray()
                    ],
                    'type' => 'promo'
                ];
                $saveNotification = $this->notifRepository->saveNotificationRepo($saveNotifPayload);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                throw new InvalidArgumentException('Gagal menyimpan data notifikasi');
            }
        }
        // END SEND FCM
        DB::commit();
        return $promo;
    }

    /**
     * update promo
     */
    public function updatePromoService($data = [])
    {
        $validator = Validator::make($data, [
            'description' => 'bail|required',
            'discount' => 'bail|required',
            'discount_max' => 'bail|required',
            'end_at' => 'bail|required',
            'start_at' => 'bail|required',
            'max_used' => 'bail|required',
            'min_value' => 'bail|required',
            'terms' => 'bail|required',
            'userId' => 'bail|required',
            'id' => 'bail|required',
            'scope' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $promo = $this->promoRepository->updatePromoRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            throw new InvalidArgumentException('Gagal mengubah data promo');
        }

        // SEND FCM
        if ($promo->scope == 'general') {
            $customerId = null;
        } else {
            $customerId = $promo->user_id;
        }

        try {
            $fcm = $this->userRepository->getFcmUserRepo($customerId);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            throw new InvalidArgumentException('Gagal mendapatkan data fcm');
        }

        if (count($fcm) > 0) {
            try {
                $notifPayload = [
                    'fcm' => $fcm,
                    'title' => $data['description'],
                    'body' => $data['terms'],
                    // 'imageUrl' => 'imageUrl',
                    'jsonData' => collect($data)->toArray()
                ];
                $resultNotification = $this->notifRepository->sendFCMRepo($notifPayload);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                throw new InvalidArgumentException('Gagal mengirim notifikasi');
            }

            // SAVE FCM
            try {
                $saveNotifPayload = [
                    'userId' => $customerId,
                    'data' => [
                        'title' => $data['description'],
                        'body' => $data['terms'],
                        'data' => collect($data)->toArray()
                    ],
                    'type' => 'promo'
                ];
                $saveNotification = $this->notifRepository->saveNotificationRepo($saveNotifPayload);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                DB::rollback();
                throw new InvalidArgumentException('Gagal menyimpan data notifikasi');
            }
        }
        DB::commit();
        return $promo;
    }

    /**
     * delete promo
     */
    public function deletePromoService($data = [])
    {
        $validator = Validator::make($data, [
            'promoCode' => 'bail|required',
            'promoId' => 'bail|required',
            'userId' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        DB::beginTransaction();
        try {
            $promo = $this->promoRepository->deletePromoRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            DB::rollback();
            throw new InvalidArgumentException('Gagal menghapus data promo');
        }
        DB::commit();
        return $promo;
    }

    /**
     * search promo service
     */
    public function searchPromoService($data = [])
    {
        $validator = Validator::make($data, [
            'customerId' => 'bail|present',
            'query' => 'bail|present',
            'type' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        try {
            $promo = $this->promoRepository->searchPromoRepo($data);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::error($e);
            throw new InvalidArgumentException('Gagal mendapatkan data promo');
        }
        return $promo;
    }

    /**
     * Get promo by id.
     *
     * @return Promo
     */
    public function getPromoByIdService($promoId)
    {
        try {
            $promo = $this->promoRepository->getById($promoId);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            throw new InvalidArgumentException('Gagal mendapatkan promo');
        }
        return $promo;
    }
}

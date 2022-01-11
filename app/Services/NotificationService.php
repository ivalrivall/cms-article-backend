<?php
namespace App\Services;

use App\Repositories\NotificationRepository;
use Exception;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class NotificationService {

    protected $notifRepository;

    public function __construct(NotificationRepository $notifRepository)
    {
        $this->notifRepository = $notifRepository;
    }

    /**
     * send notification fcm
     *
     * @param array $data
     * @return String
     */
    public function sendNotifFCMService($data = [])
    {
        $validator = Validator::make($data, [
            'fcm' => 'bail|required'
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        if (is_array($data['fcm'])) {
            try {
                $result = $this->notifRepository->sendMultiMessagingFCMRepo($data);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                // throw new InvalidArgumentException('Gagal mengirim notifikasi');
                throw new InvalidArgumentException($e->getMessage());
            }
        } else {
            try {
                $result = $this->notifRepository->sendSingleMessagingFCMRepo($data);
            } catch (Exception $e) {
                Log::info($e->getMessage());
                Log::error($e);
                // throw new InvalidArgumentException('Gagal mengirim notifikasi');
                throw new InvalidArgumentException($e->getMessage());
            }
        }
        return $result;
    }
}

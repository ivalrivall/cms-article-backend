<?php

namespace App\Repositories;

// use Kreait\Firebase\Messaging;
// use Kreait\Firebase\Messaging\ApnsConfig;
// use Kreait\Messaging\Message;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Illuminate\Support\Str;

use App\Models\Notification as NotificationModel;
use App\Models\User;

class NotificationRepository
{
    protected $notification;
    protected $user;
    public function __construct(NotificationModel $notification, User $user)
    {
        $this->notification = $notification;
        $this->user = $user;
    }

    /**
     * send single notification fcm
     *
     * @param array $data
     */
    public function sendSingleMessagingFCMRepo($data = [])
    {
        $messaging = app('firebase.messaging');
        $message = CloudMessage::withTarget('token', $data['fcm'])
            ->withNotification($data['notification'])
            ->withData($data['jsonData']);
            // ->withAndroidConfig($data['config']);
        return $messaging->send($message);
    }

    /**
     * send multiple notification fcm
     *
     * @param array $data
     */
    public function sendMultiMessagingFCMRepo($data = [])
    {
        $messaging = app('firebase.messaging');
        $message = CloudMessage::new()
            ->withNotification($data['notification'])
            ->withData($data['jsonData']);
            // ->withAndroidConfig($data['config']);
        $report = $messaging->sendMulticast($message, $data['fcm']);
        $successSends = $report->successes()->count();
        $failedSends = $report->failures()->count();

        $messagesFailure = [];
        if ($report->hasFailures()) {
            foreach ($report->failures()->getItems() as $failure) {
                $messagesFailure[] = $failure->error()->getMessage();
            }
        }

        // The following methods return arrays with registration token strings
        $successfulTargets = $report->validTokens(); // string[]

        // Unknown tokens are tokens that are valid but not know to the currently
        // used Firebase project. This can, for example, happen when you are
        // sending from a project on a staging environment to tokens in a
        // production environment
        $unknownTargets = $report->unknownTokens(); // string[]

        // Invalid (=malformed) tokens
        $invalidTargets = $report->invalidTokens(); // string[]
        return [
            'successSends' => $successSends,
            'failedSends' => $failedSends,
            'messageFailure' => $messagesFailure,
            'successfulTargets' => $successfulTargets,
            'unknownTargets' => $unknownTargets,
            'invalidTargets' => $invalidTargets
        ];
    }

    /**
     * send fcm
     */
    public function sendFCMRepo($data)
    {
        $messaging = app('firebase.messaging');
        $result = $messaging->validateRegistrationTokens($data['fcm']);
        // if (!empty($result['unknown'])) {
        //     $unknownTokens = $result['unknown'];
        // }
        // if (!empty($result['invalid'])) {
        //     $invalidTokens = $result['invalid'];
        // }
        if (!empty($result['valid'])) {
            $notification = Notification::create($data['title'], $data['body']);
            // $notification = Notification::fromArray([
            //     'title' => $data['title'],
            //     'body' => $data['body'],
            //     'image' => 'https://backend.papandayancargo.id/images/logo-text.svg'
            //     // 'image' => $data['imageUrl'],
            // ]);
            $config = AndroidConfig::fromArray([
                'ttl' => '3600s',
                'priority' => 'high',
                'notification' => [
                    'icon' => 'https://backend.papandayancargo.id/images/logo-text.svg',
                    'color' => '#f45342'
                ],
            ]);

            if (count($result['valid']) > 1) {
                $payload = [
                    'fcm' => $result['valid'],
                    'notification' => $notification,
                    'config' => $config,
                    'jsonData' => $data['jsonData']
                ];
                Log::info("send fcm multiple => ". json_encode($payload));
                return $this->sendMultiMessagingFCMRepo($payload);
            } else {
                $payload = [
                    'fcm' => $result['valid'][0],
                    'notification' => $notification,
                    'config' => $config,
                    'jsonData' => $data['jsonData']
                ];
                Log::info("send fcm single => ". json_encode($payload));
                return $this->sendSingleMessagingFCMRepo($payload);
            }
        }
    }

    /**
     * save notification
     */
    public function saveNotificationRepo($data = [])
    {
        if ($data['userId'] == null) {
            $user = $this->user->whereNotNull('fcm')->select('id')->get()->pluck('id')->all();
        } else if (is_array($data['userId'])) {
            $user = $this->user->whereNotNull('fcm')->whereIn('id', $data['userId'])->select('id')->get()->pluck('id')->all();
        } else {
            $user = $this->user->whereNotNull('fcm')->where('id', $data['userId'])->select('id')->get()->pluck('id')->all();
        }
        $usersId = collect(array_filter($user))->values()->all();
        $result = [];
        if (count($usersId) > 0) {
            foreach ($usersId as $key => $value) {
                $user = $this->user->find($value);
                $notification = new $this->notification;
                $notification->id = Str::uuid();
                $notification->data = $data['data'];
                $notification->type = $data['type'];
                $result[] = $user->notifications()->save($notification);
            }
        }
        return $result;
    }
}

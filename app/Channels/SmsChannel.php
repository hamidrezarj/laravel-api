<?php

namespace App\Channels;

use Exception;
use Ghasedak\Exceptions\ApiException;
use Ghasedak\GhasedakApi;
use Illuminate\Notifications\Notification;

class SmsChannel
{

    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toSms')) {

            $receptor = $notification->toSms($notifiable)['receptor'];
            $message = $notification->toSms($notifiable)['message'];

            try {
                $api = new GhasedakApi(env('GHASEDAKAPI_KEY'));
                $api->AccountInfo();
                $api->SendSimple(
                    $receptor,  // receptor
                    $message, // message
                    "300002525"    // choose a line number from your account
                );

            } catch (ApiException $e) {
                throw new Exception('Error occured from Ghasedak Sms API');
            } catch (Exception $e) {
                throw $e->getMessage();
            }
        } else {
            throw new Exception("toSms method not found", 1);
        }
    }
}

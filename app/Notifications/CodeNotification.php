<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Channels\SmsChannel;

class CodeNotification extends Notification implements ShouldQueue
{
    use Queueable;
    private $verification_code;
    private $phone;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($verification_code, $phone)
    {
        $this->verification_code = $verification_code;
        $this->phone = $phone;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->from('hamidrezarrj@gmail.com', 'Hamidreza')
                    ->subject('First email')
                    ->line("Your verification Code is: ".$this->verification_code->code)
                    ->action('Get User Details', route('user'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Constructs SMS for given phone number
     */
    public function toSms($notifiable)
    {
        // return $this->verification_code->code;
        return [
            'message' => 'Your verification code is: '. $this->verification_code->code,
            'receptor'   => $this->phone,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

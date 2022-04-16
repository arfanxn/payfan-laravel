<?php

namespace App\Notifications;

use App\Helpers\URLHelper;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $verificationCode, $verificationReason;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(int $verificationCode, string $verificationReason = "")
    {
        $this->verificationCode  = $verificationCode;
        $this->verificationReason  = $verificationReason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ["mail"];
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
            ->subject("Verification Code | " . config("app.name"))
            ->greeting(isset($notifiable->name) ? "Hello, $notifiable->name ." : "Hello.")
            ->line("Your verification code for verifying " . $this->verificationReason . " is.")
            ->line('Verification Code : ' . $this->verificationCode)
            ->line('Expire at : ' . now()->addMinutes(30)->format("M d D - H:i") . " UTC")
            ->line("Don't let anyone know your verification code.");
    }
}

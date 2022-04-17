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

    private $code, $reason, $notifiable_name;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        string $code,
        array $verification = ["reason" => null, "notifiable_name" => null,]
    ) {
        $this->code  = $code;
        $this->reason  = $verification['reason'] ?? null;
        $this->notifiable_name  = $verification['notifiable_name'] ?? null;
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
        $notifiableName = isset($notifiable->name) &&
            is_string($notifiable->name) && strlen($notifiable->name)
            ? $notifiable->name
            : (isset($this->notifiable_name)
                && is_string($this->notifiable_name)
                ? $this->notifiable_name : null);

        $greeting = "";
        if ($notifiableName)
            $greeting = "Hello, $notifiableName .";
        else $greeting = "Hello.";

        $body = "";
        if ($this->reason)
            $body = "Your verification code / otp for verifying " . PHP_EOL . "$this->reason is";
        else $body = "Your verification code / otp is";

        return (new MailMessage)
            ->subject("Verification Code / OTP | " . config("app.name"))
            ->greeting($greeting)
            ->line($body)
            ->line('Verification Code : ' . $this->code)
            ->line('Expire at : ' . now()->addMinutes(30)->format("M d D - H:i") . " UTC")
            ->line("Don't let anyone know your verification code.");
    }
}

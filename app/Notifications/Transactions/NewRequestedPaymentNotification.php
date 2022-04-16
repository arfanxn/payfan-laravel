<?php

namespace App\Notifications\Transactions;

use App\Helpers\URLHelper;
use App\Models\Payment;
use App\Traits\Notifications\HasToBroadcastNotificationTrait;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRequestedPaymentNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;
    use HasToBroadcastNotificationTrait;

    public Payment $payment;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Payment $payment)
    {
        if (!$payment->relationLoaded("toWallet.user") || !$payment->relationLoaded("fromWallet.user"))
            $payment = $payment->load(["toWallet.user", "fromWallet.user"]);

        $this->payment = $payment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        if (!/**/$notifiable->settings->request_notification)
            return ["database", "broadcast"];

        return ["mail", "database", "broadcast"];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $total = floatval($this->payment->amount) + floatval($this->payment->charge);
        $total = Str::contains($total, ".") ? $total . " $" : $total . ".00 $";

        return (new MailMessage)
            ->subject('Pending requested payment from "'
                . substr($this->payment->fromWallet->user->name, 0, 10)  . '" | ' . config('app.name'))
            ->greeting("Hello, $notifiable->name .")
            ->line('You have a pending requested payment from "' . $this->payment->fromWallet->user->name . '", 
                amount ' . $this->payment->amount . " $")
            ->line('Requester name : ' . $this->payment->fromWallet->user->name)
            ->line('Requested name : ' . $this->payment->toWallet->user->name)
            ->line('Amount : ' . $this->payment->amount . " $")
            ->line('Charge : ' . $this->payment->charge . " $")
            ->line('Total : ' . $total)
            ->line('Requested at : ' . Carbon::parse($this->payment->started_at
                ?? $this->payment->created_at)->toDateTimeString() . " UTC")
            ->line('Payment ID : ' . $this->payment->id)
            ->line('Transaction ID : ' . $this->payment->transaction_id)
            ->action('View Pending Request', URLHelper::frontendWeb("/activity?keyword=" . $this->payment->id))
            ->line('Thank you for using our application!');
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
            "header" => "Pending requested payment",
            "body" =>
            'You have a pending requested payment from "' . $this->payment->fromWallet->user->name . '", 
                amount ' . $this->payment->amount . " $",
            "action" => [
                "text" => "View Pending Request",
                "url" => URLHelper::frontendWeb("/activity?keyword=" . $this->payment->id),
                "query" => [
                    "payment_id" => $this->payment->id
                ]
            ]
        ];
    }
}
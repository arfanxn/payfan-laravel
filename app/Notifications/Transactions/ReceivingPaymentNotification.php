<?php

namespace App\Notifications\Transactions;

use App\Helpers\URLHelper;
use App\Models\Payment;
use App\Traits\Notifications\HasToBroadcastNotificationTrait;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReceivingPaymentNotification  extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;
    use HasToBroadcastNotificationTrait;

    private Payment $payment;

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
        if (!/**/$notifiable->settings->receive_notification)
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
            ->subject('Receive a payment from "'
                . substr($this->payment->fromWallet->user->name, 0, 10)  . '" | ' . config('app.name'))
            ->greeting("Hello, $notifiable->name .")
            ->line('You receive a payment from  "' . $this->payment->fromWallet->user->name . '", 
                amount ' . $this->payment->amount . " $")
            ->line('Sender name : ' . $this->payment->fromWallet->user->name)
            ->line('Receiver name : ' . $this->payment->toWallet->user->name)
            ->line('Amount : ' . $this->payment->amount . " $")
            // ->line('Charge : ' . $this->payment->charge . " $")
            ->line('Total : ' . $total)
            ->line('Completed at : ' . Carbon::parse($this->payment->completed_at)->toDateTimeString() . " UTC")
            ->line('Payment ID : ' . $this->payment->id)
            ->line('Transaction ID : ' . $this->payment->transaction_id)
            ->action('View Invoice', URLHelper::frontendWeb('/activity?keyword=' . $this->payment->id))
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
            "header" => "Receiving payment",
            "body" =>
            'You receive a payment from "' . substr($this->payment->fromWallet->user->name, 0, 15)
                . '", amount ' . $this->payment->amount . " $",
            "action" => [
                "text" => "View Invoice",
                "url" => URLHelper::frontendWeb("activity?keyword=" . $this->payment->id),
                "query" => [
                    "payment_id" => $this->payment->id
                ]
            ]
        ];
    }
}
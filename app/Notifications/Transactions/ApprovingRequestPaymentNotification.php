<?php

namespace App\Notifications\Transactions;

use App\Helpers\StrHelper;
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

class ApprovingRequestPaymentNotification extends Notification implements ShouldBroadcast,  ShouldQueue
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
        $total = StrHelper::make((string) $total)->toUSD(true)->get();

        return (new MailMessage)
            ->subject('Approving request payment from "'
                . substr($this->payment->fromWallet->user->name, 0, 10)  . '" | ' . config('app.name'))
            ->greeting("Hello, $notifiable->name .")
            ->line($this->toArray($notifiable)['body'])
            ->line('Requester name : ' . $this->payment->fromWallet->user->name)
            ->line('Requested name : ' . $this->payment->toWallet->user->name)
            ->line('Amount : ' . StrHelper::make((string) $this->payment->amount)->toUSD(true)->get())
            ->line('Charge : ' . StrHelper::make((string) $this->payment->charge)->toUSD(true)->get())
            ->line('Total : ' . $total)
            ->line('Approved at : ' . Carbon::parse($this->payment->completed_at)->toDateTimeString() . " UTC")
            ->line('Payment ID : ' . $this->payment->id)
            ->line('Transaction ID : ' . $this->payment->transaction_id)
            ->action('View Approved Request', URLHelper::frontendWeb("/activity?keyword=" . $this->payment->id))
            ->line('Thank you for using our application!')
            ->success();
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
            "header" => "Approving request payment",
            "body" =>
            'You approving a request payment from "' . $this->payment->fromWallet->user->name . '", 
                amount ' . StrHelper::make((string) $this->payment->amount)->toUSD(true)->get(),
            "action" => [
                "text" => "View Approved Request",
                "url" => URLHelper::frontendWeb("/activity?keyword=" . $this->payment->id),
                "query" => [
                    "payment_id" => $this->payment->id
                ]
            ]
        ];
    }
}

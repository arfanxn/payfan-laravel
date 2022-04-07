<?php

namespace App\Notifications\Transactions;

use App\Helpers\URLHelper;
use App\Models\Order;
use App\Traits\Notifications\HasToBroadcastNotificationTrait;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RejectedRequestMoneyNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;
    use HasToBroadcastNotificationTrait;

    public Order $order;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        if (!$order->relationLoaded("toWallet.user") || !$order->relationLoaded("fromWallet.user"))
            $order = $order->load(["toWallet.user", "fromWallet.user"]);

        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
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
        $total = floatval($this->order->amount) + floatval($this->order->charge);
        $total = Str::contains($total, ".") ? $total . " $" : $total . ".00 $";

        return (new MailMessage)
            ->subject('Rejected request money to "'
                . substr($this->order->toWallet->user->name, 0, 10)  . '" | ' . config('app.name'))
            ->greeting("Hello, $notifiable->name .")
            ->line('Your request money to "' . $this->order->toWallet->user->name . '", 
                amount ' . $this->order->amount . " $, has been rejected.")
            ->line('Requester name : ' . $this->order->fromWallet->user->name)
            ->line('Requested name : ' . $this->order->toWallet->user->name)
            ->line('Amount : ' . $this->order->amount . " $")
            ->line('Charge : ' . $this->order->charge . " $")
            ->line('Total : ' . $total)
            ->line('Approved at : ' . Carbon::parse($this->order->completed_at)->toDateTimeString() . " UTC")
            ->line('Order ID : ' . $this->order->id)
            ->line('Transaction ID : ' . $this->order->transaction_id)
            ->action('View Rejected Request', URLHelper::frontendWeb("/activity?keyword=" . $this->order->id))
            ->line('Thank you for using our application!')
            ->error();
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
            "header" => "Rejected request money",
            "body" =>
            'Your request money to "' . $this->order->toWallet->user->name . '", 
                amount ' . $this->order->amount . " $, has been rejected.",
            "action" => [
                "text" => "View Rejected Request",
                "url" => URLHelper::frontendWeb("/activity?keyword=" . $this->order->id),
            ]
        ];
    }
}

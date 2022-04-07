<?php

namespace App\Notifications\Transactions;

use App\Helpers\URLHelper;
use App\Models\Order;
use App\Traits\Notifications\HasToBroadcastNotificationTrait;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovingRequestMoneyNotification extends Notification implements ShouldQueue
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
            ->subject('Approving request money from "'
                . substr($this->order->fromWallet->user->name, 0, 10)  . '" | ' . config('app.name'))
            ->greeting("Hello, $notifiable->name .")
            ->line('You approving a request money from "' . $this->order->fromWallet->user->name . '", 
                amount ' . $this->order->amount . " $")
            ->line('Requester name : ' . $this->order->fromWallet->user->name)
            ->line('Requested name : ' . $this->order->toWallet->user->name)
            ->line('Amount : ' . $this->order->amount . " $")
            ->line('Charge : ' . $this->order->charge . " $")
            ->line('Total : ' . $total)
            ->line('Approved at : ' . Carbon::parse($this->order->completed_at)->toDateTimeString() . " UTC")
            ->line('Order ID : ' . $this->order->id)
            ->line('Transaction ID : ' . $this->order->transaction_id)
            ->action('View Approved Request', URLHelper::frontendWeb("/activity?keyword=" . $this->order->id))
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
            "header" => "Approving request money",
            "body" =>
            'You approving a request money from "' . $this->order->fromWallet->user->name . '", 
                amount ' . $this->order->amount . " $",
            "action" => [
                "text" => "View Approved Request",
                "url" => URLHelper::frontendWeb("/activity?keyword=" . $this->order->id),
            ]
        ];
    }
}

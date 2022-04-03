<?php

namespace App\Notifications;

use App\Helpers\URLHelper;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendMoneyNotification extends Notification // implements ShouldQueue
{
    use Queueable;

    public Order $order;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        if (!$order->relationLoaded("toWallet.user"))
            $order = $order->load("toWallet.user");

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
        return ['database', "mail"];
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
            ->subject('Send money to "'
                . substr($this->order->toWallet->user->name, 0, 10)  . '" successfully | ' . config('app.name'))
            ->greeting("Hello, $notifiable->name .")
            ->line('Your transaction to "' . $this->order->toWallet->user->name . '" successfully.')
            ->line('Sender : ' . $this->order->fromWallet->user->name)
            ->line('Receiver : ' . $this->order->toWallet->user->name)
            ->line('Amount : ' . $this->order->amount . " $")
            ->line('Charge : ' . $this->order->charge . " $")
            ->line('Total : ' . $total)
            ->line('Completed at : ' . Carbon::parse($this->order->completed_at)->toDateTimeString() . " UTC")
            ->line('Order ID : ' . $this->order->id)
            ->line('Transaction ID : ' . $this->order->transaction_id)
            ->action('View Invoice', URLHelper::frontendWeb('/activity?keyword=' . $this->order->id))
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
            "header" => "Send money successfully",
            "body" =>
            'Send money to "' . substr($this->order->toWallet->user->name, 0, 15)
                . '", amount ' . $this->order->amount . " $.",
            "action" => [
                "text" => "View Invoice",
                "url" => URLHelper::frontendWeb("activity?keyword=" . $this->order->id),
            ]
        ];
    }
}

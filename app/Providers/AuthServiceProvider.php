<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define("has-contact", fn (User $user, Contact $contact) => $user->id  === $contact->owner_id); // gate explanation : is user has/adding/saving the contact (true/false)

        Gate::define( // check user has this transaction or not 
            "has-transaction",
            fn (User $user, Transaction $transaction) =>
            $user->id === $transaction->from_wallet || $user->id === $transaction->to_wallet
        );

        Gate::define("has-notification", fn (User $user, $notification) => $user->id === $notification->notifiable_id); // check if user has the notification  
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */

    public static final function USER_AUTHORIZATION($id = "")
    {
        return  "users." . $id;
    }

    public function boot()
    {
        Broadcast::routes(['middleware' => ['auth'], "prefix"  => "api"]); // 

        require base_path('routes/channels.php');
    }
}

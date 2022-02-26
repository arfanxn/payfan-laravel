<?php

namespace App\Console\Commands;

use App\Helpers\StrHelper;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserProfilePict extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update_profile_pict';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updating all row in users table profile pict';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        for ($i = 2; $i <=  19999; $i++) {
            $hex = StrHelper::make("#" . StrHelper::random(6, "ABCDEF1234567890"))->toUpperCase()->get();
            User::where("id", $i)->update(["profile_pict" =>  $hex]);
        }
    }
}

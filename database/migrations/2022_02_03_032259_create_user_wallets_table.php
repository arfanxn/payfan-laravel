<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained("users", "id")
                ->onDelete("cascade")->onUpdate("cascade");
            $table->string("address", 16);
            $table->unsignedBigInteger("balance")->default(0);
            $table->unsignedBigInteger("total_transaction")->default(0);
            $table->timestamp("last_transaction")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_wallets');
    }
}

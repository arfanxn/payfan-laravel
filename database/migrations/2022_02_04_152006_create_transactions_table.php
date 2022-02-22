<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string("tx_hash", 50);
            $table->foreignId("from_wallet")->constrained("user_wallets", "id")
                ->onUpdate("cascade");
            $table->foreignId("to_wallet")->constrained("user_wallets", "id")
                ->onUpdate("cascade");
            $table->string("status");
            $table->string("type");
            $table->string("note", 1000)->nullable();
            $table->unsignedBigInteger("amount");
            $table->integer("charge")->default(0);
            $table->timestamp("started_at")->default(now()->toDateTimeString());
            $table->timestamp("completed_at")->nullable()->default(null);;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions_');
    }
}

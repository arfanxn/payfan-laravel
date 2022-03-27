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
            $table->uuid("id")->primary();
            // $table->string("tx_hash", 50);
            $table->foreignId("from_wallet")->constrained("wallets", "id")
                ->onUpdate("cascade");
            $table->foreignId("to_wallet")->constrained("wallets", "id")
                ->onUpdate("cascade");
            $table->unsignedDecimal("amount", 11, 2);
            $table->unsignedInteger("charge")->default(0);
            $table->string("status");
            $table->timestamps();
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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignId("user_id")->constrained("users", "id")->onDelete("cascade");
            $table->foreignId("from_wallet")
                ->constrained("wallets", "id")->onDelete("cascade");
            $table->foreignId("to_wallet")
                ->constrained("wallets", "id")->onDelete("cascade");
            $table->foreignUuid("transaction_id")
                ->constrained("transactions", "id")
                ->onUpdate("cascade")->onDelete("cascade");
            $table->string("note", 255)->nullable();
            $table->string("type", 100);
            $table->string("status", 100);
            $table->unsignedDecimal("amount", 11, 2);
            $table->unsignedDecimal("charge", 7, 2)->nullable()->default(0);
            $table->timestamps();
            $table->timestamp("completed_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}

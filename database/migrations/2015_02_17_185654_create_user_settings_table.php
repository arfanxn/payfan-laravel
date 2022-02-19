<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained("users")
                ->onDelete("cascade")->onUpdate("cascade");
            $table->boolean("two_factor_auth")->default(false);
            $table->string("security_question")->nullable();
            $table->string("security_answer")->nullable();
            $table->boolean("payment_notification")->default(true);
            $table->boolean("request_notification")->default(true);
            $table->boolean("receive_notification")->default(true);
            $table->timestamp("updated_at");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_settings');
    }
}

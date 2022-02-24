<?php

use App\Models\Notification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained("users", "id")->onDelete("cascade")->onUpdate("cascade");
            $table->string("type", 50);
            $table->string("title", 100);
            $table->string("redirect")->nullable();
            $table->string("body", 9999)->nullable();
            $table->string("status")->default(Notification::STATUS_UNREAD);
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
        Schema::dropIfExists('notifications');
    }
}

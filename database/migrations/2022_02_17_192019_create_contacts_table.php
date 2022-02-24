<?php

use App\Models\Contact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId("owner_id")->constrained("users", "id")
                ->onDelete("cascade")->onUpdate("cascade");
            $table->foreignId("saved_id")->constrained("users", "id")
                ->onDelete("cascade")->onUpdate("cascade");
            $table->string("status")->default(Contact::STATUS_ADDED); // status =  null / favorited / blocked  ; 
            $table->unsignedBigInteger("total_transaction")->default(0);
            $table->timestamp("last_transaction")->nullable();
            $table->timestamp("added_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSslUpdateQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ssl_update_queue', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ticketID')->unsigned()->nullable();
            $table->string('status');
            $table->date('date_created')->nullable()->default(null);
            $table->date('date_updated')->nullable()->default(null);

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('ticketID')->references('id')->on('testing_servers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ssl_update_queue');
    }
}

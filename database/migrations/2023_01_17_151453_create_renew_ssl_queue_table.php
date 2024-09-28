<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRenewSslQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('renew_ssl_queue', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ticketID')->unsigned()->nullable();
            $table->string('status');
            $table->dateTime('date_created')->nullable();
            $table->dateTime('date_updated')->nullable();

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
        Schema::dropIfExists('renew_ssl_queue');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaleServerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stale_server', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ticketID')->unsigned()->nullable();
            $table->string('date_added');
            $table->string('next_date_check');
            $table->boolean('ssl_renewal')->unsigned()->nullable();
            $table->boolean('delete_protection')->unsigned()->nullable();
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
        Schema::dropIfExists('stale_server');
    }
}

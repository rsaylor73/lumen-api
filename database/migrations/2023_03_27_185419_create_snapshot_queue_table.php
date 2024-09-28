<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSnapshotQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('snapshot_queue', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ticketID')->unsigned()->nullable();
            $table->bigInteger('snapshotID')->unsigned()->nullable();
            $table->string('status')->nullable();
            $table->date('date_created')->nullable()->default(null);
            $table->date('date_updated')->nullable()->default(null);

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('ticketID')->references('id')->on('testing_servers')->onDelete('cascade');
            $table->foreign('snapshotID')->references('id')->on('snapshot_backup')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('snapshot_queue');
    }
}

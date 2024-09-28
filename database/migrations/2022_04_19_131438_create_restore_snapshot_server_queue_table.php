<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestoreSnapshotServerQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restore_snapshot_server_queue', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ticketID')->unsigned()->nullable();
            $table->string('status');
            $table->date('created_at')->nullable()->default(null);
            $table->date('updated_at')->nullable()->default(null);

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
        Schema::dropIfExists('restore_snapshot_server_queue');
    }
}

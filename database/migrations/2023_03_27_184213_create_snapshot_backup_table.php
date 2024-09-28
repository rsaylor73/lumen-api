<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSnapshotBackupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('snapshot_backup', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ticketID')->unsigned()->nullable();
            $table->string('snapshotID')->nullable();
            $table->date('created_at')->nullable()->default(null);

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
        Schema::dropIfExists('snapshot_backup');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHardRebootServerQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hard_reboot_server_queue', function (Blueprint $table) {
            $table->string('instanceID')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hard_reboot_server_queue', function (Blueprint $table) {
            $table->dropColumn('instanceID');
        });
    }
}

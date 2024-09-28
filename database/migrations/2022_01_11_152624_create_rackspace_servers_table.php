<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRackspaceServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rackspace_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('serverID');
            $table->string('status');
            $table->string('ipAddress');
            $table->date('created_at')->nullable()->default(null);
            $table->date('modified_at')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rackspace_servers');
    }
}

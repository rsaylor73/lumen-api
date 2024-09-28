<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV2DemoServerQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v2_demo_server_queue', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('demo_serverID')->unsigned()->nullable();
            $table->string('status');
            $table->date('created_at')->nullable()->default(null);
            $table->date('updated_at')->nullable()->default(null);

            $table->foreign('demo_serverID')->references('id')->on('v2_demo_servers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v2_demo_server_queue');
    }
}

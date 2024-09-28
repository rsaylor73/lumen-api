<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemoServersQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demo_servers_queue', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('demoID')->unsigned()->nullable();
            $table->string('status');
            $table->date('created_at')->nullable()->default(null);
            $table->date('updated_at')->nullable()->default(null);

            //FOREIGN KEY CONSTRAINTS
            $table->foreign('demoID')->references('id')->on('demo_servers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('demo_servers_queue');
    }
}

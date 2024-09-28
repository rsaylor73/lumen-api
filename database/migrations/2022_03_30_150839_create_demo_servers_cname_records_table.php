<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemoServersCnameRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demo_servers_cname_records', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('demoID')->unsigned()->nullable();
            $table->string('cname_identifier');
            $table->string('cname_value');

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
        Schema::dropIfExists('demo_servers_cname_records');
    }
}

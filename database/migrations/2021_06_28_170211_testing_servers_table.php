<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestingServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('testing_servers', function (Blueprint $table) {
            $table->id();
            $table->string('ticket');
            $table->string('dns');
            $table->string('email');
            $table->string('ip_address');
            $table->string('instanceID');
            $table->date('created_at')->nullable()->default(null);
            $table->date('updated_at')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('testing_servers');
    }
}

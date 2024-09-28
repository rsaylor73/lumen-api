<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemoServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demo_servers', function (Blueprint $table) {
            $table->id();
            $table->string('dns');
            $table->string('email');
            $table->string('current_status');
            $table->string('ip_address');
            $table->string('instanceID');
            $table->text('description');
            $table->date('termination_date')->nullable()->default(null);
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
        Schema::dropIfExists('demo_servers');
    }
}

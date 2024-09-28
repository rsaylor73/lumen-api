<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV2TestingServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v2_testing_servers', function (Blueprint $table) {
            $table->id();
            $table->string('ticket');
            $table->string('dns');
            $table->string('email');
            $table->string('ip_address');
            $table->string('ip_private_address');
            $table->string('terraform_fileName');
            $table->string('description');
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
        Schema::dropIfExists('v2_testing_servers');
    }
}

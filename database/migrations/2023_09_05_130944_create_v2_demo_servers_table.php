<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV2DemoServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v2_demo_servers', function (Blueprint $table) {
            $table->id();
            $table->string('dns');
            $table->string('email');
            $table->string('terraform_fileName');
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
        Schema::dropIfExists('v2_demo_servers');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitTestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_tests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ticketID')->unsigned()->nullable();
            $table->string('vjs_status')->nullable();
            $table->string('vjs_tests')->nullable();
            $table->string('vjs_passes')->nullable();
            $table->string('vjs_assertions')->nullable();
            $table->string('jr_status')->nullable();
            $table->string('jr_tests')->nullable();
            $table->string('jr_passes')->nullable();
            $table->string('jr_assertions')->nullable();
            $table->string('sac_status')->nullable();
            $table->string('sac_tests')->nullable();
            $table->string('sac_passes')->nullable();
            $table->string('sac_assertions')->nullable();
            $table->string('planner_status')->nullable();
            $table->string('planner_tests')->nullable();
            $table->string('planner_passes')->nullable();
            $table->string('planner_assertions')->nullable();
            $table->string('auth_status')->nullable();
            $table->string('auth_tests')->nullable();
            $table->string('auth_passes')->nullable();
            $table->string('auth_assertions')->nullable();
            $table->date('created_at')->nullable()->default(null);
            $table->dateTime('created_time')->nullable()->default(null);

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
        Schema::dropIfExists('unit_tests');
    }
}

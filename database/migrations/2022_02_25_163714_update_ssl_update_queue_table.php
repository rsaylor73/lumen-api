<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSslUpdateQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ssl_update_queue', function (Blueprint $table) {
            $table->string('sslID')->nullable();
            $table->text('csr')->nullable();
            $table->text('crt')->nullable();
            $table->text('key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ssl_update_queue', function (Blueprint $table) {
            $table->dropColumn('sslID');
            $table->dropColumn('csr');
            $table->dropColumn('crt');
            $table->dropColumn('key');
        });
    }
}

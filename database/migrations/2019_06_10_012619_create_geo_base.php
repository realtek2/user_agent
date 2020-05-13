<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeoBase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('geo_base', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('long_ip1');
            $table->bigInteger('long_ip2');
            $table->string('ip1');
            $table->string('ip2');
            $table->string('country');
            $table->string('city_id');

            $table->index(['long_ip1', 'long_ip2']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('geo_base');
    }
}

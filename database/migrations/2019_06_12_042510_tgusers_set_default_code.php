<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TgusersSetDefaultCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tgusers', function (Blueprint $table) {
            $table->string( 'code' )->default('')->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string( 'email'    )->nullable()->default(null)->change();
            $table->string( 'password' )->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

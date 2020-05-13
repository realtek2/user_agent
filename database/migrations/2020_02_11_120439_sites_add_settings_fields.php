<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SitesAddSettingsFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('visits')->default(true);
            $table->boolean('start_of_input')->default(true);
            $table->boolean('form_submission')->default(true);
            $table->boolean('clicks_on_phone')->default(true);
            $table->boolean('clicks_on_whatsapp')->default(true);
            $table->boolean('whatsapp_id')->default(true);
            $table->boolean('deleted')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('visits');
            $table->dropColumn('start_of_input');
            $table->dropColumn('form_submission');
            $table->dropColumn('clicks_on_phone');
            $table->dropColumn('clicks_on_whatsapp');
            $table->dropColumn('whatsapp_id');
            $table->dropColumn('deleted');
        });
    }
}

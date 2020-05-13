<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SitesAddWhatsappWidgetFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->bigInteger('wb_widget_phone')->nullable();
            $table->string('wb_widget_text')->default('');
            $table->boolean('wb_widget_state')->default(false);
            $table->boolean('wb_widget_desktop_state')->default(true);
            $table->boolean('wb_widget_mobile_state')->default(true);
            $table->boolean('wb_widget_show_side')->default(true);
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
            $table->dropColumn('wb_widget_phone');
            $table->dropColumn('wb_widget_text');
            $table->dropColumn('wb_widget_state');
            $table->dropColumn('wb_widget_desktop_state');
            $table->dropColumn('wb_widget_mobile_state');
            $table->dropColumn('wb_widget_show_side');
        });
    }
}

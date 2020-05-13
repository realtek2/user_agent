<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ActionsAddFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->string( 'browser'    )->default('');
            $table->string( 'browser_v'  )->default('');
            $table->string( 'platform'   )->default('');
            $table->string( 'platform_v' )->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->dropColumn( 'browser'    );
            $table->dropColumn( 'browser_v'  );
            $table->dropColumn( 'platform'   );
            $table->dropColumn( 'platform_v' );
        });
    }
}

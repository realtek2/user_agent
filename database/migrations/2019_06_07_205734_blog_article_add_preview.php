<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BlogArticleAddPreview extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
            
     */
    public function up()
    {
        Schema::table('blog_articles', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->string('preview')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}

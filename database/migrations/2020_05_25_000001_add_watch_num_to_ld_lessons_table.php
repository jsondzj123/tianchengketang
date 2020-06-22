<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWatchNumToLdLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ld_lessons', function (Blueprint $table) {
            $table->integer('watch_num')->nullable()->default(0)->comment('观看数');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ld_lessons', function (Blueprint $table) {
            $table->dropColumn('watch_num');
        });
    }
}

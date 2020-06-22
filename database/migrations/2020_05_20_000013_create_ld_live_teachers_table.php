<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLiveTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_live_teachers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('admin_id')->comment('操作员ID');
            $table->integer('live_id')->comment('直播ID');
            $table->integer('live_child_id')->comment('直播课次ID');
            $table->integer('teacher_id')->comment('讲师ID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ld_live_teachers');
    }
}

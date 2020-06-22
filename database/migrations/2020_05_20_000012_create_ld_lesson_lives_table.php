<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLessonLivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_lesson_lives', function (Blueprint $table) {
            $table->integer('lesson_id')->unsigned()->comment('课程ID');
            $table->integer('live_id')->unsigned()->comment('直播资源ID');
            $table->timestamps();

            $table->primary(['lesson_id', 'live_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ld_lesson_lives');
    }
}

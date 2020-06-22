<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLessonVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_lesson_videos', function (Blueprint $table) {
            $table->integer('child_id')->unsigned()->comment('课程小节ID');
            $table->integer('video_id')->unsigned()->comment('录播资源ID');
            $table->timestamps();

            $table->foreign('child_id')->references('id')->on('ld_lesson_childs')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('video_id')->references('id')->on('ld_videos')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('child_id');
            $table->index('video_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ld_lesson_videos');
    }
}

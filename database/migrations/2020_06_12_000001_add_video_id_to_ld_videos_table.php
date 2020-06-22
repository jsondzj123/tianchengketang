<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVideoIdToLdVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ld_videos', function (Blueprint $table) {
            $table->integer('course_id')->default(0)->comment('欢拓课程ID');
            $table->integer('mt_video_id')->default(0)->comment('欢拓视频ID');
            $table->string('mt_video_name')->nullable()->comment('欢拓视频标题');
            $table->string('mt_url')->nullable()->comment('欢拓视频临时观看地址'); 
            $table->integer('mt_duration')->default(0)->comment('时长(秒)');
            $table->integer('start_time')->nullable()->comment('课程的开始时间');
            $table->integer('end_time')->nullable()->comment('课程的结束时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ld_videos', function (Blueprint $table) {
            $table->dropColumn('mt_video_id');
            $table->dropColumn('mt_video_name');
            $table->dropColumn('mt_url');
            $table->dropColumn('mt_duration');
            $table->dropColumn('course_id');
            $table->dropColumn('start_time');
            $table->dropColumn('end_time');
        });
    }
}

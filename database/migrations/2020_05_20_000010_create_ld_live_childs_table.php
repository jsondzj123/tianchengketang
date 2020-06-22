<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLiveChildsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_live_childs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('live_id')->unsigned()->comment('直播ID');
            $table->integer('admin_id')->unsigned()->comment('操作员ID');
            $table->string('course_name')->comment('课程名称');
            $table->integer('account')->comment('接入方主播账号或ID或手机号');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->timestamp('end_time')->nullable()->comment('结束时间');
            $table->string('nickname')->comment('主播的昵称');
            $table->string('accountIntro')->nullable()->comment('主播的简介');
            $table->string('options')->nullable()->comment('其它可选参数');
            $table->string('url')->nullable()->comment('资源地址');
            
            $table->integer('partner_id')->default(0)->comment('合作方ID');
            $table->integer('bid')->default(0)->comment('欢拓系统的主播ID');
            $table->integer('course_id')->default(0)->comment('课程ID');
            $table->string('zhubo_key')->nullable()->comment('主播登录秘钥');
            $table->string('admin_key')->nullable()->comment('助教登录秘钥');
            $table->string('user_key')->nullable()->comment('学生登录秘钥');
            $table->integer('add_time')->nullable()->comment('课程创建时间');

            $table->integer('watch_num')->default(0)->comment('观看人数');
            $table->integer('like_num')->default(0)->comment('点赞人数');
            $table->integer('online_num')->default(0)->comment('在线人数');
                
            $table->tinyInteger('is_free')->default(0)->comment('是否收费：0否1是');
            $table->tinyInteger('is_public')->default(0)->comment('是否公开课：0否1是');
            $table->tinyInteger('modetype')->default(0)->comment('模式：1语音云3大班5小班6大班互动');
            $table->tinyInteger('barrage')->default(0)->comment('是否开启弹幕：0关闭1开启');
            $table->string('robot')->nullable()->comment('虚拟用户数据');

            $table->tinyInteger('status')->default(0)->comment('直播状态');
            $table->tinyInteger('is_del')->default(0)->comment('是否删除：0否1是');
            $table->tinyInteger('is_forbid')->default(0)->comment('是否禁用：0否1是');
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
        Schema::dropIfExists('ld_lives');
    }
}

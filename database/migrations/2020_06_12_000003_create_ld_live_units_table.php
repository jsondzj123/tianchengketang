<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLiveUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_live_units', function (Blueprint $table) {
            $table->increments('id')->comment('课次ID');
            $table->integer('admin_id')->unsigned()->comment('操作员ID');
            $table->integer('class_id')->unsigned()->comment('班号ID');
            $table->string('course_name')->comment('课程名称');
            $table->integer('account')->comment('接入方主播账号或ID或手机号');
            $table->timestamp('start_time')->nullable()->comment('设置开始时间');
            $table->timestamp('end_time')->nullable()->comment('设置结束时间');
            $table->timestamp('live_stime')->nullable()->comment('直播开始时间');
            $table->timestamp('live_etime')->nullable()->comment('直播结束时间');
            $table->string('nickname')->nullable()->comment('主播的昵称');
            $table->string('accountIntro')->nullable()->comment('主播的简介');
            $table->string('options')->nullable()->comment('其它可选参数');
            $table->string('url')->nullable()->comment('资源地址');
            
            $table->integer('partner_id')->default(0)->comment('合作方ID');
            $table->integer('bid')->default(0)->comment('欢拓系统的主播ID');
            $table->integer('course_id')->default(0)->comment('欢拓课程ID');
            $table->string('zhubo_key')->nullable()->comment('主播登录秘钥');
            $table->string('admin_key')->nullable()->comment('助教登录秘钥');
            $table->string('user_key')->nullable()->comment('学生登录秘钥');
            $table->integer('add_time')->nullable()->comment('课程创建时间');
            $table->integer('duration')->default(0)->comment('时长(秒)');
            $table->string('playback_url')->nullable()->comment('回放地址');
            

            $table->integer('live_pv')->default(0)->comment('直播观看次数');
            $table->integer('live_uv')->default(0)->comment('直播观看人数');
            $table->integer('pb_pv')->default(0)->comment('回放观看次数');
            $table->integer('pb_uv')->default(0)->comment('回放观看人数');

            $table->tinyInteger('is_free')->default(0)->comment('是否收费0否1是');
            $table->tinyInteger('is_public')->default(0)->comment('是否公开课0否1是');
            $table->tinyInteger('modetype')->default(0)->comment('模式1语音云3大班5小班6大班互动');
            $table->tinyInteger('barrage')->default(0)->comment('是否开启弹幕0关闭1开启');
            $table->string('robot')->nullable()->comment('虚拟用户数据');

            
            $table->tinyInteger('playback_status')->default(0)->comment('是否生成回放0未生成1已生成');
            $table->tinyInteger('status')->default(0)->comment('直播状态1未开始2进行中3已结束');
            $table->tinyInteger('is_del')->default(0)->comment('是否删除0否1是');
            $table->tinyInteger('is_forbid')->default(0)->comment('是否禁用0否1是');
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
        Schema::dropIfExists('ld_live_units');
    }
}

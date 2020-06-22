<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToLdLiveChildsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ld_live_childs', function (Blueprint $table) {
            $table->tinyInteger('playback')->default(0)->comment('是否生成回放0未生成1已生成');
            $table->string('playbackUrl')->nullable()->comment('回放地址');
            $table->integer('live_stime')->default(0)->comment('直播开始时间');
            $table->integer('live_etime')->default(0)->comment('直播结束时间');
            $table->integer('duration')->default(0)->comment('时长(秒)');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ld_live_childs', function (Blueprint $table) {
            $table->dropColumn('is_recommend');
            $table->dropColumn('playbackUrl');
            $table->dropColumn('live_stime');
            $table->dropColumn('live_etime');
            $table->dropColumn('duration');
        });
    }
}

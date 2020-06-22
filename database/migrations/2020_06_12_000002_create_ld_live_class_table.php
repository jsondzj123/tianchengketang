<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLiveClassTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_live_class', function (Blueprint $table) {
            $table->increments('id')->comment('班号ID');
            $table->integer('admin_id')->unsigned()->comment('操作员ID');
            $table->integer('live_id')->unsigned()->comment('直播ID');
            $table->string('name')->nullable()->comment('班号名称');
            $table->text('description')->nullable()->comment('班号信息');
            $table->tinyInteger('is_del')->default(0)->comment('删除0否1是');
            $table->tinyInteger('is_forbid')->default(0)->comment('禁用0否1是');
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
        Schema::dropIfExists('ld_live_class');
    }
}

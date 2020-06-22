<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLessonStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_lesson_stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('admin_id')->default(0)->comment('操作员ID');
            $table->integer('lesson_id')->default(0)->comment('课程ID');
            $table->integer('school_pid')->default(0)->comment('分配学校ID');
            $table->integer('school_id')->default(0)->comment('被分配分校ID');
            $table->integer('current_number')->nullable()->default(0)->comment('当前库存');
            $table->integer('add_number')->nullable()->default(0)->comment('添加库存数');
            $table->integer('is_del')->nullable()->default(0)->comment('是否删除：0否1是');
            $table->integer('is_forbid')->nullable()->default(0)->comment('是否禁用：0否1是');
            $table->timestamps();

            $table->index('lesson_id');
            $table->index('school_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ld_lesson_stocks');
    }
}

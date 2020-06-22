<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLessonSchoolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_lesson_schools', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('admin_id')->unsigned()->comment('操作员ID');
            $table->integer('lesson_id')->unsigned()->comment('课程ID');
            $table->integer('school_id')->unsigned()->comment('分校ID');
            $table->string('title')->nullable()->comment('课程表题');
            $table->string('keyword')->nullable()->comment('关键词');
            $table->string('cover')->nullable()->comment('封面');
            $table->text('description')->nullable()->comment('描述');
            $table->text('introduction')->nullable()->comment('简介');
            $table->string('url')->nullable()->comment('课程资料');
            $table->decimal('price', 12, 2)->default(0)->comment('定价');
            $table->decimal('favorable_price', 12, 2)->default(0)->comment('优惠价');
            $table->tinyInteger('is_public')->default(0)->comment('是否公开:0否1是');
            $table->tinyInteger('status')->default(0)->comment('课程状态:0未上架1已上架');
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
        Schema::dropIfExists('ld_lesson_schools');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateLdStudentPapersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_student_papers', function (Blueprint $table) {
            //字段设置部分
            $table->increments('id')->comment('自增id');
            $table->integer('student_id')->default(0)->comment('学员id');
            $table->integer('bank_id')->default(0)->comment('题库id');
            $table->integer('subject_id')->default(0)->comment('科目id');
            $table->integer('papers_id')->default(0)->comment('试卷id');
            $table->string('answer_time' , 255)->default('')->comment('耗时时间');
            $table->decimal('answer_score', 10, 2)->nullable()->comment('答题总得分');
            $table->dateTime('create_at')->comment('添加时间');
            $table->dateTime('update_at')->nullable()->comment('更新时间');

            //索引设置部分
            $table->index(['student_id', 'bank_id'], 'index_exam_id');
            
            //引擎设置部分
            $table->engine  = 'InnoDB';
        });
        //设置表注释
        DB::statement("alter table `ld_student_papers` comment '学员模拟试卷提交表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ld_student_papers');
    }
}

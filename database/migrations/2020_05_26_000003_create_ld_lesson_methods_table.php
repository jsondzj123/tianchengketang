<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLessonMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_lesson_methods', function (Blueprint $table) {
            $table->integer('lesson_id')->unsigned()->comment('课程ID');
            $table->integer('method_id')->unsigned()->comment('授课方式ID');
            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('ld_lessons')->onUpdate('cascade')->onDelete('cascade');  
            $table->foreign('method_id')->references('id')->on('ld_methods')->onUpdate('cascade')->onDelete('cascade');  

            $table->primary(['lesson_id', 'method_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ld_lesson_methods');
    }
}

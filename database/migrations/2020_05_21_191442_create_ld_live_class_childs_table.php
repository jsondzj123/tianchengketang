<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLiveClassChildsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_live_class_childs', function (Blueprint $table) {
            $table->integer('live_child_id')->unsigned()->comment('课次ID');
            $table->integer('lesson_child_id')->unsigned()->comment('班号ID');
            $table->timestamps();

            $table->foreign('live_child_id')->references('id')->on('ld_live_childs')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('lesson_child_id')->references('id')->on('ld_lesson_childs')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('live_child_id');
            $table->index('lesson_child_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ld_live_class_childs');
    }
}

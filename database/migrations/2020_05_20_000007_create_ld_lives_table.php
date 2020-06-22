<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdLivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_lives', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('admin_id')->comment('操作员ID');
            $table->string('name')->nullable()->comment('名称');
            $table->string('description')->nullable()->comment('介绍');
            $table->integer('is_del')->default(0)->comment('是否删除：0否1是');
            $table->integer('is_forbid')->default(0)->comment('是否禁用：0否1是');
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

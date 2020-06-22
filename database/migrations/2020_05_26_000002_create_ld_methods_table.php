<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLdMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ld_methods', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('admin_id')->default(0)->comment('操作员id');
            $table->string('name')->comment('授课方式');
            $table->tinyInteger('is_del')->nullable()->default(0)->comment('删除0否1是');
            $table->tinyInteger('is_forbid')->nullable()->default(0)->comment('禁用0否1是');
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
        Schema::dropIfExists('ld_methods');
    }
}

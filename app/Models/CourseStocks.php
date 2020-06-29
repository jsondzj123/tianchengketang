<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class CourseStocks extends Model {
    //指定别的表名
    public $table = 'ld_course_stocks';
    //时间戳设置
    public $timestamps = false;
}
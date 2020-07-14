<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AdminLog;
use App\Models\Exam;
use App\Models\Bank;
use App\Models\QuestionSubject;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class AuthMap extends Model {
    //指定别的表名
    public $table      = 'ld_auth_map';
    //时间戳设置
    public $timestamps = false;




}
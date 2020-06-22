<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveTeacher extends Model {

        //指定别的表名
    public $table = 'ld_live_teachers';

    protected $fillable = [
        'admin_id',
        'live_id',
        'teacher_id',
        'live_child_id',    
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}


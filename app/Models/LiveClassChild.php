<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveClassChild extends Model {

    //指定别的表名
    public $table = 'ld_live_class_childs';

    protected $fillable = [
        'live_child_id',
        'lesson_child_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}


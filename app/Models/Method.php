<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Method extends Model {

    //指定别的表名
    public $table = 'ld_methods';

    protected $fillable = [
        'admin_id',
        'id',
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'is_del',
        'is_forbid',
        'pivot'
    ];

    protected $casts = [
        'id' => 'string'
    ];
}


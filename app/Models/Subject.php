<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Subject extends Model {

    //指定别的表名
    public $table = 'ld_subjects';

	protected $fillable = [
        'id',
    	'admin_id',
        'pid',
        'name',
        'cover',
        'description',
        'status', 
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'pivot'
    ];


    public function childs()
    {
    	return $this->select('id', 'name')->where(['is_del'=> 0, 'is_forbid' => 0, 'pid' =>$this->id])->get();
    }
}


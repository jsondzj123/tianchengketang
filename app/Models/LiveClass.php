<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LiveClass extends Model {

    //指定别的表名
    public $table = 'ld_live_class';

    protected $fillable = [
        'id',
        'admin_id',
        'live_id',
        'name',
        'description',
        'is_del',
        'is_forbid'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'live_class_unit_num',
        'live_class_unit_hours'
    ];

    public function getLiveClassUnitNumAttribute($value)
    {
        return 0;
        //return $this->belongsToMany('App\Models\LiveClassUnit')->count();
    }

    public function getLiveClassUnitHoursAttribute($value)
    {
        return 0;
        //return $this->belongsToMany('App\Models\LiveClassUnit')->count();
    }
}


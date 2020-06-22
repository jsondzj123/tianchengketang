<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Live extends Model {

    //指定别的表名
    public $table = 'ld_lives';

    protected $fillable = [
        'admin_id',
        'name',
        'description',
        'is_use',
    ];

    protected $hidden = [
        'updated_at',
        'pivot'
    ];

    protected $appends = [
        'is_use', 
        'admin', 
        'subject_id', 
        'subject_first_name', 
        'subject_second_name',
        'class_num'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
    
    public function getClassNumAttribute($value)
    {
        return $this->belongsToMany('App\Models\Lesson', 'ld_lesson_lives')->count();
    }

    public function getSubjectFirstNameAttribute($value) {
        $subjects = $this->belongsToMany('App\Models\Subject', 'ld_subject_lives')->where('pid', 0)->first();
        if(!empty($subjects)){
            $name = $subjects['name'];
        }else{
            $name = '';
        }
        return $name;
    }

    public function getSubjectSecondNameAttribute($value) {
        $subjects = $this->belongsToMany('App\Models\Subject', 'ld_subject_lives')->where('pid', '!=', 0)->first();
        if(!empty($subjects)){
            $name = $subjects['name'];
        }else{
            $name = '';
        }
        return $name;
    }

    public function getSubjectIdAttribute($value)
    {
        return $this->belongsToMany('App\Models\Subject', 'ld_subject_lives')->pluck('id');
    }

    public function getIsUseAttribute($value) {
        $num = LessonLive::where('live_id', $this->id)->count();
        if($num > 0){
            return 1;
        }
        return  0;
    }

    public function getAdminAttribute($value) {
        return Admin::find($this->admin_id)->username;
    }

    public function class()
    {
        return $this->hasMany('App\Models\LiveClass');
    }

    public function lessons() {
        return $this->belongsToMany('App\Models\Lesson', 'ld_lesson_lives')->withTimestamps();
    }

    public function subjects() {
        return $this->belongsToMany('App\Models\Subject', 'ld_subject_lives')->withTimestamps();
    }

    public function childs() {
        return $this->hasMany('App\Models\LiveChild', 'live_id');
    }
}


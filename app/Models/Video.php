<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model {


    public $table = 'ld_videos';
	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'admin_id', 'name', 'category', 'url', 'size', 'status', 'mt_video_id', 'mt_video_name', 'mt_url', 'course_id','mt_duration','course_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'status',
        'updated_at',
        'pivot'
    ];


    protected $appends = ['admin', 'subject_id', 'subject_first_name', 'subject_second_name'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function getAdminAttribute($value) {
        $admin = Admin::find($this->admin_id);
        if(!empty($admin)){
            return $admin['username'];
        }
        return '';
    }

    public function getSubjectFirstNameAttribute($value) {
        $subjects = $this->belongsToMany('App\Models\Subject', 'ld_subject_videos')->where('pid', 0)->first();
        if(!empty($subjects)){
            $name = $subjects['name'];
        }else{
            $name = '';
        }
        return $name;
    }

    public function getSubjectSecondNameAttribute($value) {
        $subjects = $this->belongsToMany('App\Models\Subject', 'ld_subject_videos')->where('pid', '!=', 0)->first();
        if(!empty($subjects)){
            $name = $subjects['name'];
        }else{
            $name = '';
        }
        return $name;
    }

    public function getSubjectIdAttribute($value)
    {
        return $this->belongsToMany('App\Models\Subject', 'ld_subject_videos')->pluck('id');
    }

    public function subjects() {
        return $this->belongsToMany('App\Models\Subject', 'ld_subject_videos')->withTimestamps();
    }

}


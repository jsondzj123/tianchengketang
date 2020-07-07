<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\AdminLog;
use App\Models\CouresSubject;
use App\Models\OpenCourse;
use App\Models\OpenCourseTeacher;
use Illuminate\Support\Facades\DB;
use App\Models\Teacher;
use App\Tools\CurrentAdmin;
use App\Tools\MTCloud;
use App\Models\OpenLivesChilds;

class OpenCourseController extends Controller {
	protected $school;
    protected $data;
    public function __construct(){
        $this->data = $_REQUEST;
        // $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
        $this->school = School::where(['dns'=>$_SERVER['SERVER_NAME']])->first();
    }

    //大家都在看
    public function hotList(){
   		//自增的公开课
	    $openCourse = OpenCourse::leftJoin('ld_course_open_live_childs','ld_course_open_live_childs.lesson_id','=','ld_course_open.id')
	    	->leftJoin('ld_course_open_teacher','ld_course_open_teacher.course_id','=','ld_course_open.id')
	    	->leftJoin('ld_lecturer_educationa','ld_course_open_teacher.teacher_id','=','ld_lecturer_educationa.id')
	    	->where(function($query) use ($this->school) {//自增
            	$query->where('ld_course_open.school_id',$this->school['id']);
            	$query->where('ld_course_open.is_del',0);
            	$query->where('ld_course_open.status',1);
            	$query->where('ld_lecturer_educationa.type',2);
        	})->select('ld_course_open.id','ld_course_open.title','ld_course_open.cover','ld_lecturer_educationa.real_name')
        ->orderBy('ld_course_open_live_childs.watch_num','desc')
        ->limit(4)
        ->get()->toArray();
        $count = count($openCourse);
        if($count<4){
        	  $natureOpenCourse = CourseRefOpen::leftJoin('ld_course_open_live_childs','ld_course_open_live_childs.lesson_id','=','ld_course_ref_open.id')
		    	->leftJoin('ld_course_open_teacher','ld_course_open_teacher.course_id','=','ld_course_ref_open.id')
		    	->leftJoin('ld_lecturer_educationa','ld_course_open_teacher.teacher_id','=','ld_lecturer_educationa.id')
		    	->where(function($query) use ($this->school) {//自增
		        	$query->where('ld_course_open.school_id',$this->school['id']);
		        	$query->where('ld_course_open.is_del',0);
		        	$query->where('ld_course_open.status',1);
		        	$query->where('ld_lecturer_educationa.type',2);
		    	})->select('ld_course_open.id','ld_course_open.title','ld_course_open.cover','ld_lecturer_educationa.real_name')
		    ->orderBy('ld_course_open_live_childs.watch_num','desc')
		    ->limit(4-$count)
		    ->get()->toArray();
			$openCourse = array_merge($natureOpenCourse,$openCourse);
        }
        return response()->json(['code'=>200,'msg'=>'Success','data'=>$openCourse]);
    }
    //预开始
    public function PreStart(){
    	//自增的公开课
	    $openCourse = OpenCourse::leftJoin('ld_course_open_live_childs','ld_course_open_live_childs.lesson_id','=','ld_course_open.id')
	    	->leftJoin('ld_course_open_teacher','ld_course_open_teacher.course_id','=','ld_course_open.id')
	    	->leftJoin('ld_lecturer_educationa','ld_course_open_teacher.teacher_id','=','ld_lecturer_educationa.id')
	    	->where(function($query) use ($this->school) {//自增
            	$query->where('ld_course_open.school_id',$this->school['id']);
            	$query->where('ld_course_open.is_del',0);
            	$query->where('ld_course_open.status',1);
            	$query->where('ld_course_open_live_childs.status',1);//预开始
            	$query->where('ld_lecturer_educationa.type',2);
        	})->select('ld_course_open.id','ld_course_open.title','ld_course_open.cover','ld_lecturer_educationa.real_name','ld_course_open.start_at','ld_course_open.end_at')
        ->orderBy('ld_course_open.id','desc')
        ->get()->toArray();
        //授权的公开课
        $natureOpenCourse = CourseRefOpen::leftJoin('ld_course_open_live_childs','ld_course_open_live_childs.lesson_id','=','ld_course_ref_open.id')
		    	->leftJoin('ld_course_open_teacher','ld_course_open_teacher.course_id','=','ld_course_ref_open.id')
		    	->leftJoin('ld_lecturer_educationa','ld_course_open_teacher.teacher_id','=','ld_lecturer_educationa.id')
		    	->where(function($query) use ($this->school) {//自增
		        	$query->where('ld_course_open.school_id',$this->school['id']);
		        	$query->where('ld_course_open.is_del',0);
		        	$query->where('ld_course_open.status',1);
		        	$query->where('ld_course_open_live_childs.status',1);//预开始
		        	$query->where('ld_lecturer_educationa.type',2);
		    	})->select('ld_course_open.id','ld_course_open.title','ld_course_open.cover','ld_lecturer_educationa.real_name','ld_course_open.start_at','ld_course_open.end_at')
		    ->orderBy('ld_course_open.id','desc')
		    ->get()->toArray();
		$openCourseArr = array_merge($openCourse,$natureOpenCourse);
		if(!empty($openCourseArr)){
			foreach($openCourseArr as $key=>&$v){
				$v['start_at'] = date('Y-m-d H:i:s',$v['start_at']);
				$v['end_at'] = date('Y-m-d H:i:s',$v['end_at']);
			}
		}
		return response()->json(['code'=>200,'msg'=>'Success','data'=>$openCourseArr]);
    }
    //直播中
    public function underway(){
    	//自增的公开课
	    $openCourse = OpenCourse::leftJoin('ld_course_open_live_childs','ld_course_open_live_childs.lesson_id','=','ld_course_open.id')
	    	->leftJoin('ld_course_open_teacher','ld_course_open_teacher.course_id','=','ld_course_open.id')
	    	->leftJoin('ld_lecturer_educationa','ld_course_open_teacher.teacher_id','=','ld_lecturer_educationa.id')
	    	->where(function($query) use ($this->school) {//自增
            	$query->where('ld_course_open.school_id',$this->school['id']);
            	$query->where('ld_course_open.is_del',0);
            	$query->where('ld_course_open.status',1);
            	$query->where('ld_course_open_live_childs.status',2);//进行中
            	$query->where('ld_lecturer_educationa.type',2);
        	})->select('ld_course_open.id','ld_course_open.title','ld_course_open.cover','ld_lecturer_educationa.real_name','ld_course_open.start_at','ld_course_open.end_at')
        ->orderBy('ld_course_open.id','desc')
        ->get()->toArray();
        //授权的公开课
        $natureOpenCourse = CourseRefOpen::leftJoin('ld_course_open_live_childs','ld_course_open_live_childs.lesson_id','=','ld_course_ref_open.id')
		    	->leftJoin('ld_course_open_teacher','ld_course_open_teacher.course_id','=','ld_course_ref_open.id')
		    	->leftJoin('ld_lecturer_educationa','ld_course_open_teacher.teacher_id','=','ld_lecturer_educationa.id')
		    	->where(function($query) use ($this->school) {//自增
		        	$query->where('ld_course_open.school_id',$this->school['id']);
		        	$query->where('ld_course_open.is_del',0);
		        	$query->where('ld_course_open.status',1);
		        	$query->where('ld_course_open_live_childs.status',2);//进行中
		        	$query->where('ld_lecturer_educationa.type',2);
		    	})->select('ld_course_open.id','ld_course_open.title','ld_course_open.cover','ld_lecturer_educationa.real_name','ld_course_open.start_at','ld_course_open.end_at')
		    ->orderBy('ld_course_open.id','desc')
		    ->get()->toArray();
		$openCourseArr = array_merge($openCourse,$natureOpenCourse);
		if(!empty($openCourseArr)){
			foreach($openCourseArr as $key=>&$v){
				$v['start_at'] = date('Y-m-d H:i:s',$v['start_at']);
				$v['end_at'] = date('Y-m-d H:i:s',$v['end_at']);
			}
		}
		return response()->json(['code'=>200,'msg'=>'Success','data'=>$openCourseArr]);
    }
    //已结束
    //暂时先不做分页
    public function end(){
    	//自增的公开课
    	$page = !isset($this->data['page']) || $this->data['page'] <=1 ? 1:$this->data['page'];
	    $openCourse = OpenCourse::leftJoin('ld_course_open_live_childs','ld_course_open_live_childs.lesson_id','=','ld_course_open.id')
	    	->leftJoin('ld_course_open_teacher','ld_course_open_teacher.course_id','=','ld_course_open.id')
	    	->leftJoin('ld_lecturer_educationa','ld_course_open_teacher.teacher_id','=','ld_lecturer_educationa.id')
	    	->where(function($query) use ($this->school) {//自增
            	$query->where('ld_course_open.school_id',$this->school['id']);
            	$query->where('ld_course_open.is_del',0);
            	$query->where('ld_course_open.status',1);
            	$query->where('ld_course_open_live_childs.status',3);//已结束
            	$query->where('ld_lecturer_educationa.type',2);
        	})->select('ld_course_open.id','ld_course_open.title','ld_course_open.cover','ld_lecturer_educationa.real_name','ld_course_open.start_at','ld_course_open.end_at')
        ->orderBy('ld_course_open.id','desc')
        ->get()->toArray();
        //授权的公开课
        $natureOpenCourse = CourseRefOpen::leftJoin('ld_course_open_live_childs','ld_course_open_live_childs.lesson_id','=','ld_course_ref_open.id')
		    	->leftJoin('ld_course_open_teacher','ld_course_open_teacher.course_id','=','ld_course_ref_open.id')
		    	->leftJoin('ld_lecturer_educationa','ld_course_open_teacher.teacher_id','=','ld_lecturer_educationa.id')
		    	->where(function($query) use ($this->school) {//自增
		        	$query->where('ld_course_open.school_id',$this->school['id']);
		        	$query->where('ld_course_open.is_del',0);
		        	$query->where('ld_course_open.status',1);
		        	$query->where('ld_course_open_live_childs.status',3);//已结束
		        	$query->where('ld_lecturer_educationa.type',2);
		    	})->select('ld_course_open.id','ld_course_open.title','ld_course_open.cover','ld_lecturer_educationa.real_name','ld_course_open.start_at','ld_course_open.end_at')
		    ->orderBy('ld_course_open.id','desc')
		    ->get()->toArray();
		$openCourseArr = array_merge($openCourse,$natureOpenCourse);
		if(!empty($openCourseArr)){
			foreach($openCourseArr as $key=>&$v){
				$v['start_at'] = date('Y-m-d H:i:s',$v['start_at']);
				$v['end_at'] = date('Y-m-d H:i:s',$v['end_at']);
			}
		}
		return response()->json(['code'=>200,'msg'=>'Success','data'=>$openCourseArr]);
    }
}
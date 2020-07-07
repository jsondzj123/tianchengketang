<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Coures;
use App\Models\Couresmethod;
use App\Models\CourseRefResource;
use App\Models\CourseSchool;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Articletype;
use App\Models\FootConfig;
use App\Models\Admin;
use App\Models\CouresSubject;
use App\Models\Article;
use App\Models\Teacher;
use App\Models\CourseRefTeacher;
class TeacherController extends Controller {
	protected $school;
    protected $data;
    public function __construct(){
        $this->data = $_REQUEST;
        // $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
        $this->school = School::where(['dns'=>$_SERVER['SERVER_NAME']])->first();
    }
	public function getList(){
		$type = !isset($this->data['type']) || $this->data['type']<=0 ?0:$this->data['type'];
		if($type == 0 || $type != 1){
			$teacherArr = Teacher::where(['school_id'=>$this->school['id'],'is_del'=>0,'is_forbid'=>0,'type'=>2])->select('id','head_icon','real_name','describe','number','is_recommend')->orderBy('is_recommend','desc')->get();
		}else{
			$teacherArr = Teacher::where(['school_id'=>$this->school['id'],'is_del'=>0,'is_forbid'=>0,'type'=>2])->select('id','head_icon','real_name','describe','number','is_recommend')->orderBy('number','desc')->get();
		}
		if(!empty($teacherArr)){
			foreach($teacherArr as $k=>&$v){
				$course = Couresteacher::where('teacher_id',$v['id'])->pluck('course_id')->get()->toArray();
				if(!empty($course)){
					$v['student_num'] = Order::whereIn('class_id',$course)->where(['school_id'=>$this->school['id'],'pay_type'=>2,'oa_status'=>1])->whereIn('pay_status',[3,4])->count();
				}else{
					$v['student_num'] = 0;
				}
			}
		}     
		//授权讲师
		$teacherIds = CourseRefTeacher::leftJoin('ld_lecturer_educationa','ld_lecturer_educationa.id','=','ld_course_ref_teacher.teacher_id')
        						->where(['ld_course_teacher.course_id'=>$v['id'],'ld_lecturer_educationa.type'=>2])->get()->toArray();//授权公开课的讲师          
		if(!empty($teacherIds)){

			foerach($teacherIds as $key=>$v){
			//公开课

			//课程
			}
		}
		




		$natureCourse = CourseSchool::where(function($query) use ($body,$this->school) {
                    $query->where('ld_course_school.to_school_id',$this->school['id']); //被授权学校  
                    $query->where('ld_course_school.is_del',0);
            })->select('id')->get()->toArray(); //授权课程
        if(!empty($natureCourse)){
        	foreach($natureCourse as $key=>$v){
        		$teacher[] = Couresteacher::leftJoin('ld_lecturer_educationa','ld_lecturer_educationa.id','=','ld_course_teacher.teacher_id')
        						->where(['ld_course_teacher.course_id'=>$v['id'],'ld_lecturer_educationa.type'=>2])->first()->toArray();
        	}
        	foreach($teacher as $k=>$v){

        	}
        }
		
}
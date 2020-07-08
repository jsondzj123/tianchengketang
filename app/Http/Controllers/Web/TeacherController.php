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
    //列表
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
	}
	//详情页
	public function dateils(){
		if(!isset($this->data['teacher_id']) || empty($this->data['teacher_id']) || $this->data['teacher_id'] < 0 ){
			return response()->json(['code'=>201,'msg'=>'教师标识为空或类型不合法']);
		}
		if(!isset($this->data['is_nature']) || empty($this->data['is_nature'])){
			return response()->json(['code'=>201,'msg'=>'类型标识为空或类型不合法']);
		}
		$teacherInfo = Teacher::where(['id'=>$thid->data['teacher_id']])->get();
		$teacherInfo['star'] = 5;//星数
		$teacherInfo['grade'] = '5.0';//评分
		$teacherInfo['evaluate'] = 0; //评论数
		$teacherInfo['class_number']= 0;//开课数量
		$teacherInfo['student_num']= 0;//学员数量
		$teacherInfo['comment'] = [];//评论
		$teacherInfo['course'] = [];//课程信息
		if($this->data['is_nature'] == 1){
			//授权讲师
			$arr= [];
			$data = CourseSchool::leftJoin('ld_course_teacher','ld_course_teacher.course_id','=','ld_course_school.course_id')
						->leftJoin('ld_lecturer_educaiona','ld_lecturer_educaiona.ld','=','ld_course_teacher.teacher_id',)
						->where(['ld_course_school.is_del'=>0,'ld_course_school.to_school_id'=>$this->school['id'],'ld_course_school.status'=>1,'ld_lecturer_educaiona.type'=>2])
						->select('ld_course_school.cover','ld_course_school.title','ld_course_school.pricing','ld_course_school.buy_num','ld_lecturer_educaiona.id','ld_course_school.course_id')
						->get()->toArray();
			foreach($data as $key=>$v){
				if($v['id'] == $this->data['teacher_id']){
					$arr[] = $v;
				}
			}
			if(!empty($arr)){
				$teacherInfo['class_number'] = count($arr);//开课数量
				$sum =0;
				foreach($arr as $k=>&$v){

					$v['buy_num'] += Order::where(['school_id'=>$this->school['id'],'nature'=>1,'status'=>2,'class_ld'=>$v['course_id']])->whereIn('pay_status',[3,4])->count();
					$sum+=$v['buy_num'];
				}
				$teacherInfo['student_num'] = $sum;//学员数量
				$teacherInfo['course'] = $arr;
			}
			return ['code'=>200,'msg'=>'Success','data'=>$teacherInfo]
		}else{
			//自增讲师
		}

	}	     
}
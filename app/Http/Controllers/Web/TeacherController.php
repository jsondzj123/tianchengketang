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
		$teacherInfo = Teacher::where(['id'=>$thid->data['teacher_id'],'school_id'=>$this->school['id']])->get();
		$teacherInfo['star'] = 5;//星数
		$teacherInfo['grade'] = '5.0';//评分
		$teacherInfo['evaluate'] = '5.0'; //评论数
		

	}	     
}
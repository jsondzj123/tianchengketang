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


class IndexController extends Controller {
    protected $school;
    protected $data;
    public function __construct(){
        $this->data = $_REQUEST;
        // $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
        $this->school = School::where(['dns'=>$this->data['dns']])->first();
    }
    //讲师列表
    public function teacherList(){
    	$limit = !isset($this->data['limit']) || empty($this->data['limit']) || $this->data['limit'] <= 0 ? 8 : $this->data['limit'];
    	$recomendTeacherArr = Teacher::where(['school_id'=>$this->school['id'],'is_del'=>0,'type'=>2])->orderBy('number','desc')->select('id','head_icon','real_name','describe','number')->limit($limit)->get()->toArray();

    	$count = count($recomendTeacherArr);
    	if($count<$limit){
    		$teacherData = Teacher::where(['school_id'=>$this->school['id'],'is_del'=>0,'type'=>2])->orderBy('number','desc')->select('id','head_icon','real_name','describe','number','teacher_icon')->limit($limit-$count)->get()->toArray();
    		$recomendTeacherArr=array_merge($recomendTeacherArr,$teacherData);
    	}
   		
    	return response()->json(['code'=>200,'msg'=>'Success','data'=>$recomendTeacherArr]);
    }
    //新闻资讯
    public function newInformation(){
    	$limit = !isset($this->data['limit']) || empty($this->data['limit']) || $this->data['limit']<=0 ? 4 : $this->data['limit'];
    	$where = ['ld_article_type.school_id'=>$this->school['id'],'ld_article_type.status'=>1,'ld_article_type.is_del'=>1,'ld_article.status'=>1,'ld_article.is_del'=>1];
    	$news = Articletype::leftJoin('ld_article','ld_article.article_type_id','=','ld_article_type.id')
             ->where($where)
             ->limit($limit)->get();
        return response()->json(['code'=>200,'msg'=>'Success','data'=>$news]);
    }
    //页脚
    public function footer(){
    	$footer = [];
    	$admin = Admin::where('school_id',$this->school['id'])->select('school_status')->first();
    	if(!empty($admin)){
    		if($admin['school_status'] > 0){
    			$footer = FootConfig::where(['school_id'=>$this->school['id'],'is_del'=>0,'is_open'=>0])->select('id','parent_id','name','url','text','create_at')->get();
		    	if(!empty($footer)){
		    		$footer = getParentsList($footer);
		    	}
    		}
    	}
    	return response()->json(['code'=>200,'msg'=>'Success','data'=>$footer]);
    }
    //精品课程
    public function course(){
       
    	$course =  $zizengCourseData = $natureCourseData = [];
    	$subjectOne = CouresSubject::where(['school_id'=>$this->school['id'],'is_open'=>0,'is_del'=>0,'parent_id'=>0])->pluck('id')->toArray();//自增学科大类

    	$natureCourseIds = CourseSchool::where(['to_school_id'=>$this->school['id'],'is_del'=>0])->pluck('course_id')->toArray();//授权课程

        $natuerSubjectOne =[];
    	if(empty($subjectOne) && empty($natureCourseIds) ){
    		$course = [];
    	}else{
    		if(!empty($natureCourseIds)){
    			$natuerSubjectOne = Coures::whereIn('id',$natureCourseIds)->where(['is_del'=>0,'status'=>1])->pluck('parent_id as id')->toArray();//授权学科大类
                
    			foreach($natuerSubjectOne as $key=>$v){
    				 $natureCourseData[] = CourseSchool::leftJoin('ld_course','ld_course.id','=','ld_course_school.course_id')
				            ->where('ld_course_school.from_school_id',$this->school['id']) //授权学校
				            ->where('ld_course_school.is_del',0)
				            ->select('ld_course_school.course_id as id','ld_course.parent_id','ld_course.child_id','ld_course.title')->limit(8)->get()->toArray(); //授权课程
    			}
       		}
    		$subjectIds = array_merge($subjectOne,$natuerSubjectOne);

    		$subjectIds	= array_unique($subjectIds);

    		$course['subjectOne'] = CouresSubject::whereIn('id',$subjectIds)->where(['is_del'=>0,'is_open'=>0])->select('id','subject_name')->get();//授权/自增学科大类	
    		if(!empty($subjectOne)){
    			foreach($subjectOne as $key=>$v){
    				$zizengCourseData[$v] = Coures::where(['parent_id'=>$v,'status'=>1,'is_del'=>0])->limit(8)->get()->toArray();
    			}
    		}

            if(!empty($natureCourseData)){
                $course['course'] = array_merge($natureCourseData,$zizengCourseData);
            }else{
                 $course['course'] =$zizengCourseData;
            }
    		
    	}
        return response()->json(['code'=>200,'msg'=>'Success','data'=>$course]);
    }	
}
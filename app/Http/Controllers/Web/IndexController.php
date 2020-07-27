<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Coures;
use App\Models\Couresmethod;
use App\Models\CourseRefResource;
use App\Models\CourseRefTeacher;
use App\Models\CourseSchool;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Articletype;
use App\Models\FootConfig;
use App\Models\Admin;
use App\Models\CouresSubject;
use App\Models\Subject;

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
    	$limit = 8;
        $courseRefTeacher = CourseRefTeacher::leftJoin('ld_lecturer_educationa','ld_lecturer_educationa.id','=','ld_course_ref_teacher.teacher_id')
            ->where(['to_school_id'=>$this->school['id'],'type'=>2])->limit($limit)->get()->toArray();
        $courseRefTeacher = array_unique($courseRefTeacher, SORT_REGULAR);
        $count = count($courseRefTeacher);
    	if($count<$limit){
    		$teacherData = Teacher::where(['school_id'=>$this->school['id'],'is_del'=>0,'type'=>2])->orderBy('number','desc')->select('id','head_icon','real_name','describe','number','teacher_icon')->limit($limit-$count)->get()->toArray();
    		$recomendTeacherArr=array_merge($courseRefTeacher,$teacherData);
    	}else{
            $recomendTeacherArr = $courseRefTeacher;
        }
        if(!empty($recomendTeacherArr)){
            foreach ($recomendTeacherArr as $k => &$v) {
                $v['star_num'] = 5;
            }
        }
    	return response()->json(['code'=>200,'msg'=>'Success','data'=>$recomendTeacherArr]);
    }
    //新闻资讯
    public function newInformation(){

    	$limit = !isset($this->data['limit']) || empty($this->data['limit']) || $this->data['limit']<=0 ? 4 : $this->data['limit'];
    	$where = ['ld_article_type.school_id'=>$this->school['id'],'ld_article_type.status'=>1,'ld_article_type.is_del'=>1];
    	$news = Articletype::leftJoin('ld_article','ld_article.article_type_id','=','ld_article_type.id')
             ->where($where)
             ->limit($limit)->get();

        return response()->json(['code'=>200,'msg'=>'Success','data'=>$news]);
    }
    //首页信息
    public function index(){
    	$arr = [];

        $arr['logo'] = empty($this->school['logo_url'])?'':$this->school['logo_url'];

        $arr['header'] = $arr['footer'] = $arr['icp'] = [];
    	$admin = Admin::where('school_id',$this->school['id'])->select('school_status')->first();

		$footer = FootConfig::where(['school_id'=>$this->school['id'],'is_del'=>0,'is_open'=>0,'is_show'=>0,'type'=>2])->select('id','parent_id','name','url','create_at')->get();
    	if(!empty($footer)){
    		$arr['footer'] = getParentsList($footer);
    	}
        $icp = FootConfig::where(['school_id'=>$this->school['id'],'is_del'=>0,'is_open'=>0,'is_show'=>0,'type'=>3])->select('name')->first();
        if(empty($icp)){
            $arr['icp'] = '';
        }else{
            $arr['icp'] = $icp['name'];
        }
        $arr['header'] = FootConfig::where(['school_id'=>$this->school['id'],'is_del'=>0,'is_open'=>0,'is_show'=>0,'type'=>1])->select('id','parent_id','name','url','create_at')->orderBy('sort')->get();
        $logo =  FootConfig::where(['school_id'=>$this->school['id'],'is_del'=>0,'is_open'=>0,'is_show'=>0,'type'=>4])->select('logo')->orderBy('sort')->first();
        if(empty($logo)){
            $arr['index_logo'] = '';
        }else{
            $arr['index_logo'] = $logo['logo'];
        }
        $arr['status'] = $admin['school_status'];
    	return response()->json(['code'=>200,'msg'=>'Success','data'=>$arr]);
    }
    //精品课程
    public function course(){
    	$course =  $zizengCourseData = $natureCourseData = $CouresData = [];
    	$subjectOne = CouresSubject::where(['school_id'=>$this->school['id'],'is_open'=>0,'is_del'=>0,'parent_id'=>0])->select('id')->get()->toArray();//自增学科大类
        $natuerSubjectOne = CourseSchool::select('parent_id')->where(['to_school_id'=>$this->school['id'],'is_del'=>0,'status'=>1])->select('parent_id as id')->groupBy('parent_id')->get()->toArray();//授权学科大类
        if(!empty($natuerSubjectOne)){
            foreach($natuerSubjectOne as $key=>&$v){
                $subject_name= Subject::where(['id'=>$v['id'],'is_del'=>0])->select('subject_name')->first();
                $v['subject_name'] =$subject_name['subject_name'];
            }
        }
        if(!empty($subjectOne)){
            foreach($subjectOne as $key=>&$va){
                $subject_name = Subject::where(['id'=>$va['id'],'is_del'=>0])->select('subject_name')->first();
                 $va['subject_name'] =$subject_name['subject_name'];
            }
        }

        if(!empty($subjectOne)&& !empty($natuerSubjectOne)){

             $subject=array_merge($subjectOne,$natuerSubjectOne);
             $last_names = array_column($subject,'id');
             array_multisort($last_names,SORT_ASC,$subject);
        }else{
            $subject = empty($subjectOne) ?$natuerSubjectOne:$subjectOne;
        }
        $newArr = [];

        foreach ($subject as $key => $val) {
            $natureCourseData = CourseSchool::where(['to_school_id'=>$this->school['id'],'is_del'=>0,'parent_id'=>$val['id'],'status'=>1])->limit(8)->get()->toArray();//授权课程
            $count = count($natureCourseData);
            if($count<8){
                $CouresData =Coures::where(['school_id'=>$this->school['id'],'is_del'=>0,'parent_id'=>$val['id'],'status'=>1])->limit(8-$count)->get()->toArray(); //自增
            }

            if(!empty($CouresData)){
                    foreach($CouresData as $key=>&$zizeng){
                        $zizeng['nature'] = 0;
                    }
                }
            if(!empty($natureCourseData)){
                foreach($natureCourseData as $key=>&$nature){
                    $nature['nature'] = 1;
                }
            }
            if(!empty($natureCourseData)&& !empty($CouresData)){
                $courseArr =array_merge($natureCourseData,$CouresData);
            }else{
                $courseArr = empty($natureCourseData) ?$CouresData:$natureCourseData;
            }
           $newArr[$val['id']] = $courseArr;
        }

    	$arr = [
            'course'=>$newArr,
            'subjectOne'=>$subject,
        ];
        return response()->json(['code'=>200,'msg'=>'Success','data'=>$arr]);
    }
    //获取公司信息
    public function getCompany(){
        $company['name'] = isset($this->school['name']) ?$this->school['name']:'';
        $company['account_name'] = isset($this->school['account_name']) ?$this->school['account_name']:'';
        $company['account_num'] =  isset($this->school['account_num']) ?$this->school['account_num']:'';
        $company['open_bank'] =  isset($this->school['open_bank']) ?$this->school['open_bank']:'';
        return response()->json(['code'=>200,'msg'=>'Success','data'=>$company]);
    }
    public function getPay(){
        if(!isset($this->data['id']) || $this->data['id'] <=0){
            return response()->json(['code'=>201,'msg'=>'id为空或类型不合法']);
        }
        $FootConfigArr =FootConfig::where(['id'=>$this->data['id'],'is_del'=>0,'is_show'=>0])->select('text')->first();
        return response()->json(['code'=>200,'msg'=>'Success','data'=>$FootConfigArr]);
    }
}

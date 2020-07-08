<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Coures;
use App\Models\Coureschapters;
use App\Models\Couresmethod;
use App\Models\CouresSubject;
use App\Models\Couresteacher;
use App\Models\CourseLiveResource;
use App\Models\CourseRefResource;
use App\Models\CourseSchool;
use App\Models\Order;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Video;
use App\Models\FootConfig;
class FooterController extends Controller {
    protected $school;
    protected $data;

    public function __construct(){
        $this->data = $_REQUEST;
        $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
    }
    //详情
    public function details(){
    	if(!isset($this->data['parent_id']) || $this->data['parent_id']<=0){ //
    		return response()->json(['code'=>201,'msg'=>'父级标识为空或数据不合法']);
    	}
    	if(!isset($this->data['id']) || $this->data['id']<=0){
    		return response()->json(['code'=>201,'msg'=>'id为空或数据不合法']);
    	}	

    	$left_navigation_bar = FootConfig::where(['parent_id'=>$this->data['parent_id'],'is_del'=>0,'is_open'=>0])->get();
    	$data = FootConfig::where(['id'=>$this->data['id'],'is_del'=>0,'is_open'=>0])->first();
    	if($data['name'] == '对公账户'){
    		$data['company'] = $this->school;
    	}
    	if($data['name'] == '名师简介'){
    			$teacherArr = Teacher::where(['school_id'=>$this->school['id'],'is_del'=>0,'is_forbid'=>0,'type'=>2])->select('id','head_icon','real_name','describe','number','is_recommend')->orderBy('number','desc')->get();
    			
    			$natureTeacherArr = Teacher::where(['school_id'=>$this->school['id'],'is_del'=>0,'is_forbid'=>0,'type'=>2])->select('id','head_icon','real_name','describe','number','is_recommend')->orderBy('number','desc')->get();
    	}
    	return response()->json('code'=>200,'msg'=>'Success','data'=>$data,'left_list'=>$left_navigation_bar);
    }


}
<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Coures;
use App\Models\Couresmethod;
use App\Models\CourseRefResource;
use App\Models\CourseSchool;
use App\Models\School;

class CourseController extends Controller {
    protected $school;
    protected $data;
    public function __construct(){
        $this->data = $_REQUEST;
        $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
    }
    /*
         * @param  课程列表
         * @param  author  苏振文
         * @param  ctime   2020/7/4 17:09
         * return  array
     */
    public function courseList(){
        $school_id = $this->school['id'];
        //每页显示的条数
        $pagesize = (int)isset($this->data['pageSize']) && $this->data['pageSize'] > 0 ? $this->data['pageSize'] : 20;
        $page     = isset($this->data['page']) && $this->data['page'] > 0 ? $this->data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        $count1 = Coures::select('id','title','cover','sale_price','buy_num')->where(['school_id'=>$school_id])->count();
        $count2 = Coures::where(['to_school_id'=>$school_id,'is_del'=>0])->count();
        $count = $count1 + $count2;
        //自增课程
        $course = Coures::select('id','title','cover','sale_price','buy_num','nature')->where(['school_id'=>$school_id,'is_del'=>0,'status'=>1])->get()->toArray();
        //授权课程
        $ref_course = CourseSchool::select('id','title','cover','sale_price','buy_num')->where(['to_school_id'=>$school_id,'is_del'=>0,'status'=>1])->get()->toArray();
        foreach ($ref_course as $ks=>&$vs){
            $method = Couresmethod::select('method_id')->where(['course_id'=>$vs['id'],'is_del'=>0])->get()->toArray();
            $v['method'] = $method;
            $v['nature'] = 1;
        }
        //授课类型
        foreach ($course as $k=>&$v){
            $method = Couresmethod::select('method_id')->where(['course_id'=>$v['id'],'is_del'=>0])->get()->toArray();
            $v['method'] = $method;
        }
        //两数组合并 排序


    }
}


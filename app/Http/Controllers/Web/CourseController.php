<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Coures;
use App\Models\Couresmethod;
use App\Models\CouresSubject;
use App\Models\Couresteacher;
use App\Models\CourseRefResource;
use App\Models\CourseSchool;
use App\Models\Order;
use App\Models\School;
use App\Models\Teacher;

class CourseController extends Controller {
    protected $school;
    protected $data;
    public function __construct(){
        $this->data = $_REQUEST;
        $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
    }
    /*
         * @param  学科列表
         * @param  author  苏振文
         * @param  ctime   2020/7/6 15:38
         * return  array
         */
    public function subjectList(){
        //自增学科
        $subject = CouresSubject::where(['school_id'=>$this->school,'parent_id'=>0,'is_open'=>0,'is_del'=>0])->get()->toArray();
        if(!empty($subject)){
            foreach ($subject as $k=>&$v){
                $subject = CouresSubject::where(['parent_id'=>$v['id'],'is_open'=>0,'is_del'=>0])->get()->toArray();
                $v['son'] = $subject;
            }
        }
        //授权学科
        $course = CourseSchool::select('ld_course.parent_id')->leftJoin('ld_course','ld_course.id','=','ld_course_school.course_id')
                  ->where(['ld_course_school.to_school_id'=>$this->school['id'],'ld_course_school.is_del'=>0,'ld_course.is_del'=>0])->groupBy('ld_course.parent_id')->get()->toArray();
        if(!empty($course)){
            foreach ($course as $ks=>$vs){
                $ones = CouresSubject::where(['id'=>$v['parent_id'],'parent_id'=>0,'is_open'=>0,'is_del'=>0])->first()->toArray();
                if(!empty($ones)){
                    $ones['son'] = CouresSubject::where(['parent_id'=>$v['parent_id'],'is_open'=>0,'is_del'=>0])->get()->toArray();
                }
                array_push($subject,$ones);
            }
        }
        return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>$subject]);
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
        //学科大类小类条件
        if(!empty($this->data['parent'])){
            $parent = json_decode($this->data['parent'],true);
            $where=$wheres=[];
            if(!empty($parent[0])){
                $where['parent_id'] = $parent[0];
                $wheres['ld_course.parent_id'] = $parent[0];
            }
            if(!empty($parent[1])){
                $where['child_id'] = $parent[1];
                $wheres['ld_course.child_id'] = $parent[1];
            }
        }
        //授课类型条件
        if(!empty($this->data['method'])) {
            $methodwhere['method_id'] = $this->data['method'];
        }
        //总条数
        $count1 = Coures::where(['school_id'=>$school_id,'is_del'=>0])->where($where)->count();
        $count2 = CourseSchool::leftJoin('ld_course','ld_course.id','=','ld_course_school.course_id')
           ->where(['to_school_id'=>$school_id,'is_del'=>0])->where($wheres)->count();
        $count = $count1 + $count2;
        //自增课程
        $name = isset($this->data['name'])?$this->data['name']:'';
        $course=[];
        if($count1 != 0){
            $course = Coures::select('id','title','cover','sale_price','buy_num','nature','watch_num','create_at')
                ->where($where)
                ->where(['school_id'=>$school_id,'is_del'=>0,'status'=>1])
                ->where('title','like','%'.$name.'%')
                ->get()->toArray();
            foreach ($course as $k=>&$v){
                $method = Couresmethod::select('method_id')->where(['course_id'=>$v['id'],'is_del'=>0])->where($methodwhere)->get()->toArray();
                if(!empty($method)){
                    $v['method'] = $method;
                }else{
                    unset($course[$k]);
                }
            }
        }
        $ref_course=[];
        //授权课程
        if($count2 != 0){
            $ref_course = CourseSchool::select('ld_course_school.course_id','ld_course_school.title','ld_course_school.cover','ld_course_school.sale_price','ld_course_school.buy_num','ld_course_school.watch_num','ld_course_school.create_at')
                ->leftJoin('ld_course','ld_course.id','=','ld_course_school.course_id')
                ->where($wheres)->where(['ld_course_school.to_school_id'=>$school_id,'ld_course_school.is_del'=>0,'ld_course_school.status'=>1])
                ->where('ld_course_school.title','like','%'.$name.'%')
                ->get()->toArray();
            foreach ($ref_course as $ks=>&$vs){
                $method = Couresmethod::select('method_id')->where(['course_id'=>$vs['course_id'],'is_del'=>0])->where($methodwhere)->get()->toArray();
                if(!empty($method)){
                    $v['method'] = $method;
                    $v['nature'] = 1;
                }else{
                    unset($ref_course[$ks]);
                }
            }
        }
        //两数组合并 排序
        if(!empty($course) && !empty($ref_course)){
            $all = array_merge($course,$ref_course);//合并两个二维数组
        }else{
            $all = !empty($course)?$course:$ref_course;
        }
        //sort 1最新2最热  默认最新
        $sort = isset($this->data['sort'])?$this->data['sort']:1;
        if($sort == 1){
            $date = array_column($all, 'create_at');
            array_multisort($date,SORT_DESC,$all);
        }else if($sort == 2){
            $date = array_column($all, 'watch_num');
            array_multisort($date,SORT_DESC,$all);
        }
        $res = array_slice($all,($page-1)*$pagesize,$pagesize);
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>$res,'page'=>$page,'where'=>$this->data]);
    }
    /*
         * @param  课程详情
         * @param  author  苏振文
         * @param  ctime   2020/7/6 17:50
         * return  array
         */
    public function courseDetail(){
        //课程基本信息
        $course = Coures::where(['id'=>$this->data['id'],'is_del'=>0])->first()->toArray();
        if(!$course){
            return response()->json(['code' => 201 , 'msg' => '无查看权限']);
        }
        //分类信息
        $parent = CouresSubject::select('id','subject_name')->where(['id'=>$course['parent_id'],'parent_id'=>0,'is_del'=>0,'is_open'=>0])->first();
        $child = CouresSubject::select('subject_name')->where(['id'=>$course['child_id'],'parent_id'=>$parent['id'],'is_del'=>0,'is_open'=>0])->first();
        $course['parent_name'] = $parent['subject_name'];
        $course['child_name'] = $child['subject_name'];
        unset($course['parent_id']);
        unset($course['child_id']);
        //授课方式
        $method = Couresmethod::select('method_id')->where(['course_id'=>$this->data['id']])->get()->toArray();
        if(!empty($method)){
            $course['method'] = $method;
        }
        //学习人数   基数+订单数
        $ordernum = Order::where(['class_id'=>$this->data['id'],'status'=>2,'oa_status'=>1])->count();
        $course['buy_num'] = $course['buy_num']+$ordernum;
        //讲师信息
        $teacher=[];
        $teacherlist = Couresteacher::where(['course_id'=>$this->data['id'],'is_del'=>0])->get()->toArray();
        if(!empty($teacherlist)){
            foreach ($teacherlist as $k=>$v){
                $oneteacher = Teacher::where(['id'=>$v['teacher_id'],'is_del'=>0])->first();
                array_push($teacher,$oneteacher);
            }
        }
        //是否购买
        $order = Order::where(['student_id'=>AdminLog::getAdminInfo()->admin_user->id,'class_id'=>$this->data['course_id'],'status'=>2])->count();
        $course['is_pay'] = $order > 0?1:0;
        //直播目录
//        if(in_array(['']))
    }
}


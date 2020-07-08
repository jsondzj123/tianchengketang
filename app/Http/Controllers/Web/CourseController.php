<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Coures;
use App\Models\Coureschapters;
use App\Models\Couresmaterial;
use App\Models\Couresmethod;
use App\Models\CouresSubject;
use App\Models\Couresteacher;
use App\Models\CourseLiveResource;
use App\Models\CourseRefResource;
use App\Models\CourseSchool;
use App\Models\CourseShiftNo;
use App\Models\LiveChild;
use App\Models\LiveClass;
use App\Models\LiveClassChildTeacher;
use App\Models\Order;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Video;
use App\Tools\MTCloud;
use Illuminate\Support\Facades\Redis;

class CourseController extends Controller {
    protected $school;
    protected $data;
    protected $userid;
    public function __construct(){
        $this->data = $_REQUEST;
        $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
        $this->userid = isset(AdminLog::getAdminInfo()->admin_user->id)?AdminLog::getAdminInfo()->admin_user->id:0;
    }
    /*
         * @param  学科列表
         * @param  author  苏振文
         * @param  ctime   2020/7/6 15:38
         * return  array
         */
    public function subjectList(){
        //存redis
        $key = 'Websubjectlist'.$this->school['id'];
        if(Redis::get($key)){
            return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>json_decode(Redis::get($key),true)]);
        }else{
            //自增学科
            $subject = CouresSubject::where(['school_id'=>$this->school['id'],'parent_id'=>0,'is_open'=>0,'is_del'=>0])->get()->toArray();
            if(!empty($subject)){
                foreach ($subject as $k=>&$v){
                    $subjects = CouresSubject::where(['parent_id'=>$v['id'],'is_open'=>0,'is_del'=>0])->get()->toArray();
                    if(!empty($subjects)){
                        $v['son'] = $subjects;
                    }
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
            Redis::set($key,json_encode($subject),300);
            return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>$subject]);
        }
    }
    /*
         * @param  课程列表
         * @param  author  苏振文
         * @param  ctime   2020/7/4 17:09
         * return  array
     */
    public function courseList(){
        $keys = json_encode($this->data).$this->school['id'];
        if(Redis::get($keys)){
            $data = json_decode(Redis::get($keys),true);
            return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>$data[0],'page'=>$data[1],'where'=>$data[2]]);
        }else {
            $school_id = $this->school['id'];
            //每页显示的条数
            $pagesize = (int)isset($this->data['pageSize']) && $this->data['pageSize'] > 0 ? $this->data['pageSize'] : 20;
            $page = isset($this->data['page']) && $this->data['page'] > 0 ? $this->data['page'] : 1;
            $offset = ($page - 1) * $pagesize;
            //学科大类小类条件
            $parent = [];
            if (!empty($this->data['parent'])) {
                $parent = json_decode($this->data['parent'], true);
            }
            //授课类型条件
            $methodwhere = '';
            if (!empty($this->data['method'])) {
                $methodwhere = $this->data['method'];
            }
            //总条数
            $count1 = Coures::where(['school_id' => $school_id, 'is_del' => 0])
                ->where(function ($query) use ($parent) {
                    if (!empty($parent[0]) && $parent[0] != '') {
                        $query->where('parent_id', $parent[0]);
                    }
                    if (!empty($parent[1]) && $parent[1] != '') {
                        $query->where('child_id', $parent[1]);
                    }
                })->count();
            $count2 = CourseSchool::leftJoin('ld_course', 'ld_course.id', '=', 'ld_course_school.course_id')
                ->where(['ld_course_school.to_school_id' => $school_id, 'ld_course_school.is_del' => 0])
                ->where(function ($query) use ($parent) {
                    if (!empty($parent[0]) && $parent[0] != '') {
                        $query->where('ld_courseparent_id', $parent[0]);
                    }
                    if (!empty($parent[1]) && $parent[1] != '') {
                        $query->where('ld_coursechild_id', $parent[1]);
                    }
                })->count();
            $count = $count1 + $count2;
            //自增课程
            $name = isset($this->data['name']) ? $this->data['name'] : '';
            $course = [];
            if ($count1 != 0) {
                $course = Coures::select('id', 'title', 'cover', 'sale_price', 'buy_num', 'nature', 'watch_num', 'create_at')
                    ->where(function ($query) use ($parent) {
                        if (!empty($parent[0]) && $parent[0] != '') {
                            $query->where('parent_id', $parent[0]);
                        }
                        if (!empty($parent[1]) && $parent[1] != '') {
                            $query->where('child_id', $parent[1]);
                        }
                    })
                    ->where(['school_id' => $school_id, 'is_del' => 0, 'status' => 1])
                    ->where('title', 'like', '%' . $name . '%')
                    ->get()->toArray();
                foreach ($course as $k => &$v) {
                    $method = Couresmethod::select('method_id')->where(['course_id' => $v['id'], 'is_del' => 0])->get()->toArray();
                    if (!empty($method)) {
                        foreach ($method as $key => &$val) {
                            if ($val['method_id'] == 1) {
                                $val['method_name'] = '直播';
                            }
                            if ($val['method_id'] == 2) {
                                $val['method_name'] = '录播';
                            }
                            if ($val['method_id'] == 3) {
                                $val['method_name'] = '其他';
                            }
                        }
                        $v['method'] = $method;
                    } else {
                        unset($course[$k]);
                    }
                }
            }
            $ref_course = [];
            //授权课程
            if ($count2 != 0) {
                $ref_course = CourseSchool::select('ld_course_school.course_id as id', 'ld_course_school.title', 'ld_course_school.cover', 'ld_course_school.sale_price', 'ld_course_school.buy_num', 'ld_course_school.watch_num', 'ld_course_school.create_at')
                    ->leftJoin('ld_course', 'ld_course.id', '=', 'ld_course_school.course_id')
                    ->where(function ($query) use ($parent) {
                        if (!empty($parent[0]) && $parent[0] != '') {
                            $query->where('ld_course.parent_id', $parent[0]);
                        }
                        if (!empty($parent[1]) && $parent[1] != '') {
                            $query->where('ld_course.child_id', $parent[1]);
                        }
                    })->where(['ld_course_school.to_school_id' => $school_id, 'ld_course_school.is_del' => 0, 'ld_course_school.status' => 1])
                    ->where('ld_course_school.title', 'like', '%' . $name . '%')
                    ->get()->toArray();
                foreach ($ref_course as $ks => &$vs) {
                    $method = Couresmethod::select('method_id')->where(['course_id' => $vs['course_id'], 'is_del' => 0])->where($methodwhere)->get()->toArray();
                    if (!empty($method)) {
                        foreach ($method as $key => &$val) {
                            if ($val['method_id'] == 1) {
                                $val['method_name'] = '直播';
                            }
                            if ($val['method_id'] == 2) {
                                $val['method_name'] = '录播';
                            }
                            if ($val['method_id'] == 3) {
                                $val['method_name'] = '其他';
                            }
                        }
                        $v['method'] = $method;
                        $v['nature'] = 1;
                    } else {
                        unset($ref_course[$ks]);
                    }
                }
            }
            //两数组合并 排序
            if (!empty($course) && !empty($ref_course)) {
                $all = array_merge($course, $ref_course);//合并两个二维数组
            } else {
                $all = !empty($course) ? $course : $ref_course;
            }
            //sort 1最新2最热  默认最新
            $sort = isset($this->data['sort']) ? $this->data['sort'] : 1;
            if ($sort == 1) {
                $date = array_column($all, 'create_at');
                array_multisort($date, SORT_DESC, $all);
            } else if ($sort == 2) {
                $date = array_column($all, 'watch_num');
                array_multisort($date, SORT_DESC, $all);
            }
            $res = array_slice($all, ($page - 1) * $pagesize, $pagesize);
            $page = [
                'pageSize' => $pagesize,
                'page' => $page,
                'total' => $count
            ];
            $datas = [
                0=>$res,
                1=>$page,
                2=>$this->data,
            ];
            Redis::set($keys,json_encode($datas),300);
            return response()->json(['code' => 200, 'msg' => '获取成功', 'data' => $res, 'page' => $page, 'where' => $this->data]);
        }
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
        //修改观看数
        Coures::where(['id'=>$this->data['id']])->update(['watch_num'=>$course['watch_num']+1]);
        $keys = $this->data.$this->userid;
        if(Redis::get($keys)){
            return response()->json(['code' => 200 , 'msg' => '查询成功','data'=>json_decode(Redis::get($keys),true)]);
        }else {
            //分类信息
            $parent = CouresSubject::select('id', 'subject_name')->where(['id' => $course['parent_id'], 'parent_id' => 0, 'is_del' => 0, 'is_open' => 0])->first();
            $child = CouresSubject::select('subject_name')->where(['id' => $course['child_id'], 'parent_id' => $parent['id'], 'is_del' => 0, 'is_open' => 0])->first();
            $course['parent_name'] = $parent['subject_name'];
            $course['child_name'] = $child['subject_name'];
            unset($course['parent_id']);
            unset($course['child_id']);
            //授课方式
            $method = Couresmethod::select('method_id')->where(['course_id' => $this->data['id']])->get()->toArray();
            if (!empty($method)) {
                $course['method'] = array_column($method, 'method_id');
            }
            //学习人数   基数+订单数
            $ordernum = Order::where(['class_id' => $this->data['id'], 'status' => 2, 'oa_status' => 1])->count();
            $course['buy_num'] = $course['buy_num'] + $ordernum;
            //讲师信息
            $teacher = [];
            $teacherlist = Couresteacher::where(['course_id' => $this->data['id'], 'is_del' => 0])->get()->toArray();
            if (!empty($teacherlist)) {
                foreach ($teacherlist as $k => $v) {
                    $oneteacher = Teacher::where(['id' => $v['teacher_id'], 'is_del' => 0])->first();
                    array_push($teacher, $oneteacher);
                }
            }
            //是否购买
            if ($course['sale_price'] > 0) {
                $order = Order::where(['student_id' => $this->userid, 'class_id' => $this->data['id'], 'status' => 2])->count();
                $course['is_pay'] = $order > 0 ? 1 : 0;
            } else {
                $course['is_pay'] = 1;
            }
            Redis::set($keys,json_encode($course),300);
            return response()->json(['code' => 200, 'msg' => '查询成功', 'data' => $course]);
        }
    }
    /*
         * @param  课程介绍
         * @param  author  苏振文
         * @param  ctime   2020/7/7 15:18
         * return  array
         */
    public function courseIntroduce(){
        $keys = $this->data;
        if(Redis::get($keys)){
            return response()->json(['code' => 201 , 'msg' => '查询成功','data'=>json_decode(Redis::get($keys),true)]);
        }else{
            //课程基本信息
            $course = Coures::select('introduce')->where(['id'=>$this->data['id'],'is_del'=>0])->first()->toArray();
            if(!$course){
                return response()->json(['code' => 201 , 'msg' => '无查看权限']);
            }
            Redis::set($keys,json_encode($course),300);
            return response()->json(['code' => 201 , 'msg' => '查询成功','data'=>$course]);
        }
    }

    /*
         * @param  课程直播列表
         * @param  author  苏振文
         * @param  ctime   2020/7/7 14:39
         * return  array
         */
    public function livearr(){
        //每页显示的条数
        $pagesize = (int)isset($this->data['pageSize']) && $this->data['pageSize'] > 0 ? $this->data['pageSize'] : 2;
        $page     = isset($this->data['page']) && $this->data['page'] > 0 ? $this->data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        //课程基本信息
        $course = Coures::where(['id'=>$this->data['id'],'is_del'=>0])->first()->toArray();
        if(!$course){
            return response()->json(['code' => 201 , 'msg' => '无查看权限']);
        }
        //用户是否购买，如果购买，显示全部   班号 课次
        $order = Order::where(['student_id'=>AdminLog::getAdminInfo()->admin_user->id,'class_id'=>$this->data['course_id'],'status'=>2])->count();
        $courseArr=[];
        if($order > 0){
            //获取所有的班号
            $courseArr = CourseLiveResource::select('shift_id')->where(['course_id'=>$this->data['id'],'is_del'=>0])->get()->toArray();
            if($courseArr != 0){
                foreach ($courseArr as $k=>&$v){
                    //获取班级信息
                    $class = LiveClass::where(['id'=>$v['shift_id'],'is_del'=>0])->first()->toArray();
                    $v['class_name'] = $class['name'];
                    //获取所有的课次
                    $classci = LiveChild::where(['shift_no_id'=>$v['shift_id'],'is_del'=>0,'status'=>1])->get()->toArray();
                    //课次关联讲师
                    if(!empty($classci)){
                        foreach ($classci as $ks=>&$vs){
                            //开课时间戳 start_at 结束时间戳转化 end_at
                            $ymd = date('Y-m-s',$vs['start_at']);//年月日
                            $start = date('H:i',$vs['start_at']);//开始时分
                            $end = date('H:i',$vs['end_at']);//结束时分
                            $weekarray = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];
                            $xingqi = date("w", 1593414566);
                            $week = $weekarray[$xingqi];
                            $vs['times'] = $ymd.'&nbsp&nbsp'.$week.'&nbsp&nbsp'.$start.'-'.$end;
                            $teacher = LiveClassChildTeacher::leftJoin('ld_lecturer_educationa','ld_lecturer_educationa.id','=','ld_course_class_teacher.teacher_id')
                                ->where(['ld_course_class_teacher.is_del'=>0,'ld_lecturer_educationa.is_del'=>0,'ld_lecturer_educationa.type'=>2,'ld_lecturer_educationa.is_forbid'=>0])
                                ->first()->toArray();
                            if(!empty($teacher)){
                                $vs['teacher_name'] = $teacher['real_name'];
                            }
                        }
                    }
                }
            }
        }
        return response()->json(['code' => 200 , 'msg' => '查询成功','data'=>$courseArr]);
    }
    /*
         * @param  课程录播列表
         * @param  author  苏振文
         * @param  ctime   2020/7/7 14:39
         * return  array
         */
    public function recordedarr(){
        //每页显示的条数
        $pagesize = (int)isset($this->data['pageSize']) && $this->data['pageSize'] > 0 ? $this->data['pageSize'] : 3;
        $page     = isset($this->data['page']) && $this->data['page'] > 0 ? $this->data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        //课程基本信息
        if(!isset($this->data['id'])||empty($this->data['id'])){
            return response()->json(['code' => 201 , 'msg' => '课程id为空']);
        }
        $course = Coures::where(['id'=>$this->data['id'],'is_del'=>0])->first()->toArray();
        if(!$course){
            return response()->json(['code' => 201 , 'msg' => '无查看权限']);
        }
        //判断此课程是否免费
        //免费课程  将此课程的所有录播内容查询出来
        //用户是否购买，如果购买，显示全部
        //是否购买
        if($course['sale_price'] > 0){
            $order = Order::where(['student_id'=>$this->userid,'class_id'=>$this->data['id'],'status'=>2])->count();
            $is_pay = $order > 0?1:0;
        }else{
            $is_pay = 1;
        }
        //免费或者已经购买，展示全部
        if($course['sale_price'] == 0 || $is_pay == 0){
            //章总数
            $count = Coureschapters::where(['course_id'=>$this->data['id'],'is_del'=>0,'parent_id'=>0])->count();
            $recorde =[];
            if($count > 0){
                $recorde = Coureschapters::where(['course_id'=>$this->data['id'],'is_del'=>0,'parent_id'=>0])->offset($offset)->limit($pagesize)->get();
                if(!empty($recorde)){
                    foreach ($recorde as $ks=>&$vs){
                        $recorde = Coureschapters::where(['course_id'=>$this->data['id'],'is_del'=>0,'parent_id'=>$vs['id']])->get()->toArray();
                        foreach ($recorde as $key=>&$val){
                            //查询小节绑定的录播资源
                            $ziyuan = Video::where(['id'=>$val['resource_id'],'is_del'=>0,'status'=>0])->first()->toArray();
                            $val['ziyuan'] = $ziyuan;
                            //获取 学习时长
                            $MTCloud = new MTCloud();
                            $use_duration  =  $MTCloud->coursePlaybackVisitorList($this->data['id'],1,50);
                            print_r($use_duration);
                            if(!empty($use_duration)){
                                foreach ($use_duration['data'] as $kk=>$vv){
                                    if($vv['uid'] == $this->userid){
                                        if($vv['use_duration'] == 0){
                                            $val['study'] = 0;
                                        }else{
                                            $val['study'] =  sprintf("%01.2f", $vv['use_duration']/$vv['mt_duration']*100).'%';
                                        }
                                    }
                                }
                            }
                        }
                        $vs['chapters'] = $recorde;
                    }
                }
            }
            $page=[
                'pageSize'=>$pagesize,
                'page' =>$page,
                'total'=>$count
            ];
            return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>$recorde,'page'=>$page]);
        }else{
            //只展示试听章节
            //章总数
            $count = Coureschapters::where(['course_id'=>$this->data['id'],'is_del'=>0,'parent_id'=>'> 0'])->count();
            $recorde =[];
            if($count > 0){
                $recorde = Coureschapters::where(['course_id'=>$this->data['id'],'is_del'=>0,'parent_id'=>0])->get()->toArray();
                if(!empty($recorde)){
                    foreach ($recorde as $kss=>&$vss){
                        $recorde = Coureschapters::where(['course_id'=>$this->data['id'],'is_del'=>0,'parent_id'=>$vss['id'],'is_free'=>2])->get()->toArray();
                        if(!empty($recorde)){
                            foreach ($recorde as $key=>$val){
                                //查询小节绑定的录播资源
                                $ziyuan = Video::where(['id'=>$val['resource_id'],'is_del'=>0,'status'=>0])->first()->toArray();
                                $val['ziyuan'] = $ziyuan;
                            }
                            $vs['chapters'] = $recorde;
                        }else{
                            unset($recorde[$kss]);
                        }
                    }
                }
            }
            $page=[
                'pageSize'=>$pagesize,
                'page' =>$page,
                'total'=>$count
            ];
            return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>$recorde,'page'=>$page]);
        }
    }
    /*
         * @param  课程资料表
         * @param  author  苏振文
         * @param  ctime   2020/7/7 14:40
         * return  array
         */
    public function material(){
        //每页显示的条数
        $pagesize = (int)isset($this->data['pageSize']) && $this->data['pageSize'] > 0 ? $this->data['pageSize'] : 10;
        $page     = isset($this->data['page']) && $this->data['page'] > 0 ? $this->data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        //查询所有班号  根据班号，查询条件将资料查询出来
        $classlist = CourseLiveResource::where(['is_del'=>0,'course_id'=>$this->data['id']])->get()->toArray();
        $shift = array_column($classlist, 'shift_id');
        $where['is_del'] = 0;
        $where['mold'] = 1;
        if(isset($this->data['type']) && !empty($this->data['type'])){
            $where['type'] = $this->data['type'];
        }
        $count = Couresmaterial::whereIn('parent_id', $shift)->where($where)->count();
        $ziyuan = Couresmaterial::whereIn('parent_id', $shift)->where($where)->orderByDesc('id')->offset($offset)->limit($pagesize)->get()->toArray();
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$ziyuan,'where'=>$where,'page'=>$page];
    }
}


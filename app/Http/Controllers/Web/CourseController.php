<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Collection;
use App\Models\Coures;
use App\Models\Coureschapters;
use App\Models\Couresmaterial;
use App\Models\Couresmethod;
use App\Models\CouresSubject;
use App\Models\Couresteacher;
use App\Models\CourseLiveResource;
use App\Models\CourseSchool;
use App\Models\CourseStocks;
use App\Models\Lecturer;
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
        $this->userid = isset($this->data['user_info']['user_id'])?$this->data['user_info']['user_id']:0;
    }
    /*
         * @param  学科列表
         * @param  author  苏振文
         * @param  ctime   2020/7/6 15:38
         * return  array
         */
    public function subjectList(){
        //存redis
//        $key = 'Websubjectlist'.$this->school['id'];
//        if(Redis::get($key)){
//            return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>json_decode(Redis::get($key),true)]);
//        }else{
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
                    $ones = CouresSubject::where(['id'=>$vs['parent_id'],'parent_id'=>0,'is_open'=>0,'is_del'=>0])->first()->toArray();
                    if(!empty($ones)){
                        $ones['son'] = CouresSubject::where(['parent_id'=>$vs['parent_id'],'is_open'=>0,'is_del'=>0])->get()->toArray();
                    }
                    array_push($subject,$ones);
                }
            }
//            Redis::set($key,json_encode($subject),300);
            return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>$subject]);
//        }
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
            $page = isset($this->data['page']) && $this->data['page'] > 0 ? $this->data['page'] : 1;
//            if(isset($this->data['name']) && !empty($this->data['name'])){
//                $page = 1;
//            }
           $offset = ($page - 1) * $pagesize;
            //学科大类小类条件
            $parent = [];
            if (!empty($this->data['parent'])) {
                $parent = json_decode($this->data['parent'], true);
            }
            //授课类型条件
            $methodwhere = isset($this->data['method'])?$this->data['method']:'';
            $name = isset($this->data['name']) ? $this->data['name'] : '';
            //总条数
            $count1 = Coures::where(['school_id' => $school_id, 'is_del' => 0,'status'=>1])
                ->where('title', 'like', '%' . $name . '%')
                ->where(function ($query) use ($parent) {
                    if (!empty($parent[0]) && $parent[0] != ''&& $parent[0] != 0) {
                        $query->where('parent_id', $parent[0]);
                    }
                    if (!empty($parent[1]) && $parent[1] != ''&& $parent[1] != 0) {
                        $query->where('child_id', $parent[1]);
                    }
                })->count();

            $count2 = CourseSchool::where(['to_school_id' => $school_id, 'is_del' => 0,'status'=>1])
                ->where('title', 'like', '%' . $name . '%')
                ->where(function ($query) use ($parent) {
                    if (!empty($parent[0]) && $parent[0] != ''&& $parent[0] != 0) {
                        $query->where('parent_id', $parent[0]);
                    }
                    if (!empty($parent[1]) && $parent[1] != ''&& $parent[1] != 0) {
                        $query->where('child_id', $parent[1]);
                    }
                })->count();
            $count = 0;
            //自增课程
            $course = [];
            if ($count1 != 0) {
                $course = Coures::select('id', 'title', 'cover', 'sale_price', 'buy_num', 'nature', 'watch_num', 'create_at')
                    ->where(function ($query) use ($parent) {
                        if (!empty($parent[0]) && $parent[0] != ''&& $parent[0] != 0) {
                            $query->where('parent_id', $parent[0]);
                        }
                        if (!empty($parent[1]) && $parent[1] != ''&& $parent[1] != 0) {
                            $query->where('child_id', $parent[1]);
                        }
                    })
                    ->where(['school_id' => $school_id, 'is_del' => 0, 'status' => 1])
                    ->where('title', 'like', '%' . $name . '%')
                    ->get()->toArray();
                foreach ($course as $k => &$v) {
                    $method = Couresmethod::select('method_id')->where(['course_id' => $v['id'], 'is_del' => 0])
                        ->where(function ($query) use ($methodwhere) {
                            if($methodwhere != ''){
                                $query->where('method_id', $methodwhere);
                            }
                        })->get()->toArray();
                    if (!empty($method)) {
                        $count = $count +1;
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
                $ref_course = CourseSchool::select('id', 'title', 'cover', 'sale_price', 'buy_num', 'watch_num', 'create_at', 'course_id')
                    ->where(function ($query) use ($parent) {
                        if (!empty($parent[0]) && $parent[0] != ''&& $parent[0] != 0) {
                            $query->where('parent_id', $parent[0]);
                        }
                        if (!empty($parent[1]) && $parent[1] != ''&& $parent[1] != 0) {
                            $query->where('child_id', $parent[1]);
                        }
                    })
                    ->where(['to_school_id' => $school_id, 'is_del' => 0, 'status' => 1])
                    ->where('title', 'like', '%' . $name . '%')
                    ->get()->toArray();
                foreach ($ref_course as $ks => &$vs) {
                    //获取库存计算总数  订单总数   判断 相等或大于就删除，否则展示
                    $add_number = CourseStocks::where(['course_id' => $vs['course_id'], 'school_id' => $school_id, 'is_del' => 0])->get()->toArray();
                    if (!empty($add_number)) {
                        //库存总数
                        $stocknum = 0;
                        foreach ($add_number as $kstock => $vstock) {
                            $stocknum = $stocknum + $vstock['add_number'];
                        }
                        if ($stocknum != 0) {
                            //查订单表
                            $ordercount = Order::where(['status' => 2, 'oa_status' => 1, 'student_id' => $school_id, 'class_id' => $vs['id'], 'nature' => 1])->count();
                            if ($ordercount < $stocknum) {
                                $method = Couresmethod::select('method_id')->where(['course_id' => $vs['course_id'], 'is_del' => 0])
                                    ->where(function ($query) use ($methodwhere) {
                                        if ($methodwhere != '') {
                                            $query->where('method_id', $methodwhere);
                                        }
                                    })->get()->toArray();
                                if (!empty($method)) {
                                    $count = $count +1;
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
                                    $vs['method'] = $method;
                                    $vs['nature'] = 1;
                                } else {
                                    unset($ref_course[$ks]);
                                }
                            } else {
                                unset($ref_course[$ks]);
                            }
                        } else {
                            unset($ref_course[$ks]);
                        }
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
            $res = array_slice($all, $offset, $pagesize);
            if(empty($res)){
                $res = array_slice($all, 1, $pagesize);
            }
            $page = [
                'pageSize' => $pagesize,
                'page' => $page,
                'total' => $count
            ];
            return response()->json(['code' => 200, 'msg' => '获取成功', 'data' => $res, 'page' => $page, 'where' => $this->data]);
    }
    /*
         * @param  课程详情
         * @param  author  苏振文
         * @param  ctime   2020/7/6 17:50
         * return  array
         */
    public function courseDetail(){
        if(!isset($this->data['id']) || empty($this->data['id'])){
            return response()->json(['code' => 201 , 'msg' => '课程id不能为空']);
        }
        $this->data['nature'] = isset($this->data['nature'])?$this->data['nature']:0;
        //课程基本信息
        //授权
        if($this->data['nature'] == 1){
            $course = CourseSchool::where(['id'=>$this->data['id'],'is_del'=>0])->first()->toArray();
            if(!$course){
                return response()->json(['code' => 201 , 'msg' => '无此课程']);
            }
            //修改观看数
            CourseSchool::where(['id'=>$this->data['id']])->update(['watch_num'=>$course['watch_num']+1]);
            //授课方式
            $method = Couresmethod::select('method_id')->where(['course_id' => $course['course_id']])->get()->toArray();
            if (!empty($method)) {
                $course['method'] = array_column($method, 'method_id');
            }
            //学习人数   基数+订单数
            $ordernum = Order::where(['class_id' => $course['course_id'], 'status' => 2, 'oa_status' => 1,'nature'=>1])->count();
            $course['buy_num'] = $course['buy_num'] + $ordernum;
            //讲师信息
            $teacher = [];
            $teacherlist = Couresteacher::where(['course_id' => $course['course_id'], 'is_del' => 0])->get()->toArray();
            if (!empty($teacherlist)) {
                foreach ($teacherlist as $k => $v) {
                    $oneteacher = Teacher::where(['id' => $v['teacher_id'], 'is_del' => 0])->first();
                    array_push($teacher, $oneteacher);
                }
            }
            //是否购买
            if($this->userid != 0){
                if ($course['sale_price'] > 0) {
                    $order = Order::where(['student_id' => $this->userid, 'class_id' =>$course['course_id'], 'status' => 2,'nature'=>1])->count();
                    $course['is_pay'] = $order > 0 ? 1 : 0;
                } else {
                    $course['is_pay'] = 1;
                }
            }else{
                $course['is_pay'] = 0;
            }
            //收藏数量
            $collect = Collection::where(['lesson_id'=>$course['course_id'],'is_del'=>0,'nature'=>1])->count();
            $course['collect'] = $collect;
            //判断用户是否收藏
            if($this->userid != 0){
                $collects = Collection::where(['lesson_id'=>$course['course_id'],'student_id'=>$this->userid,'is_del'=>0,'nature'=>1])->count();
                if($collects != 0){
                    $course['is_collect'] = 1;
                }else{
                    $course['is_collect'] = 0;
                }
            }else{
                $course['is_collect'] = 0;
            }
        }else{
            $course = Coures::where(['id'=>$this->data['id'],'is_del'=>0])->first()->toArray();
            if(!$course){
                return response()->json(['code' => 201 , 'msg' => '无此课程']);
            }
            //修改观看数
            Coures::where(['id'=>$this->data['id']])->update(['watch_num'=>$course['watch_num']+1]);
            //授课方式
            $method = Couresmethod::select('method_id')->where(['course_id' =>$this->data['id']])->get()->toArray();
            if (!empty($method)) {
                $course['method'] = array_column($method, 'method_id');
            }
            //学习人数   基数+订单数
            $ordernum = Order::where(['class_id' => $this->data['id'], 'status' => 2, 'oa_status' => 1,'nature'=>0])->count();
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
            if($this->userid != 0){
                if ($course['sale_price'] > 0) {
                    $order = Order::where(['student_id' => $this->userid, 'class_id' =>$this->data['id'], 'status' => 2,'nature'=>0])->count();
                    echo $order;
                    $course['is_pay'] = $order > 0 ? 1 : 0;
                } else {
                    $course['is_pay'] = 1;
                }
            }else{
                $course['is_pay'] = 0;
            }
            //收藏数量
            $collect = Collection::where(['lesson_id'=>$this->data['id'],'is_del'=>0,'nature'=>0])->count();
            $course['collect'] = $collect;
            //判断用户是否收藏
            if($this->userid != 0){
                $collects = Collection::where(['lesson_id'=>$this->data['id'],'student_id'=>$this->userid,'is_del'=>0,'nature'=>0])->count();
                if($collects != 0){
                    $course['is_collect'] = 1;
                }else{
                    $course['is_collect'] = 0;
                }
            }else{
                $course['is_collect'] = 0;
            }
        }
        //分类信息
        $parent = CouresSubject::select('id', 'subject_name')->where(['id' => $course['parent_id'], 'parent_id' => 0, 'is_del' => 0, 'is_open' => 0])->first();
        $child = CouresSubject::select('subject_name')->where(['id' => $course['child_id'], 'parent_id' => $parent['id'], 'is_del' => 0, 'is_open' => 0])->first();
        $course['parent_name'] = $parent['subject_name'];
        $course['child_name'] = $child['subject_name'];
        unset($course['parent_id']);
        unset($course['child_id']);
        return response()->json(['code' => 200, 'msg' => '查询成功', 'data' => $course]);
    }
    //课程收藏
    public function collect(){
        if(!isset($this->data['id'])||empty($this->data['id'])){
            return response()->json(['code' => 201, 'msg' => '课程id为空']);
        }
        $list = Collection::where(['lesson_id'=>$this->data['id'],'student_id'=>$this->userid,'nature'=>$this->data['nature']])->first();
        if($list){
            $status = $list['is_del'] == 1?0:1;
            $add = Collection::where(['lesson_id'=>$this->data['id'],'student_id'=>$this->userid])->update(['is_del'=>$status]);
        }else{
            $add = Collection::insert([
                'lesson_id' => $this->data['id'],
                'student_id' => $this->userid,
                'created_at' => date('Y-m-d H:i:s'),
                'nature' => $this->data['nature']
            ]);
        }
        if($add){
            $count = Collection::where(['lesson_id'=>$this->data['id'],'nature'=>$this->data['nature'],'is_del'=>0])->count();
            return response()->json(['code' => 200, 'msg' => '操作成功','data'=>$count]);
        }else{
            return response()->json(['code' => 203, 'msg' => '操作失败']);
        }
    }
    /*
         * @param  课程讲师
         * @param  author  苏振文
         * @param  ctime   2020/7/13 15:29
         * return  array
         */
    public function courseTeacher(){
        if(!isset($this->data['id'])||empty($this->data['id'])){
            return response()->json(['code' => 201, 'msg' => '课程id为空']);
        }
        $nature = isset($this->data['nature'])?$this->data['nature']:0;
        if($nature == 1){
            $course = CourseSchool::where(['id'=>$this->data['id'],'is_del'=>0,'status'=>1])->first();
            $this->data['id'] =  $course['course_id'];
        }
        $teacher = Couresteacher::where(['course_id'=>$this->data['id'],'is_del'=>0])->get();
        $teacherlist=[];
        if(!empty($teacher)){
            foreach ($teacher as $k=>$v){
                $teacherlist[] = Lecturer::where(['id'=>$v['teacher_id'],'is_del'=>0,'is_forbid'=>0,'type'=>2])->first();
            }
        }
        return response()->json(['code' => 200, 'msg' => '获取成功','data'=>$teacherlist]);
    }
    /*
         * @param  课程介绍
         * @param  author  苏振文
         * @param  ctime   2020/7/7 15:18
         * return  array
         */

    public function courseIntroduce(){
        $nature = $this->data['nature'];
        if($nature == 1){
            //课程基本信息
            $course = CourseSchool::select('introduce')->where(['id'=>$this->data['id'],'is_del'=>0])->first();
            if(!$course){
                return response()->json(['code' => 201 , 'msg' => '无查看权限']);
            }
        }else{
            //课程基本信息
            $course = Coures::select('introduce')->where(['id'=>$this->data['id'],'is_del'=>0])->first();
            if(!$course){
                return response()->json(['code' => 201 , 'msg' => '无查看权限']);
            }
        }
        return response()->json(['code' => 200 , 'msg' => '查询成功','data'=>$course]);
    }
    /*
         * @param  课程录播列表
         * @param  author  苏振文
         * @param  ctime   2020/7/7 14:39
         * return  array
         */
    public function recordedarr(){
        //课程基本信息
        if(!isset($this->data['id'])||empty($this->data['id'])){
            return response()->json(['code' => 201 , 'msg' => '课程id为空']);
        }
        $nature = isset($this->data['nature'])?$this->data['nature']:0;
        if($nature == 1){
            $course = CourseSchool::where(['to_school_id'=>$this->school['id'],'id'=>$this->data['id'],'is_del'=>0])->first();
            if(!$course){
                return response()->json(['code' => 201 , 'msg' => '无查看权限']);
            }
            $orderwhere=[
                'student_id'=>$this->userid,
                'class_id' => $course['id'],
                'status' => 2,
                'nature' =>1
            ];
            $this->data['id'] = $course['course_id'];
        }else{
            $course = Coures::where(['school_id'=>$this->school['id'],'id'=>$this->data['id'],'is_del'=>0])->first();
            if(!$course){
                return response()->json(['code' => 201 , 'msg' => '无查看权限']);
            }
            $orderwhere=[
                'student_id'=>$this->userid,
                'class_id' => $this->data['id'],
                'status' => 2,
                'nature' =>0
            ];
        }
        //判断用户与课程的关系
        //判断课程是否免费
            if ($course['sale_price'] > 0) {
                $order = Order::where($orderwhere)->first();
                //判断是否购买
                if (!empty($order)) {
                    //判断是否到期 0是无期限
                    if ($course['expiry'] != 0) {
                        //看订单里面的到期时间 进行判断
                        if (date('Y-m-d H:i:s') >= $order['validity_time']) {
                            //课程到期  只能观看
                            $is_show = 0;
                        } else {
                            $is_show = 1;
                        }
                    } else {
                        $is_show = 1;
                    }
                } else {
                    //未购买
                    $is_show = 0;
                }
            } else {
                //免费
                $is_show = 1;
            }
        //章总数
        $count = Coureschapters::where(['course_id'=>$this->data['id'],'is_del'=>0,'parent_id'=>0])->count();
        $recorde =[];
        if($count > 0) {
            //如果is_show是1  查询所有的课程   0查询能免费看的，试听的课程
            if($is_show == 1){
                $chapterswhere = [
                    'is_del' => 0,
                ];
            }else {
                //查询免费课程
                $chapterswhere = [
                    'is_del' => 0,
                    'is_free' => 2
                ];
            }
            //获取章
            $recorde = Coureschapters::where(['course_id' => $this->data['id'], 'is_del' => 0, 'parent_id' => 0])->get()->toArray();
            if (!empty($recorde)) {
                //循环章  查询每个章下的节
                foreach ($recorde as $k => &$v) {
                    $recordes = Coureschapters::where(['course_id' => $this->data['id'], 'parent_id' => $v['id']])->where($chapterswhere)->get()->toArray();
                    if (!empty($recordes)) {
                        $MTCloud = new MTCloud();
                        //循环每个小节 查询小节的进度
                        foreach ($recordes as $key => &$val) {
                            //查询小节绑定的录播资源
                            $ziyuan = Video::where(['id' => $val['resource_id'], 'is_del' => 0, 'status' => 0])->first();
                            $video_url = $MTCloud->videoGet($ziyuan['mt_video_id'],'720d');
                            if($video_url['code'] ==  0){
                                $val['video_url'] = $video_url['data']['videoUrl'];
                            }else{
                                $val['video_url'] = '';
                            }
//                            if (empty($ziyuan)) {
//                                $val['study'] = 0;
//                            } else {
//                                $use_duration = $MTCloud->coursePlaybackVisitorList($ziyuan['course_id'], 1, 50);
//                                if (isset($use_duration['data']) || !empty($use_duration['data'])) {
//                                    foreach ($use_duration['data'] as $kk => $vv) {
//                                        if ($vv['uid'] == $this->userid) {
//                                            if ($vv['use_duration'] == 0) {
//                                                $val['study'] = 0;
//                                            } else {
//                                                $val['study'] = sprintf("%01.2f", $vv['use_duration'] / $vv['mt_duration'] * 100) . '%';
//                                            }
//                                        } else {
//                                            $val['study'] = 0;
//                                        }
//                                    }
//                                }
//                            }
                        }
                        $v['chapters'] = $recordes;
                    }
                }
            }
        }
        return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>$recorde]);
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
        if(!isset($this->data['id'])||empty($this->data['id'])){
            return response()->json(['code' => 201 , 'msg' => '课程id为空']);
        }
        $nature = isset($this->data['nature'])?$this->data['nature']:0;
        if($nature == 1){
            $course = CourseSchool ::where(['to_school_id'=>$this->school['id'],'id'=>$this->data['id'],'is_del'=>0])->first();
            //课程是否免费或者用户是否购买，如果购买，显示全部班号课次
            $order = Order::where(['student_id'=>$this->userid,'class_id'=>$this->data['id'],'status'=>2])->count();
            $this->data['id'] = $course['course_id'];
        }else{
            $course = Coures::where(['school_id'=>$this->school['id'],'id'=>$this->data['id'],'is_del'=>0])->first();
            //课程是否免费或者用户是否购买，如果购买，显示全部班号课次
            $order = Order::where(['student_id'=>$this->userid,'class_id'=>$this->data['id'],'status'=>2])->count();
        }
        if(!$course){
            return response()->json(['code' => 201 , 'msg' => '无查看权限']);
        }
        $courseArr=[];
        if($order == 0 || $course['sale_price'] == 0){
            $courseArr['is_pay'] = 1;
            //获取所有的班号
            $courseArr = CourseLiveResource::select('shift_id')->where(['course_id'=>$this->data['id'],'is_del'=>0])->get()->toArray();
            if($courseArr != 0){
                foreach ($courseArr as $k=>&$v){
                    //获取班级信息
                    $class = LiveClass::where(['id'=>$v['shift_id'],'is_del'=>0])->first();
                    $v['class_name'] = $class['name'];
                    //获取所有的课次
                    $classci = LiveChild::where(['shift_no_id'=>$v['shift_id'],'is_del'=>0,'status'=>1])->get();
                    if(!empty($classci)){
                        //课次关联讲师  时间戳转换   查询所有资料
                        foreach ($classci as $ks=>&$vs){
                            //开课时间戳 start_at 结束时间戳转化 end_at
                            $ymd = date('Y-m-s',$vs['start_at']);//年月日
                            $start = date('H:i',$vs['start_at']);//开始时分
                            $end = date('H:i',$vs['end_at']);//结束时分
                            $weekarray = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];
                            $xingqi = date("w", 1593414566);
                            $week = $weekarray[$xingqi];
                            $vs['times'] = $ymd.'&nbsp&nbsp'.$week.'&nbsp&nbsp'.$start.'-'.$end;
                            //查询讲师
                            $teacher = LiveClassChildTeacher::leftJoin('ld_lecturer_educationa','ld_lecturer_educationa.id','=','ld_course_class_teacher.teacher_id')
                                ->where(['ld_course_class_teacher.is_del'=>0,'ld_lecturer_educationa.is_del'=>0,'ld_lecturer_educationa.type'=>2,'ld_lecturer_educationa.is_forbid'=>0])
                                ->first()->toArray();
                            if(!empty($teacher)){
                                $vs['teacher_name'] = $teacher['real_name'];
                            }
                            //查询资料
                            $material = Couresmaterial::where(['mold'=>3,'is_del'=>0,'course_id'=>$this->data['id'],'parent_id'=>$vs['id']])->get()->toArray();
                            if(!empty($material)){
                                $vs['material'] = $material;
                            }
                        }
                        $v['keci'] = $classci;
                    }
                }
            }
        }else{
            $courseArr['is_pay'] = 0;
        }
        return response()->json(['code' => 200 , 'msg' => '查询成功','data'=>$courseArr]);
    }
    /*
         * @param  课程资料表   录播  直播班号 课程小节
         * @param  author  苏振文
         * @param  ctime   2020/7/7 14:40
         * return  array
         */
    public function material(){
        //每页显示的条数
        $pagesize = (int)isset($this->data['pageSize']) && $this->data['pageSize'] > 0 ? $this->data['pageSize'] : 10;
        $page     = isset($this->data['page']) && $this->data['page'] > 0 ? $this->data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        $nature = isset($this->data['nature'])?$this->data['nature']:0;
        //订单判断是否购买
        if($nature == 1){
            $course = CourseSchool ::where(['id'=>$this->data['id'],'is_del'=>0])->first();
            //课程是否免费或者用户是否购买
            $order = Order::where(['student_id'=>$this->userid,'class_id'=>$this->data['id'],'status'=>2])->count();
            if($order > 0){
                $is_pay = 1;
            }else{
                $is_pay = 0;
            }
            $this->data['id'] = $course['course_id'];
        }else{
            //课程是否免费或者用户是否购买
            $order = Order::where(['student_id'=>$this->userid,'class_id'=>$this->data['id'],'status'=>2])->count();
            if($order > 0){
                $is_pay = 1;
            }else{
                $is_pay = 0;
            }
        }
        $type = isset($this->data['type'])?$this->data['type']:'';
        $ziyuan=[];
//        if($is_pay > 0){
            //录播资料
            $jie = Coureschapters::where(['course_id'=>$this->data['id'],'is_del'=>0])->where('parent_id','>',0)->get();
            if(!empty($jie)){
                foreach ($jie as $k=>$v){
                    $ziliao = Couresmaterial::where(['parent_id'=>$v['id'],'is_del'=>0,'mold'=>1])
                        ->where(function ($query) use ($type) {
                            if (!empty($type) && $type != '' && $type != 0) {
                                $query->where('type', $type);
                            }
                        })->get();
                    if(!empty($ziliao)){
                        foreach ($ziliao as $kss=>$vss){
                            $ziyuan[] = $vss;
                        }
                    }
                }
            }
            //直播资料
            $ban = CourseLiveResource::where(['course_id'=>$this->data['id'],'is_del'=>0])->get();
            if(!empty($ban)){
                foreach ($ban as $ks=>$vs){
                    $ziliaos = Couresmaterial::where(['parent_id'=>$vs['id'],'is_del'=>0,'mold'=>2])
                        ->where(function ($query) use ($type) {
                            if (!empty($type) && $type != '' && $type != 0) {
                                $query->where('type', $type);
                            }
                        })->first();
                    $ziyuan[] = $ziliaos;
                }
            }
//        }
        $ziyuan['is_pay'] = $is_pay;
        $res = array_slice($ziyuan, $offset, $pagesize);
        return ['code' => 200 , 'msg' => '查询成功','data'=>$res,'page'=>$page];
    }
}


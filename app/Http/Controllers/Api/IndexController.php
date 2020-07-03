<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\OpenCourse;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Order;
use App\Models\Subject;
use App\Models\LessonTeacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


class IndexController extends Controller {
    /*
     * @param  description   首页轮播图接口
     * @param author    dzj
     * @param ctime     2020-05-25
     * return string
     */
    public function getChartList() {
        //获取提交的参数
        try{
            $rotation_chart_list = [
                [
                    'chart_id'     =>   1 ,
                    'title'        =>   '轮播图1' ,
                    'jump_url'     =>   '' ,
                    'pic_image'    =>   "https://longdeapi.oss-cn-beijing.aliyuncs.com/upload/2020-06-17/159238101090725ee9ce52b4dbc.jpg" ,
                    'type'         =>   1 ,
                    'lession_info' => [
                        'lession_id'  => 1 ,
                        'lession_name'=> '课程名称1'
                    ]
                ] ,
                [
                    'chart_id'     =>   2 ,
                    'title'        =>   '轮播图2' ,
                    'jump_url'     =>   '' ,
                    'pic_image'    =>   "https://longdeapi.oss-cn-beijing.aliyuncs.com/upload/2020-06-17/159238104323565ee9ce73db673.jpg" ,
                    'type'         =>   1 ,
                    'lession_info' =>   [
                        'lession_id'  => 0 ,
                        'lession_name'=> ''
                    ]
                ] ,
                [
                    'chart_id'     =>   3 ,
                    'title'        =>   '轮播图3' ,
                    'jump_url'     =>   '' ,
                    'pic_image'    =>   "https://longdeapi.oss-cn-beijing.aliyuncs.com/upload/2020-06-17/159238106166285ee9ce85ea7e0.jpg" ,
                    'type'         =>   1 ,
                    'lession_info' => [
                        'lession_id'  => 2 ,
                        'lession_name'=> '课程名称2'
                    ]
                ]
            ];
            return response()->json(['code' => 200 , 'msg' => '获取轮播图列表成功' , 'data' => $rotation_chart_list]);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description   首页公开课接口
     * @param author    dzj
     * @param ctime     2020-05-25
     * return string
     */
    public function getOpenClassList() {
        //获取提交的参数
        try{
            //判断公开课列表是否为空
            $open_class_count = OpenCourse::where('status' , 1)->where('is_del' , 0)->where('is_recommend', 1)->count();
            if($open_class_count && $open_class_count > 0){
                //获取公开课列表
                $open_class_list = OpenCourse::select('id' , 'cover' , 'start_at' , 'end_at')
                        ->where('status' , 1)->where('is_del' , 0)->where('is_recommend', 1)
                        ->orderBy('start_at' , 'ASC')->offset(0)->limit(3)->get()->toArray();

                //新数组赋值
                $lession_array = [];
                //循环公开课列表
                foreach($open_class_list as $k=>$v){
                    //根据课程id获取讲师姓名
                    $info = DB::table('ld_course_open')->select("ld_lecturer_educationa.real_name")->where("ld_course_open.id" , $v['id'])->leftJoin('ld_course_open_teacher' , function($join){
                        $join->on('ld_course_open.id', '=', 'ld_course_open_teacher.course_id');
                    })->leftJoin("ld_lecturer_educationa" , function($join){
                        $join->on('ld_course_open_teacher.teacher_id', '=', 'ld_lecturer_educationa.id')->where("ld_lecturer_educationa.type" , 2);
                    })->first();

                    //判断课程状态
                    if($v['end_at'] < time()){
                        $status = 3;
                    } elseif($v['start_at'] > time()){
                        $status = 2;
                    } else {
                        $status = 1;
                    }

                    //新数组赋值
                    $lession_array[] = [
                        'open_class_id'  =>  $v['id'] ,
                        'cover'          =>  $v['cover'] && !empty($v['cover']) ? $v['cover'] : '' ,
                        'teacher_name'   =>  $info && !empty($info) ? $info->real_name : '' ,
                        'start_date'     =>  date('Y-m-d' , $v['start_at']) ,
                        'start_time'     =>  date('H:i' , $v['start_at']) ,
                        'end_time'       =>  date('H:i' , $v['end_at']) ,
                        'status'         =>  $status
                    ];
                }
                return response()->json(['code' => 200 , 'msg' => '获取公开课列表成功' , 'data' => $lession_array]);
            } else {
                return response()->json(['code' => 200 , 'msg' => '获取公开课列表成功' , 'data' => []]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description   首页讲师接口
     * @param author    dzj
     * @param ctime     2020-05-25
     * return string
     */
    public function getTeacherList() {
        //获取提交的参数
        try{
            //判断讲师列表是否为空
            $teacher_count = Teacher::where("is_del" , 0)->where("is_forbid" , 0)->where("is_recommend" , 1)->where("type" , 2)->count();
            if($teacher_count && $teacher_count > 0){
                //新数组赋值
                $teacher_array = [];

                //获取讲师列表
                $teacher_list  = Teacher::withCount('lessons as lesson_number')->where("is_del" , 0)->where("is_forbid" , 0)->where("is_recommend" , 1)->where("type" , 2)->offset(0)->limit(6)->get()->toArray();
                foreach($teacher_list as $k=>$v){
                    //根据大分类的id获取大分类的名称
                    if($v['parent_id'] && $v['parent_id'] > 0){
                        $lession_parent_name = Subject::where("id" , $v['parent_id'])->where("is_del" , 0)->where("is_forbid" , 0)->value("name");
                    }

                    //根据小分类的id获取小分类的名称
                    if($v['child_id'] && $v['child_id'] > 0){
                        $lession_child_name  = Subject::where("id" , $v['child_id'])->where("is_del" , 0)->where("is_forbid" , 0)->value("name");
                    }

                    //数组赋值
                    $teacher_array[] = [
                        'teacher_id'   =>   $v['id'] ,
                        'teacher_name' =>   $v['real_name'] ,
                        'teacher_icon' =>   $v['head_icon'] ,
                        'lession_parent_name' => $v['parent_id'] > 0 ? !empty($lession_parent_name) ? $lession_parent_name : '' : '',
                        'lession_child_name'  => $v['child_id']  > 0 ? !empty($lession_child_name)  ? $lession_child_name  : '' : '',
                        'star_num'     => $v['star_num'],
                        'lesson_number'=> $v['lesson_number'] ,
                        'student_number'=>$v['student_number']
                    ];
                }
                return response()->json(['code' => 200 , 'msg' => '获取讲师列表成功' , 'data' => $teacher_array]);
            } else {
                return response()->json(['code' => 200 , 'msg' => '获取讲师列表成功' , 'data' => []]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }


    /*
     * @param  description   APP版本升级接口
     * @param author    dzj
     * @param ctime     2020-05-27
     * return string
     */
    public function checkVersion() {
        try {
            //获取版本的最新更新信息
            $version_info = DB::table('ld_version')->select('is_online','is_mustup','version','content','download_url')->orderBy('create_at' , 'DESC')->first();
            $version_info->content = json_decode($version_info->content , true);
            return response()->json(['code' => 200 , 'msg' => '获取版本升级信息成功' , 'data' => $version_info]);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description   公开课列表接口
     * @param author    dzj
     * @param ctime     2020-05-25
     * return string
     */
    public function getOpenPublicList() {
        //获取提交的参数
        try{
            $pagesize = isset(self::$accept_data['pagesize']) && self::$accept_data['pagesize'] > 0 ? self::$accept_data['pagesize'] : 15;
            $page     = isset(self::$accept_data['page']) && self::$accept_data['page'] > 0 ? self::$accept_data['page'] : 1;
            $offset   = ($page - 1) * $pagesize;
            $today_class     = [];
            $tomorrow_class  = [];
            $over_class      = [];
            $arr             = [];
            $lession_list= DB::table('ld_course_open')->select(DB::raw("any_value(id) as id") , DB::raw("any_value(cover) as cover") , DB::raw("any_value(start_at) as start_at") , DB::raw("any_value(end_at) as end_at") , DB::raw("from_unixtime(start_at , '%Y-%m-%d') as start_time"))->where('is_del',0)->where('status',1)->orderBy('start_at' , 'DESC')->groupBy('start_time')->offset($offset)->limit($pagesize)->get()->toArray();
            //判读公开课列表是否为空
            if($lession_list && !empty($lession_list)){
                foreach($lession_list as $k=>$v){
                    //获取当天公开课列表的数据
                    if($v->start_time == date('Y-m-d')){
                        //根据开始日期和结束日期进行查询
                        $class_list = DB::table('ld_course_open')->select('id as open_class_id' , 'title' , 'cover' , DB::raw("from_unixtime(start_at , '%H:%i') as start_time") , DB::raw("from_unixtime(end_at , '%H:%i') as end_time") , 'start_at' , 'end_at')->where('start_at' , '>=' , strtotime($v->start_time.' 00:00:00'))->where('end_at' , '<=' , strtotime($v->start_time.' 23:59:59'))->where('is_del',0)->where('status',1)->orderBy('start_at' , 'ASC')->get()->toArray();
                        $today_arr = [];
                        foreach($class_list as $k1=>$v1){
                            //判断课程状态
                            if($v1->end_at < time()){
                                $status = 3;
                            } elseif($v1->start_at > time()){
                                $status = 2;
                            } else {
                                $status = 1;
                            }
                            //封装数组
                            $today_arr[] = [
                                'open_class_id'       =>   $v1->open_class_id  ,
                                'cover'               =>   $v1->cover ,
                                'start_time'          =>   $v1->start_time ,
                                'end_time'            =>   $v1->end_time ,
                                'open_class_name'     =>   $v1->title ,
                                'status'              =>   $status
                            ];
                        }
                        //课程时间点排序
                        array_multisort(array_column($today_arr, 'start_time') , SORT_ASC , $today_arr);
                        //公开课日期赋值
                        $today_class[$v->start_time]['open_class_date']   = $v->start_time;
                        //公开课列表赋值
                        $today_class[$v->start_time]['open_class_list']   = $today_arr;
                    } else if($v->start_time > date('Y-m-d')) {
                        //公开课日期赋值
                        $class_list = DB::table('ld_course_open')->select('id as open_class_id' , 'title' , 'cover' , DB::raw("from_unixtime(start_at , '%H:%i') as start_time") , DB::raw("from_unixtime(end_at , '%H:%i') as end_time") , 'start_at' , 'end_at')->where("start_at" , ">" , strtotime($v->start_time.' 00:00:00'))->where("end_at" , "<" , strtotime($v->start_time.' 23:59:59'))->where('is_del',0)->where('status',1)->orderBy('start_at' , 'ASC')->get()->toArray();
                        $date2_arr = [];
                        foreach($class_list as $k2=>$v2){
                            $date2_arr[] = [
                                'open_class_id'       =>   $v2->open_class_id  ,
                                'cover'               =>   $v2->cover ,
                                'start_time'          =>   $v2->start_time ,
                                'end_time'            =>   $v2->end_time ,
                                'open_class_name'     =>   $v2->title ,
                                'status'              =>   2
                            ];
                        }
                        //课程时间点排序
                        array_multisort(array_column($date2_arr, 'start_time') , SORT_ASC , $date2_arr);
                        //公开课日期赋值
                        $tomorrow_class[$v->start_time]['open_class_date']   = $v->start_time;
                        //公开课列表赋值
                        $tomorrow_class[$v->start_time]['open_class_list']   = $date2_arr;
                    } else {
                        //公开课日期赋值
                        $class_list = DB::table('ld_course_open')->select('id as open_class_id' , 'title' , 'cover' , DB::raw("from_unixtime(start_at , '%H:%i') as start_time") , DB::raw("from_unixtime(end_at , '%H:%i') as end_time") , 'start_at' , 'end_at')->where("start_at" , ">" , strtotime($v->start_time.' 00:00:00'))->where("end_at" , "<" , strtotime($v->start_time.' 23:59:59'))->where('is_del',0)->where('status',1)->orderBy('start_at' , 'ASC')->get()->toArray();
                        $date_arr = [];
                        foreach($class_list as $k2=>$v2){
                            $date_arr[] = [
                                'open_class_id'       =>   $v2->open_class_id  ,
                                'cover'               =>   $v2->cover ,
                                'start_time'          =>   $v2->start_time ,
                                'end_time'            =>   $v2->end_time ,
                                'open_class_name'     =>   $v2->title ,
                                'status'              =>   3
                            ];
                        }
                        //课程时间点排序
                        array_multisort(array_column($date_arr, 'start_time') , SORT_ASC , $date_arr);
                        //公开课日期赋值
                        $over_class[$v->start_time]['open_class_date']   = $v->start_time;
                        //公开课列表赋值
                        $over_class[$v->start_time]['open_class_list']   = $date_arr;
                    }
                }
                //判断明天课程是否为空
                if($tomorrow_class && !empty($tomorrow_class)){
                    //课程时间点排序
                    array_multisort(array_column($tomorrow_class, 'open_class_date') , SORT_ASC , $tomorrow_class);
                }
                $arr =  array_merge(array_values($today_class) , array_values($tomorrow_class) , array_values($over_class));
            }
            return response()->json(['code' => 200 , 'msg' => '获取公开课列表成功' , 'data' => $arr]);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description   名师列表接口
     * @param author    dzj
     * @param ctime     2020-06-02
     * return string
     */
    public function getFamousTeacherList(){
        //获取提交的参数
        try{
            //每页显示条数
            $pagesize = isset(self::$accept_data['pagesize']) && self::$accept_data['pagesize'] > 0 ? self::$accept_data['pagesize'] : 15;
            //当前页数
            $page     = isset(self::$accept_data['page']) && self::$accept_data['page'] > 0 ? self::$accept_data['page'] : 1;
            //分页标识符
            $offset   = ($page - 1) * $pagesize;
            //类型(0表示综合,1表示人气,2表示好评)
            $type     = isset(self::$accept_data['type']) && self::$accept_data['type'] > 0 ? self::$accept_data['type'] : 0;

            //根据人气、好评、综合进行排序
            if($type == 1){ //人气排序|好评排序
                //获取名师列表
                $famous_teacher_list = Teacher::withCount('lessons as lesson_number')->where('type' , 2)->where('is_del' , 0)->where('is_forbid' , 0)->get();
            } else {  //综合排序|好评
                //获取名师列表
                $famous_teacher_list = Teacher::withCount('lessons as lesson_number')->where('type' , 2)->where('is_del' , 0)->where('is_forbid' , 0)->orderBy('is_recommend' , 'DESC')->offset($offset)->limit($pagesize)->get();
            }

            //判断讲师是否为空
            if($famous_teacher_list && !empty($famous_teacher_list)){
                //将对象转化为数组信息
                $famous_teacher_list = $famous_teacher_list->toArray();
                if($type == 1){
                    $sort_field = $type == 1 ? 'student_number' : 'star_num';
                    array_multisort(array_column($famous_teacher_list, $sort_field) , SORT_DESC , $famous_teacher_list);
                    $famous_teacher_list = array_slice($famous_teacher_list,$offset,$pagesize);
                }

                //空数组
                $teacher_list = [];
                foreach($famous_teacher_list as $k=>$v){
                    //根据大分类的id获取大分类的名称
                    if($v['parent_id'] && $v['parent_id'] > 0){
                        $lession_parent_name = Subject::where("id" , $v['parent_id'])->where("is_del" , 0)->where("is_forbid" , 0)->value("name");
                    }

                    //根据小分类的id获取小分类的名称
                    if($v['child_id'] && $v['child_id'] > 0){
                        $lession_child_name  = Subject::where("id" , $v['child_id'])->where("is_del" , 0)->where("is_forbid" , 0)->value("name");
                    }

                    //数组数值信息赋值
                    $teacher_list[] = [
                        'teacher_id'          =>  $v['id'] ,
                        'teacher_icon'        =>  $v['head_icon'] ,
                        'teacher_name'        =>  $v['real_name'] ,
                        'lession_parent_name' =>  $v['parent_id'] > 0 ? !empty($lession_parent_name) ? $lession_parent_name : '' : '',
                        'lession_child_name'  =>  $v['child_id']  > 0 ? !empty($lession_child_name)  ? $lession_child_name  : '' : '',
                        'star_num'            =>  $v['star_num'] ,
                        'lesson_number'       =>  $v['lesson_number'] ,
                        'student_number'      =>  $v['student_number']
                    ];
                }
            } else {
                $teacher_list = "";
            }
            return response()->json(['code' => 200 , 'msg' => '获取名师列表成功' , 'data' => $teacher_list]);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description   名师详情接口
     * @param author    dzj
     * @param ctime     2020-06-02
     * return string
     */
    public function getFamousTeacherInfo(){
        //获取提交的参数
        try{
            //获取名师id
            $teacher_id  = isset(self::$accept_data['teacher_id']) && !empty(self::$accept_data['teacher_id']) && self::$accept_data['teacher_id'] > 0 ? self::$accept_data['teacher_id'] : 0;
            if(!$teacher_id || $teacher_id <= 0 || !is_numeric($teacher_id)){
                return response()->json(['code' => 202 , 'msg' => '名师id不合法']);
            }

            //空数组赋值
            $teacher_array = "";

            //根据名师的id获取名师的详情信息
            $teacher_info  =  Teacher::where('id' , $teacher_id)->where('type' , 2)->where('is_del' , 0)->where('is_forbid' , 0)->first();
            if($teacher_info && !empty($teacher_info)){
                //名师数组信息
                $teacher_array = [
                    'teacher_icon'   =>   $teacher_info->head_icon  ,
                    'teacher_name'   =>   $teacher_info->real_name  ,
                    'teacher_content'=>   $teacher_info->content
                ];
            }
            return response()->json(['code' => 200 , 'msg' => '获取名师详情成功' , 'data' => $teacher_array]);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description   名师课程列表接口
     * @param author    dzj
     * @param ctime     2020-06-02
     * return string
     */
    public function getTeacherLessonList(){
        //获取提交的参数
        try{
            //获取名师id
            $teacher_id  = isset(self::$accept_data['teacher_id']) && !empty(self::$accept_data['teacher_id']) && self::$accept_data['teacher_id'] > 0 ? self::$accept_data['teacher_id'] : 0;
            if(!$teacher_id || $teacher_id <= 0 || !is_numeric($teacher_id)){
                return response()->json(['code' => 202 , 'msg' => '名师id不合法']);
            }

            //分页相关的参数
            $pagesize = isset(self::$accept_data['pagesize']) && self::$accept_data['pagesize'] > 0 ? self::$accept_data['pagesize'] : 15;
            $page     = isset(self::$accept_data['page']) && self::$accept_data['page'] > 0 ? self::$accept_data['page'] : 1;
            $offset   = ($page - 1) * $pagesize;

            //获取名师课程列表
            $teacher_lesson_list = Lesson::join('ld_course_teacher','ld_course_teacher.course_id','=','ld_course.id')
                    ->select('ld_course.id', 'admin_id', 'title', 'cover', 'pricing', 'sale_price', 'buy_num', 'status', 'ld_course.is_del')
                    ->where(['ld_course.is_del'=> 0, 'ld_course.status' => 1])
                    ->offset($offset)->limit($pagesize)->get()->toArray();
            if($teacher_lesson_list && !empty($teacher_lesson_list)){
                return response()->json(['code' => 200 , 'msg' => '获取名师课程列表成功' , 'data' => $teacher_lesson_list]);
            } else {
                return response()->json(['code' => 200 , 'msg' => '获取名师课程列表成功' , 'data' => []]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }


    /**
     * @param  description   首页学科接口
     * @param author    sxl
     * @param ctime     2020-05-28
     * @return string
     */
    public function getSubjectList() {
        //获取提交的参数
        try{
            $subject = Subject::select('id', 'subject_name')->where(['is_del' => 0,'parent_id' => 0])->limit(6)->get();
            return $this->response($subject);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /**
     * @param  description   首页课程接口
     * @param author    sxl
     * @param ctime     2020-05-28
     * @return string
     */
    public function getLessonList() {
        //获取提交的参数
        try{
                $subject = Subject::select('id', 'subject_name')
                        ->where(['is_del' => 0,'parent_id' => 0])
                        ->limit(4)
                        ->get();
                foreach($subject as $k =>$v){
                    $subject[$k]['lesson'] = Lesson::join("ld_course_subject","ld_course_subject.id","=","ld_course.parent_id")
                    ->select('ld_course.id', 'ld_course.title', 'ld_course.cover', 'ld_course.buy_num', 'ld_course.pricing as old_price', 'ld_course.sale_price')
                    ->where(['ld_course.is_del' => 0, 'ld_course.is_recommend' => 1, 'ld_course.status' => 1,'ld_course.parent_id' => $v['id']])
                    ->get();
                }
                foreach($subject as $k => $v){

                    foreach($v['lesson'] as $kk => $vv){

                        $token = isset($_REQUEST['user_token']) ? $_REQUEST['user_token'] : 0;
                        $student = Student::where('token', $token)->first();
                        //是否收藏
                        //购买人数  基数加真是购买人数
                        $vv['sold_num'] =  Order::where(['oa_status'=>1,'class_id'=>$vv['id']])->count() + $vv['buy_num'];
                        if(!empty($student)){
                            $num = Order::where(['student_id'=>$student['id'],'status'=>2,'class_id'=>$vv['id']])->count();
                            if($num > 0){
                                $vv['is_buy'] = 1;
                            }else{
                                $vv['is_buy'] = 0;
                            }
                        }else{
                            $vv['is_buy'] = 0;
                        }
                    }
                }


            return $this->response($subject);
        } catch (Exception $ex) {
            return $this->response($ex->getMessage());
        }
    }
}

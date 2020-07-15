<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Coures extends Model {
    //指定别的表名
    public $table = 'ld_course';
    //时间戳设置
    public $timestamps = false;
    //列表
    public static function courseList($data){
        //获取用户网校id
        $data['school_status'] = isset(AdminLog::getAdminInfo()->admin_user->school_status) ? AdminLog::getAdminInfo()->admin_user->school_status : 0;
        $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        if(!isset($data['nature']) || empty($data['nature'])){
            //自增
            $count1 = self::where(['is_del'=>0])->where(function($query) use ($data,$school_id) {
                //判断总校 查询所有或一个分校
//                if($data['school_status'] == 1){
//                    if(!empty($data['school_id']) && $data['school_id'] != ''){
//                        $query->where('school_id',$data['school_id']);
//                    }
//                }else{
                    //分校查询当前学校
                    $query->where('school_id',$school_id);
//                }
                //学科大类
                if(!empty($data['coursesubjectOne']) && $data['coursesubjectOne'] != ''){
                    $query->where('parent_id',$data['coursesubjectOne']);
                }

                //学科小类
                if(!empty($data['coursesubjectTwo']) && $data['coursesubjectTwo'] != ''){
                    $query->where('child_id',$data['coursesubjectTwo']);
                }
                //状态
                if(!empty($data['status']) && $data['status'] != ''){
                    $query->where('status',$data['status']-1);
                }
            })->count();
            //授权
            $count2 = CourseSchool::where(['is_del'=>0])->where(function($query) use ($data,$school_id) {
                //判断总校 查询所有或一个分校
//                if($data['school_status'] == 1){
//                    if(!empty($data['school_id']) && $data['school_id'] != ''){
//                        $query->where('to_school_id',$data['school_id']);
//                    }
//                }else{
                    //分校查询当前学校
                    $query->where('to_school_id',$school_id);
//                }
                //学科大类
                if(!empty($data['coursesubjectOne']) && $data['coursesubjectOne'] != ''){
                    $query->where('parent_id',$data['coursesubjectOne']);
                }
                //学科小类
                if(!empty($data['coursesubjectTwo']) && $data['coursesubjectTwo'] != ''){
                    $query->where('child_id',$data['coursesubjectTwo']);
                }
                //状态
                if(!empty($data['status']) && $data['status'] != ''){
                    $query->where('status',$data['status']-1);
                }
            })->count();
            $count = $count1 + $count2;
        }else if($data['nature']-1 == 1){
            //授权
            $count = CourseSchool::where(['is_del'=>0])->where(function($query) use ($data,$school_id) {
                //判断总校 查询所有或一个分校
//                if($data['school_status'] == 1){
//                    if(!empty($data['school_id']) && $data['school_id'] != ''){
//                        $query->where('to_school_id',$data['school_id']);
//                    }
//                }else{
                    //分校查询当前学校
                    $query->where('to_school_id',$school_id);
//                }
                //学科大类
                if(!empty($data['coursesubjectOne']) && $data['coursesubjectOne'] != ''){
                    $query->where('parent_id',$data['coursesubjectOne']);
                }
                //学科小类
                if(!empty($data['coursesubjectTwo']) && $data['coursesubjectTwo'] != ''){
                    $query->where('child_id',$data['coursesubjectTwo']);
                }
                //状态
                if(!empty($data['status']) && $data['status'] != ''){
                    $query->where('status',$data['status']-1);
                }
            })->count();
        }else{
            //自增
            $count = self::where(['is_del'=>0])->where(function($query) use ($data,$school_id) {
                //判断总校 查询所有或一个分校
//                if($data['school_status'] == 1){
//                    if(!empty($data['school_id']) && $data['school_id'] != ''){
//                        $query->where('school_id',$data['school_id']);
//                    }
//                }else{
                    //分校查询当前学校
                    $query->where('school_id',$school_id);
//                }
                //学科大类
                if(!empty($data['coursesubjectOne']) && $data['coursesubjectOne'] != ''){
                    $query->where('parent_id',$data['coursesubjectOne']);
                }

                //学科小类
                if(!empty($data['coursesubjectTwo']) && $data['coursesubjectTwo'] != ''){
                    $query->where('child_id',$data['coursesubjectTwo']);
                }
                //状态
                if(!empty($data['status']) && $data['status'] != ''){
                    $query->where('status',$data['status']-1);
                }
            })->count();
        }
        $list=[];
        if($count > 0){
            if(!isset($data['nature']) || empty($data['nature'])){
                //全部
                $list1 = self::where(['is_del'=>0])->where(function($query) use ($data,$school_id) {
                    //判断总校 查询所有或一个分校
//                    if($data['school_status'] == 1){
//                        if(!empty($data['school_id']) && $data['school_id'] != ''){
//                            $query->where('school_id',$data['school_id']);
//                        }
////                    }else{
                        //分校查询当前学校
                        $query->where('school_id',$school_id);
//                    }
                    //学科大类
                    if(!empty($data['coursesubjectOne']) && $data['coursesubjectOne'] != ''){
                        $query->where('parent_id',$data['coursesubjectOne']);
                    }
                    //学科小类
                    if(!empty($data['coursesubjectTwo']) && $data['coursesubjectTwo'] != ''){
                        $query->where('child_id',$data['coursesubjectTwo']);
                    }
                    //状态
                    if(!empty($data['status']) && $data['status'] != ''){
                        $query->where('status',$data['status']-1);
                    }
                })
                    ->orderBy('id','desc')->get()->toArray();
                foreach($list1  as $k=>&$v){
                    $where=[
                        'course_id'=>$v['id'],
                        'is_del'=>0
                    ];
                    if(!empty($data['method'])) {
                        $where['method_id'] = $data['method'];
                    }
                    $method = Couresmethod::select('method_id')->where($where)->get()->toArray();
                    if(empty($method)){
                        unset($list1[$k]);
                    }else{
                        foreach ($method as $key=>&$val){
                            if($val['method_id'] == 1){
                                $val['method_name'] = '直播';
                            }
                            if($val['method_id'] == 2){
                                $val['method_name'] = '录播';
                            }
                            if($val['method_id'] == 3){
                                $val['method_name'] = '其他';
                            }
                        }
                        $v['method'] = $method;
                    }
                }
                $list2 = CourseSchool::where(['is_del'=>0])->where(function($query) use ($data,$school_id) {
                    //判断总校 查询所有或一个分校
//                    if($data['school_status'] == 1){
//                        if(!empty($data['school_id']) && $data['school_id'] != ''){
//                            $query->where('to_school_id',$data['school_id']);
//                        }
//                    }else{
                        //分校查询当前学校
                        $query->where('to_school_id',$school_id);
//                    }
                    //学科大类
                    if(!empty($data['coursesubjectOne']) && $data['coursesubjectOne'] != ''){
                        $query->where('parent_id',$data['coursesubjectOne']);
                    }
                    //学科小类
                    if(!empty($data['coursesubjectTwo']) && $data['coursesubjectTwo'] != ''){
                        $query->where('child_id',$data['coursesubjectTwo']);
                    }
                    //状态
                    if(!empty($data['status']) && $data['status'] != ''){
                        $query->where('status',$data['status']-1);
                    }
                })
                    ->orderBy('id','desc')->get()->toArray();
                foreach($list2  as $ks=>&$vs){
                    $vs['nature'] = 1;
                    $where=[
                        'course_id'=>$vs['course_id'],
                        'is_del'=>0
                    ];
                    if(!empty($data['method'])) {
                        $where['method_id'] = $data['method'];
                    }
                    $method = Couresmethod::select('method_id')->where($where)->get()->toArray();
                    if(!$method){
                        unset($list2[$k]);
                    }else{
                        foreach ($method as $key=>&$val){
                            if($val['method_id'] == 1){
                                $val['method_name'] = '直播';
                            }
                            if($val['method_id'] == 2){
                                $val['method_name'] = '录播';
                            }
                            if($val['method_id'] == 3){
                                $val['method_name'] = '其他';
                            }
                        }
                        $vs['method'] = $method;
                    }
                }
                $list =array_slice(array_merge($list1,$list2),($page - 1) * $pagesize, $pagesize);
            }else if($data['nature']-1 == 1){
                //授权
                $list = CourseSchool::where(['is_del'=>0])->where(function($query) use ($data,$school_id) {
                    //判断总校 查询所有或一个分校
//                    if($data['school_status'] == 1){
//                        if(!empty($data['school_id']) && $data['school_id'] != ''){
//                            $query->where('to_school_id',$data['school_id']);
//                        }
//                    }else{
                        //分校查询当前学校
                        $query->where('to_school_id',$school_id);
//                    }
                    //学科大类
                    if(!empty($data['coursesubjectOne']) && $data['coursesubjectOne'] != ''){
                        $query->where('parent_id',$data['coursesubjectOne']);
                    }
                    //学科小类
                    if(!empty($data['coursesubjectTwo']) && $data['coursesubjectTwo'] != ''){
                        $query->where('child_id',$data['coursesubjectTwo']);
                    }
                    //状态
                    if(!empty($data['status']) && $data['status'] != ''){
                        $query->where('status',$data['status']-1);
                    }
                })->orderBy('id','desc')
                    ->offset($offset)->limit($pagesize)->get()->toArray();
                    foreach($list  as $k=>&$v){
                        $v['nature'] = 1;
                        $where=[
                            'course_id'=>$v['course_id'],
                            'is_del'=>0
                        ];
                        if(!empty($data['method'])) {
                            $where['method_id'] = $data['method'];
                        }
                        $method = Couresmethod::select('method_id')->where($where)->get()->toArray();
                        if(!$method){
                            unset($list[$k]);
                        }else{
                            foreach ($method as $key=>&$val){
                                if($val['method_id'] == 1){
                                    $val['method_name'] = '直播';
                                }
                                if($val['method_id'] == 2){
                                    $val['method_name'] = '录播';
                                }
                                if($val['method_id'] == 3){
                                    $val['method_name'] = '其他';
                                }
                            }
                            $v['method'] = $method;
                        }
                    }
            }else{
                //自增
                $list = self::where(['is_del'=>0])->where(function($query) use ($data,$school_id) {
                    //判断总校 查询所有或一个分校
//                    if($data['school_status'] == 1){
//                        if(!empty($data['school_id']) && $data['school_id'] != ''){
//                            $query->where('school_id',$data['school_id']);
//                        }
//                    }else{
                        //分校查询当前学校
                        $query->where('school_id',$school_id);
//                    }
                    //学科大类
                    if(!empty($data['coursesubjectOne']) && $data['coursesubjectOne'] != ''){
                        $query->where('parent_id',$data['coursesubjectOne']);
                    }
                    //学科小类
                    if(!empty($data['coursesubjectTwo']) && $data['coursesubjectTwo'] != ''){
                        $query->where('child_id',$data['coursesubjectTwo']);
                    }
                    //状态
                    if(!empty($data['status']) && $data['status'] != ''){
                        $query->where('status',$data['status']-1);
                    }
                })
                    ->orderBy('id','desc')
                    ->offset($offset)->limit($pagesize)->get()->toArray();
                foreach($list  as $k=>&$v){
                    $where=[
                        'course_id'=>$v['id'],
                        'is_del'=>0
                    ];
                    if(!empty($data['method'])) {
                        $where['method_id'] = $data['method'];
                    }
                    $method = Couresmethod::select('method_id')->where($where)->get()->toArray();
                    if(!$method){
                        unset($list[$k]);
                    }else{
                        foreach ($method as $key=>&$val){
                            if($val['method_id'] == 1){
                                $val['method_name'] = '直播';
                            }
                            if($val['method_id'] == 2){
                                $val['method_name'] = '录播';
                            }
                            if($val['method_id'] == 3){
                                $val['method_name'] = '其他';
                            }
                        }
                        $v['method'] = $method;
                    }
                }
            }

        }
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$list,'where'=>$data,'page'=>$page];
    }
    //添加
    public static function courseAdd($data){
        if(empty($data) || !isset($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['parent']) || empty($data['parent'])){
            return ['code' => 201 , 'msg' => '请选择学科'];
        }
        if(!isset($data['title']) || empty($data['title'])){
            return ['code' => 201 , 'msg' => '学科名称不能为空'];
        }
        if(!isset($data['cover']) || empty($data['cover'])){
            return ['code' => 201 , 'msg' => '学科封面不能为空'];
        }
//        if(!isset($data['pricing']) || empty($data['pricing'])){
//            return ['code' => 201 , 'msg' => '请填写课程原价'];
//        }
//        if(!isset($data['sale_price']) || empty($data['sale_price'])){
//            return ['code' => 201 , 'msg' => '请填写课程优惠价'];
//        }
        $data['pricing'] = isset($data['pricing'])?$data['pricing']:0;
        $data['sale_price'] = isset($data['sale_price'])?$data['sale_price']:0;
        if(!isset($data['method']) || empty($data['method'])){
            return ['code' => 201 , 'msg' => '请选择授课方式'];
        }
        if(!isset($data['teacher']) || empty($data['teacher'])){
            return ['code' => 201 , 'msg' => '请选择讲师'];
        }
        if(!isset($data['describe']) || empty($data['describe'])){
            return ['code' => 201 , 'msg' => '课程描述不能为空'];
        }
        if(!isset($data['introduce']) || empty($data['introduce'])){
            return ['code' => 201 , 'msg' => '课程简介不能为空'];
        }
        $user_id = isset(AdminLog::getAdminInfo()->admin_user->id)?AdminLog::getAdminInfo()->admin_user->id:0;
        $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id)?AdminLog::getAdminInfo()->admin_user->school_id:0;
        $title = self::where(['title'=>$data['title'],'is_del'=>0,'nature'=>1])->first();
        if($title){
            return ['code' => 201 , 'msg' => '课程已存在'];
        }
        DB::beginTransaction();
        //入课程表  课程授课表 课程讲师表
        $parent = json_decode($data['parent'],true);
        $couser = self::insertGetId([
            'admin_id' => $user_id,
            'school_id' => $school_id,
            'parent_id' => isset($parent[0])?$parent[0]:0,
            'child_id' => isset($parent[1])?$parent[1]:0,
            'title' => $data['title'],
            'keywords' => isset($data['keywords'])?$data['keywords']:'',
            'cover' => $data['cover'],
            'pricing' => $data['pricing'],
            'sale_price' => $data['sale_price'],
            'buy_num' => isset($data['buy_num'])?$data['buy_num']:0,
            'expiry' => isset($data['expiry'])?$data['expiry']:24,
            'describe' => $data['describe'],
            'introduce' => $data['introduce'],
        ]);
        if($couser){
            $method = json_decode($data['method'],true);
            foreach ($method as $k=>$v){
                 Couresmethod::insert([
                    'course_id' => $couser,
                    'method_id' => $v
                ]);
            }
            $teacher = json_decode($data['teacher'],true);
            foreach ($teacher as $k=>$v){
                 Couresteacher::insert([
                    'course_id' => $couser,
                    'teacher_id' => $v
                ]);
            }
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'courseAdd' ,
                'route_url'      =>  'admin/Course/courseAdd' ,
                'operate_method' =>  'add' ,
                'content'        =>  '添加操作'.json_encode($data) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            DB::commit();
            return ['code' => 200 , 'msg' => '添加成功'];
        }else{
            DB::rollback();
            return ['code' => 202 , 'msg' => '添加失败'];
        }
    }
    //删除
    public static function courseDel($data){
        if(empty($data) || !isset($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '请选择学科大类'];
        }
        if($data['nature'] == 1){
            return ['code' => 203, 'msg' => '授权课程，无法删除'];
        }
        $school_status = isset(AdminLog::getAdminInfo()->admin_user->school_status) ? AdminLog::getAdminInfo()->admin_user->school_status : 0;
        if($school_status == 1){
            // 总校删除 先查询授权分校库存，没有进行删除
            $courseSchool = CourseStocks::where('course_id',$data['id'])->where('add_number','>',0)->get()->toArray();
            if(!empty($courseSchool)) {
                return ['code' => 203, 'msg' => '此课程授权给分校，无法删除'];
            }
        }
        $del = self::where(['id'=>$data['id']])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
        if($del){
            $user_id = AdminLog::getAdminInfo()->admin_user->id;
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'courseDel' ,
                'route_url'      =>  'admin/Course/courseDel' ,
                'operate_method' =>  'courseDel' ,
                'content'        =>  '删除操作'.json_encode($data) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return ['code' => 200 , 'msg' => '删除成功'];
        }else{
            return ['code' => 201 , 'msg' => '删除失败'];
        }
    }
    //单条查询
    public static function courseFirst($data){
        if(empty($data) || !isset($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '请选择课程'];
        }
        $nature = isset($data['nature'])?$data['nature']:0;
        if($nature == 1){
            $find = CourseSchool::where(['id'=>$data['id'],'is_del'=>0])->first();
            if(!$find){
                return ['code' => 201 , 'msg' => '此数据不存在'];
            }
            $find['nature'] = 1;
            //查询授课方式
            $method= Couresmethod::select('method_id')->where(['course_id'=>$find['course_id'],'is_del'=>0])->get()->toArray();
            $find['method'] = array_column($method, 'method_id');
            $where = [];
            if($find['parent_id'] > 0){
                $where[0] = $find['parent_id'];
            }
            if($find['parent_id'] > 0 && $find['child_id'] > 0){
                $where[1] = $find['child_id'];
            }

            $find['parent'] = $where;
            unset($find['parent_id'],$find['child_id']);
            //查询讲师
            $teachers = $teacher = Couresteacher::select('teacher_id')->where(['course_id'=>$find['course_id'],'is_del'=>0])->get()->toArray();
            if(!empty($teachers)){
                foreach ($teachers as $k=>&$v){
                    $name = Lecturer::select('real_name')->where(['id'=>$v['teacher_id'],'is_del'=>0,'type'=>2])->first();
                    if(!empty($name)){
                        $v['real_name'] = $name['real_name'];
                    }else{
                        unset($teachers[$k]);
                    }
                }
            }
            $find['teacher'] = array_column($teacher, 'teacher_id');
            $find['teachers'] = $teachers;
        }else{
            $find = self::where(['id'=>$data['id'],'is_del'=>0])->first();
            if(!$find){
                return ['code' => 201 , 'msg' => '此数据不存在'];
            }
            //查询授课方式
            $method= Couresmethod::select('method_id')->where(['course_id'=>$find['id'],'is_del'=>0])->get()->toArray();
            $find['method'] = array_column($method, 'method_id');
            $where = [];
            if($find['parent_id'] > 0){
                $where[0] = $find['parent_id'];
            }
            if($find['parent_id'] > 0 && $find['child_id'] > 0){
                $where[1] = $find['child_id'];
            }
            $find['parent'] = $where;
            unset($find['parent_id'],$find['child_id']);
            //查询讲师
            $teachers = $teacher = Couresteacher::select('teacher_id')->where(['course_id'=>$data['id'],'is_del'=>0])->get()->toArray();
            if(!empty($teachers)){
                foreach ($teachers as $k=>&$v){
                    $name = Lecturer::select('real_name')->where(['id'=>$v['teacher_id'],'is_del'=>0,'type'=>2])->first();
                    $v['real_name'] = $name['real_name'];
                }
            }
            $find['teacher'] = array_column($teacher, 'teacher_id');
            $find['teachers'] = $teachers;
        }
        return ['code' => 200 , 'msg' => '查询成功','data'=>$find];
    }
    //修改
    public static function courseUpdate($data){
        if(empty($data) || !isset($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        DB::beginTransaction();
        try{
                //修改 课程表 课程授课表 课程讲师表
                $cousermethod = isset($data['method'])?$data['method']:'';
                $couserteacher = isset($data['teacher'])?$data['teacher']:'';
                unset($data['/admin/course/courseUpdate']);
                unset($data['method']);
                unset($data['teacher']);
                unset($data['teachers']);
                $parent = json_decode($data['parent'],true);
                if(isset($parent[0]) && !empty($parent[0])){
                    $data['parent_id'] = $parent[0];
                }
                if(isset($parent[1]) && !empty($parent[1])){
                    $data['child_id'] = $parent[1];
                }
                unset($data['parent']);

                //判断自增还是授权
                $nature = isset($data['nature'])?$data['nature']:0;
                if($nature == 1){
                    //只修改基本信息
                    unset($data['nature']);
                    $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id)?AdminLog::getAdminInfo()->admin_user->school_id:0;
                    $data['update_at'] = date('Y-m-d H:i:s');
                    $id = $data['id'];
                    unset($data['id']);
                    unset($data['parent_id']);
                    unset($data['child_id']);
                    CourseSchool::where(['id'=>$id])->update($data);
                }else {
                    $data['update_at'] = date('Y-m-d H:i:s');
                    self::where(['id' => $data['id']])->update($data);
                    if (!empty($cousermethod)) {
                        Couresmethod::where(['course_id' => $data['id']])->update(['is_del' => 1, 'update_at' => date('Y-m-d H:i:s')]);
                        $method = json_decode($cousermethod, true);
                        foreach ($method as $k => $v) {
                            $infor = Couresmethod::where(['course_id' => $data['id'], 'method_id' => $v])->first();
                            if ($infor) {
                                Couresmethod::where(['id' => $infor['id']])->update(['is_del' => 0, 'update_at' => date('Y-m-d H:i:s')]);
                            } else {
                                Couresmethod::insert([
                                    'course_id' => $data['id'],
                                    'method_id' => $v
                                ]);
                            }
                        }
                    }
                    if (!empty($couserteacher)) {
                        Couresteacher::where(['course_id' => $data['id']])->update(['is_del' => 1, 'update_at' => date('Y-m-d H:i:s')]);
                        $teacher = json_decode($couserteacher, true);
                        foreach ($teacher as $k => $v) {
                            $infor = Couresteacher::where(['course_id' => $data['id'], 'teacher_id' => $v])->first();
                            if ($infor) {
                                Couresteacher::where(['id' => $infor['id']])->update(['is_del' => 0, 'update_at' => date('Y-m-d H:i:s')]);
                            } else {
                                Couresteacher::insert([
                                    'course_id' => $data['id'],
                                    'teacher_id' => $v
                                ]);
                            }
                        }
                    }
                }
            $user_id = AdminLog::getAdminInfo()->admin_user->id;
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'courseUpdate' ,
                'route_url'      =>  'admin/Course/courseUpdate' ,
                'operate_method' =>  'Update' ,
                'content'        =>  '修改操作'.json_encode($data) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
        DB::commit();
        return ['code' => 200 , 'msg' => '修改成功'];
        } catch (Exception $ex) {
            DB::rollback();
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //修改推荐状态
    public static function courseComment($data){
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => 'id为空'];
        }
        $nature = isset($data['nature'])?$data['nature']:0;
        if($nature == 1){
            $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            $find = CourseSchool::where(['to_school_id'=>$school_id,'course_id'=>$data['id']])->first()->toArray();
            $recommend = $find['is_recommend'] == 1 ? 0:1;
            $up = CourseSchool::where(['id'=>$find['id']])->update(['is_recommend'=>$recommend,'update_at'=>date('Y-m-d H:i:s')]);
        }else{
            $find = self::where(['id'=>$data['id']])->first()->toArray();
            $recommend = $find['is_recommend'] == 1 ? 0:1;
            $up = self::where(['id'=>$data['id']])->update(['is_recommend'=>$recommend,'update_at'=>date('Y-m-d H:i:s')]);
        }
        if($up){
            $user_id = AdminLog::getAdminInfo()->admin_user->id;
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'courseComment' ,
                'route_url'      =>  'admin/Course/courseComment' ,
                'operate_method' =>  'update' ,
                'content'        =>  '修改推荐状态操作'.json_encode($data) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return ['code' => 200 , 'msg' => '修改成功'];
        }else{
            return ['code' => 201 , 'msg' => '修改失败'];
        }
    }
    //修改课程状态
    public static function courseUpStatus($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '课程id不能为空'];
        }
        if(!isset($data['status']) || empty($data['status'])){
            return ['code' => 201 , 'msg' => '课程状态不能为空'];
        }
        $nature = isset($data['nature'])?$data['nature']:0;
        if($nature == 1){
            $up = CourseSchool::where('id',$data['id'])->update(['status'=>$data['status'],'update_at'=>date('Y-m-d H:i:s')]);
        }else{
            $up = self::where('id',$data['id'])->update(['status'=>$data['status'],'update_at'=>date('Y-m-d H:i:s')]);
        }
        if($up){
            $user_id = AdminLog::getAdminInfo()->admin_user->id;
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'courseUpStatus' ,
                'route_url'      =>  'admin/Course/courseUpStatus' ,
                'operate_method' =>  'update' ,
                'content'        =>  '修改课程状态操作'.json_encode($data) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return ['code' => 200, 'msg' => '操作成功'];
        }else{
            return ['code' => 202 , 'msg' => '操作失败'];
        }
    }
    //课程关联直播的列表
    public static function liveToCourseList($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '课程id不能为空'];
        }
        $list = [];
        $first = [];
        $checked = [];
        $count = CourseLiveResource::where(['course_id'=>$data['id'],'is_del'=>0])->count();
        if($count > 0){
            $list = CourseLiveResource::where(['course_id'=>$data['id'],'is_del'=>0])->get()->toArray();
            foreach ($list as $k=>&$v){
                array_push($first,$v['id']);
                $names = Live::select('name')->where(['id'=>$v['resource_id']])->first();
                $v['name'] = $names['name'];
                $shift_no = LiveClass::where(['resource_id'=>$v['resource_id'],'is_del'=>0,'is_forbid'=>0])->get()->toArray();
                foreach ($shift_no as $ks=>&$vs){
                    if($ks == 0){
                        if($v['shift_id'] != ''){
                            array_push($checked,$v['shift_id']);
                        }else{
                            array_push($checked,$vs['id']);
                        }
                    }
                    //查询课次
                    $class_num = LiveChild::where(['shift_no_id'=>$vs['id'],'is_del'=>0,'status'=>1])->count();
                    //课时
                    $class_time = LiveChild::where(['shift_no_id'=>$vs['id'],'is_del'=>0,'status'=>1])->sum('class_hour');
                    $vs['class_num'] = $class_num;
                    $vs['class_time'] = $class_time;
                }
                $v['shift_no'] = $shift_no;
            }
        }
        return ['code' => 200 , 'msg' => '获取成功','data'=>$list,'first'=>$first,'checked'=>$checked];
    }
    //课程进行排课
    public static function liveToCourseshift($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['first']) || empty($data['first'])){
            return ['code' => 201 , 'msg' => 'first参数为空'];
        }
        if(!isset($data['checked']) || empty($data['checked'])){
            return ['code' => 201 , 'msg' => 'checked参数为空'];
        }
        $first = json_decode($data['first'],true);
        $checked = json_decode($data['checked'],true);
        foreach ($first as $k=>$v){
            CourseLiveResource::where('id',$v)->update(['shift_id'=>$checked[$k],'update_at'=>date('Y-m-d H:i:s')]);
        }
        $user_id = AdminLog::getAdminInfo()->admin_user->id;
        //添加日志操作
        AdminLog::insertAdminLog([
            'admin_id'       =>   $user_id  ,
            'module_name'    =>  'liveToCourseshift' ,
            'route_url'      =>  'admin/Course/liveToCourseshift' ,
            'operate_method' =>  'update' ,
            'content'        =>  '排课操作'.json_encode($data) ,
            'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
            'create_at'      =>  date('Y-m-d H:i:s')
        ]);
        return ['code' => 200 , 'msg' => '修改成功'];
    }
}

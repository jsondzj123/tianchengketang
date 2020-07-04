<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AdminLog;
use App\Models\Couresteacher;
use App\Models\Coures;
use App\Models\Order;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class Teacher extends Model {
    //指定别的表名
    public $table      = 'ld_lecturer_educationa';
    //时间戳设置
    public $timestamps = false;

    protected $fillable = [
        'teacher_name',
        'teacher_introduce',
        'teacher_header_pic',
        'subject_id'
    ];

    protected $hidden = [
        'pivot'
    ];

    protected $appends = ['checked' , 'student_number' , 'star_num'];

    public function getCheckedAttribute($value)
    {
        return true;
    }

    public function lessons() {
        return $this->belongsToMany('App\Models\Teacher', 'ld_course_teacher');
    }

    //获取学员数量
    public function getStudentNumberAttribute($value) {
        //获取课程的id列表
        $lesson_list     = Couresteacher::where('teacher_id' , $this->id)->get();
        if($lesson_list && !empty($lesson_list)){
            //获取课程id列表
            $lesson_ids = array_column($lesson_list->toArray() , 'course_id');
            //通过课程id获取对应的购买基数
            $buy_num    = Coures::whereIn('id' , $lesson_ids)->sum('buy_num');

            //查询订单所属的学员购买记录数量
            $order_count= Order::whereIn('class_id' , $lesson_ids)->where('status' , 2)->count();

            //获取学员总数量
            $student_number  = (int)bcadd($buy_num , $order_count);
        } else {
            $student_number  = 0;
        }
        return $student_number;
    }

    //好评数量
    public function getStarNumAttribute($value) {
        return 5;
    }

    /*
     * @param  description   添加教师/教务方法
     * @param  data          数组数据
     * @param  author        dzj
     * @param  ctime         2020-04-25
     * return  int
     */
    public static function insertTeacher($data) {
        return self::insertGetId($data);
    }

    /*
     * @param  descriptsion    根据讲师或教务id获取详细信息
     * @param  参数说明         body包含以下参数[
     *     teacher_id   讲师或教务id
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-25
     * return  array
     */
    public static function getTeacherInfoById($body=[]) {
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断讲师或教务id是否合法
        if(!isset($body['teacher_id']) || empty($body['teacher_id']) || $body['teacher_id'] <= 0){
            return ['code' => 202 , 'msg' => '老师id不合法'];
        }

        //key赋值
        $key = 'teacher:teacherinfo:'.$body['teacher_id'];

        //判断此老师是否被请求过一次(防止重复请求,且数据信息不存在)
        if(Redis::get($key)){
            return ['code' => 204 , 'msg' => '此讲师教务不存在'];
        } else {
            //判断此讲师教务在讲师教务表中是否存在
            $teacher_count = self::where('id',$body['teacher_id'])->count();
            if($teacher_count <= 0){
                //存储讲师教务的id值并且保存60s
                Redis::setex($key , 60 , $body['teacher_id']);
                return ['code' => 204 , 'msg' => '此讲师教务不存在'];
            }
        }

        //根据id获取讲师或教务详细信息
        $teacher_info = self::where('id',$body['teacher_id'])->select('head_icon','school_id','phone','real_name','sex','qq','wechat','parent_id','child_id','describe','content')->first()->toArray();
        //判断学科是否存在二级
        if($teacher_info['child_id'] && $teacher_info['child_id'] > 0){
            $teacher_info['parent_id'] = [$teacher_info['parent_id'] , $teacher_info['child_id']];
        } else {
            $teacher_info['parent_id'] = [$teacher_info['parent_id']];
        }
        return ['code' => 200 , 'msg' => '获取老师信息成功' , 'data' => $teacher_info];
    }

    /*
     * @param  descriptsion    根据讲师或教务id获取详细信息
     * @param  参数说明         body包含以下参数[
     *     teacher_id   讲师或教务id
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-25
     * return  array
     */
    public static function getTeacherList($body=[]) {
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断讲师或教务类型是否合法
        if(!isset($body['type']) || empty($body['type']) || $body['type'] <= 0 || !in_array($body['type'] , [1,2])){
            return ['code' => 202 , 'msg' => '老师类型不合法'];
        }

        //每页显示的条数
        $pagesize = isset($body['pagesize']) && $body['pagesize'] > 0 ? $body['pagesize'] : 15;
        $page     = isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $offset   = ($page - 1) * $pagesize;

        //获取讲师或教务是否有数据
        $teacher_count = self::where(function($query) use ($body){
            $query->where('is_del' , '=' , 0);
            //获取老师类型(讲师还是教务)
            $query->where('type' , '=' , $body['type']);

            //判断搜索内容是否为空
            if(isset($body['search']) && !empty($body['search'])){
                $query->where('real_name','like','%'.$body['search'].'%');
            }
        })->count();

        //判断讲师或教务是否有数据
        if($teacher_count && $teacher_count > 0){
            //获取讲师或教务列表
            $teacher_list = self::where(function($query) use ($body){
                $query->where('is_del' , '=' , 0);
                //获取老师类型(讲师还是教务)
                $query->where('type' , '=' , $body['type']);

                //判断搜索内容是否为空
                if(isset($body['search']) && !empty($body['search'])){
                    $query->where('real_name','like','%'.$body['search'].'%');
                }
            })->select('id as teacher_id','real_name','phone','create_at','number','is_recommend','is_forbid')->orderByDesc('id')->offset($offset)->limit($pagesize)->get()->toArray();
            //判断如果是讲师则查询开课数量
            if($body['type'] == 2){
                foreach($teacher_list as $k=>$v){
                    $teacher_list[$k]['number'] = Couresteacher::where('teacher_id' , $v['teacher_id'])->count();
                }
            }
            return ['code' => 200 , 'msg' => '获取老师列表成功' , 'data' => ['teacher_list' => $teacher_list , 'total' => $teacher_count , 'pagesize' => $pagesize , 'page' => $page]];
        } else {
            return ['code' => 200 , 'msg' => '获取老师列表成功' , 'data' => ['teacher_list' => [] , 'total' => 0 , 'pagesize' => $pagesize , 'page' => $page]];
        }
    }

    /*
     * @param  description   讲师或教务搜索列表
     * @param  参数说明       body包含以下参数[
     *     parent_id     学科分类id
     *     real_name     老师姓名
     * ]
     * @param author    dzj
     * @param ctime     2020-04-29
     */
    public static function getTeacherSearchList($body=[]) {
        //获取讲师或教务列表
        $teacher_list = self::where(function($query) use ($body){
            $query->where('is_del' , '=' , 0);
            //判断学科分类是否选择
            if(isset($body['parent_id']) && !empty($body['parent_id'])){
                $parent_id = json_decode($body['parent_id'] , true);
                if($parent_id && !empty($parent_id)){
                    $query->where('parent_id','=',$parent_id[0]);
                    //判断二级分类的id是否为空
                    if(isset($parent_id[1]) && $parent_id[1] > 0){
                        $query->where('child_id','=',$parent_id[1]);
                    }
                }
            }

            //判断姓名是否为空
            if(isset($body['real_name']) && !empty($body['real_name'])){
                $query->where('real_name','like','%'.$body['real_name'].'%');
            }
        })->select('id as teacher_id','real_name','type')->orderByDesc('create_at')->get()->toArray();

        //判断获取列表是否为空
        if($teacher_list && !empty($teacher_list)){
            $arr = [];
            foreach($teacher_list as $k => $v){
                //教务
                if($v['type'] == 1){
                    $arr['jiaowu'][] = [
                        'teacher_id' =>  $v['teacher_id'] ,
                        'real_name'  =>  $v['real_name']
                    ];
                } else {
                    $arr['jiangshi'][] = [
                        'teacher_id' =>  $v['teacher_id'] ,
                        'real_name'  =>  $v['real_name']
                    ];
                }
            }
            $teacher_list = $arr;
        }
        return ['code' => 200 , 'msg' => '获取老师列表成功' , 'data' => $teacher_list];
    }

    /*
     * @param  descriptsion    更改讲师教务的方法
     * @param  参数说明         body包含以下参数[
     *     teacher_id   讲师或教务id
     *     head_icon    头像
     *     phone        手机号
     *     real_name    讲师姓名/教务姓名
     *     sex          性别
     *     qq           QQ号码
     *     wechat       微信号
     *     parent_id    学科一级分类id
     *     child_id     学科二级分类id
     *     describe     讲师描述/教务描述
     *     content      讲师详情
     *     type         老师类型(1代表教务,2代表讲师)
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-25
     * return  array
     */
    public static function doUpdateTeacher($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断讲师或教务id是否合法
        if(!isset($body['teacher_id']) || empty($body['teacher_id']) || $body['teacher_id'] <= 0){
            return ['code' => 202 , 'msg' => '老师id不合法'];
        }

        //判断头像是否上传
        /*if(!isset($body['head_icon']) || empty($body['head_icon'])){
            return ['code' => 201 , 'msg' => '请上传头像'];
        }*/

        //判断手机号是否为空
        if(!isset($body['phone']) || empty($body['phone'])){
            return ['code' => 201 , 'msg' => '请输入手机号'];
        } else if(!preg_match('#^13[\d]{9}$|^14[\d]{9}$|^15[\d]{9}$|^17[\d]{9}$|^18[\d]{9}|^16[\d]{9}$#', $body['phone'])) {
            return ['code' => 202 , 'msg' => '手机号不合法'];
        }

        //判断姓名是否为空
        if(!isset($body['real_name']) || empty($body['real_name'])){
            return ['code' => 201 , 'msg' => '请输入姓名'];
        }

        //判断性别是否选择
        /*if(!isset($body['sex']) || empty($body['sex'])){
            return ['code' => 201 , 'msg' => '请选择性别'];
        } else if(!in_array($body['sex'] , [1,2])) {
            return ['code' => 202 , 'msg' => '性别不合法'];
        }*/

        //判断描述是否为空
        /*if(!isset($body['describe']) || empty($body['describe'])){
            return ['code' => 201 , 'msg' => '请输入描述'];
        }*/

        //如果是讲师
        $teacher_info = self::find($body['teacher_id']);
        if($teacher_info['type'] > 1){
            //判断学科是否选择
            if(!isset($body['parent_id']) || empty($body['parent_id']) || empty(json_decode($body['parent_id'] , true))){
                return ['code' => 201 , 'msg' => '请选择关联学科'];
            }

            //判断详情是否为空
            /*if(!isset($body['content']) || empty($body['content'])){
                return ['code' => 201 , 'msg' => '请输入详情'];
            }*/

            //转化学科类型
            $parent_info = json_decode($body['parent_id'] , true);
        } else {
            $parent_info = "";
        }

        //获取老师id
        $teacher_id = $body['teacher_id'];

        //key赋值
        $key = 'teacher:update:'.$teacher_id;

        //判断此老师是否被请求过一次(防止重复请求,且数据信息不存在)
        if(Redis::get($key)){
            return ['code' => 204 , 'msg' => '此讲师教务不存在'];
        } else {
            //判断此讲师教务在讲师教务表中是否存在
            $teacher_count = self::where('id',$teacher_id)->count();
            if($teacher_count <= 0){
                //存储讲师教务的id值并且保存60s
                Redis::setex($key , 60 , $teacher_id);
                return ['code' => 204 , 'msg' => '此讲师教务不存在'];
            }
        }

        //判断学科类型
        if($teacher_info['type'] > 1){
            $parent_id = $parent_info && !empty($parent_info) && isset($parent_info[0]) ? $parent_info[0] : 0;
            $child_id  = $parent_info && !empty($parent_info) && isset($parent_info[1]) ? $parent_info[1] : 0;
        } else {
            $parent_id = 0;
            $child_id  = 0;
        }

        //讲师或教务数组信息追加
        $teacher_array = [
            'head_icon'  =>    isset($body['head_icon']) && !empty($body['head_icon']) ? $body['head_icon'] : '' ,
            'phone'      =>    isset($body['phone']) && !empty($body['phone']) ? $body['phone'] : '',
            'real_name'  =>    isset($body['real_name']) && !empty($body['real_name']) ? $body['real_name'] : '' ,
            'qq'         =>    isset($body['qq']) && !empty($body['qq']) ? $body['qq'] : '' ,
            'wechat'     =>    isset($body['wechat']) && !empty($body['wechat']) ? $body['wechat'] : '' ,
            'sex'        =>    isset($body['sex']) && !empty($body['sex']) ? $body['sex'] : 0,
            'describe'   =>    isset($body['describe']) && !empty($body['describe']) ? $body['describe'] : '',
            'parent_id'  =>    $parent_id ,
            'child_id'   =>    $child_id ,
            'content'    =>    $teacher_info['type'] > 1 ? isset($body['content']) && !empty($body['content']) ? $body['content'] : '' : '' ,
            'update_at'  =>    date('Y-m-d H:i:s')
        ];

        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;

        //开启事务
        DB::beginTransaction();

        //根据讲师或教务id更新信息
        if(false !== self::where('id',$teacher_id)->update($teacher_array)){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $admin_id  ,
                'module_name'    =>  'Teacher' ,
                'route_url'      =>  'admin/teacher/doUpdateTeacher' ,
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($body) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            //事务提交
            DB::commit();
            return ['code' => 200 , 'msg' => '更新成功'];
        } else {
            //事务回滚
            DB::rollBack();
            return ['code' => 203 , 'msg' => '更新失败'];
        }
    }


    /*
     * @param  description   增加讲师教务的方法
     * @param  参数说明       body包含以下参数[
     *     head_icon    头像
     *     phone        手机号
     *     real_name    讲师姓名/教务姓名
     *     sex          性别(1男,2女)
     *     qq           QQ号码
     *     wechat       微信号
     *     parent_id    学科一级分类id
     *     child_id     学科二级分类id
     *     describe     讲师描述/教务描述
     *     content      讲师详情
     *     type         老师类型(1代表教务,2代表讲师)
     * ]
     * @param author    dzj
     * @param ctime     2020-04-25
     * return string
     */
    public static function doInsertTeacher($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断是教师还是教务
        if(!isset($body['type']) || empty($body['type']) || !in_array($body['type'] , [1,2])){
            return ['code' => 202 , 'msg' => '老师类型不合法'];
        } else {
            //判断头像是否上传
            /*if(!isset($body['head_icon']) || empty($body['head_icon'])){
                return ['code' => 201 , 'msg' => '请上传头像'];
            }*/

            //判断手机号是否为空
            if(!isset($body['phone']) || empty($body['phone'])){
                return ['code' => 201 , 'msg' => '请输入手机号'];
            } else if(!preg_match('#^13[\d]{9}$|^14[\d]{9}$|^15[\d]{9}$|^17[\d]{9}$|^18[\d]{9}|^16[\d]{9}$#', $body['phone'])) {
                return ['code' => 202 , 'msg' => '手机号不合法'];
            }

            //判断姓名是否为空
            if(!isset($body['real_name']) || empty($body['real_name'])){
                return ['code' => 201 , 'msg' => '请输入姓名'];
            }

            //判断性别是否选择
            /*if(!isset($body['sex']) || empty($body['sex'])){
                return ['code' => 201 , 'msg' => '请选择性别'];
            } else if(!in_array($body['sex'] , [1,2])) {
                return ['code' => 202 , 'msg' => '性别不合法'];
            }*/

            //判断描述是否为空
            /*if(!isset($body['describe']) || empty($body['describe'])){
                return ['code' => 201 , 'msg' => '请输入描述'];
            }*/

            //如果是讲师
            if($body['type'] > 1){
                //判断学科是否选择
                if(!isset($body['parent_id']) || empty($body['parent_id']) || empty(json_decode($body['parent_id'] , true))){
                    return ['code' => 201 , 'msg' => '请选择关联学科'];
                }

                //判断详情是否为空
                /*if(!isset($body['content']) || empty($body['content'])){
                    return ['code' => 201 , 'msg' => '请输入详情'];
                }*/

                //转化学科类型
                $parent_info = json_decode($body['parent_id'] , true);
            } else {
                $parent_info = "";
            }
        }

        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        $school_id= isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;

        //判断学科类型
        if($body['type'] > 1){
            $parent_id = $parent_info && !empty($parent_info) && isset($parent_info[0]) ? $parent_info[0] : 0;
            $child_id  = $parent_info && !empty($parent_info) && isset($parent_info[1]) ? $parent_info[1] : 0;
        } else {
            $parent_id = 0;
            $child_id  = 0;
        }

        //讲师或教务数组信息追加
        $teacher_array = [
            'type'       =>    $body['type'] ,
            'head_icon'  =>    isset($body['head_icon']) && !empty($body['head_icon']) ? $body['head_icon'] : '' ,
            'phone'      =>    isset($body['phone']) && !empty($body['phone']) ? $body['phone'] : '',
            'real_name'  =>    isset($body['real_name']) && !empty($body['real_name']) ? $body['real_name'] : '' ,
            'qq'         =>    isset($body['qq']) && !empty($body['qq']) ? $body['qq'] : '' ,
            'wechat'     =>    isset($body['wechat']) && !empty($body['wechat']) ? $body['wechat'] : '' ,
            'sex'        =>    isset($body['sex']) && !empty($body['sex']) ? $body['sex'] : 0,
            'describe'   =>    isset($body['describe']) && !empty($body['describe']) ? $body['describe'] : '',
            'parent_id'  =>    $parent_id ,
            'child_id'   =>    $child_id ,
            'content'    =>    $body['type'] > 1 ? isset($body['content']) && !empty($body['content']) ? $body['content'] : '' : '' ,
            'admin_id'   =>    $admin_id ,
            'school_id'  =>    $school_id ,
            'create_at'  =>    date('Y-m-d H:i:s')
        ];

        //开启事务
        DB::beginTransaction();

        //将数据插入到表中
        if(false !== self::insertTeacher($teacher_array)){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $admin_id  ,
                'module_name'    =>  'Teacher' ,
                'route_url'      =>  'admin/teacher/doInsertTeacher' ,
                'operate_method' =>  'insert' ,
                'content'        =>  json_encode($body) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            //事务提交
            DB::commit();
            return ['code' => 200 , 'msg' => '添加成功'];
        } else {
            //事务回滚
            DB::rollBack();
            return ['code' => 203 , 'msg' => '添加失败'];
        }
    }

    /*
     * @param  descriptsion    删除老师的方法
     * @param  参数说明         body包含以下参数[
     *      teacher_id   讲师或教务id
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-25
     * return  array
     */
    public static function doDeleteTeacher($body=[]) {

        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断讲师或教务id是否合法
        if(!isset($body['teacher_id']) || empty($body['teacher_id']) || $body['teacher_id'] <= 0){
            return ['code' => 202 , 'msg' => '老师id不合法'];
        }

        //key赋值
        $key = 'teacher:delete:'.$body['teacher_id'];

        //判断此老师是否被请求过一次(防止重复请求,且数据信息不存在)
        if(Redis::get($key)){
            return ['code' => 204 , 'msg' => '此讲师教务不存在'];
        } else {
            //判断此讲师教务在讲师教务表中是否存在
            $teacher_count = self::where('id',$body['teacher_id'])->count();
            if($teacher_count <= 0){
                //存储讲师教务的id值并且保存60s
                Redis::setex($key , 60 , $body['teacher_id']);
                return ['code' => 204 , 'msg' => '此讲师教务不存在'];
            }
        }

        //追加更新时间
        $data = [
            'is_del'     => 1 ,
            'update_at'  => date('Y-m-d H:i:s')
        ];

        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;

        //开启事务
        DB::beginTransaction();

        //根据讲师或教务id更新删除状态
        if(false !== self::where('id',$body['teacher_id'])->update($data)){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $admin_id  ,
                'module_name'    =>  'Teacher' ,
                'route_url'      =>  'admin/teacher/doDeleteTeacher' ,
                'operate_method' =>  'delete' ,
                'content'        =>  json_encode($body) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            //事务提交
            DB::commit();
            return ['code' => 200 , 'msg' => '删除成功'];
        } else {
            //事务回滚
            DB::rollBack();
            return ['code' => 203 , 'msg' => '删除失败'];
        }
    }

    /*
     * @param  descriptsion    推荐老师的方法
     * @param  参数说明         body包含以下参数[
     *     is_recommend   是否推荐(1代表推荐,2代表不推荐)
     *     teacher_id     讲师或教务id
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-25
     * return  array
     */
    public static function doRecommendTeacher($body=[]) {

        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断讲师或教务id是否合法
        if(!isset($body['teacher_id']) || empty($body['teacher_id']) || $body['teacher_id'] <= 0){
            return ['code' => 202 , 'msg' => '老师id不合法'];
        }

        //key赋值
        $key = 'teacher:recommend:'.$body['teacher_id'];

        //判断此老师是否被请求过一次(防止重复请求,且数据信息不存在)
        if(Redis::get($key)){
            return ['code' => 204 , 'msg' => '此讲师教务不存在'];
        } else {
            //判断此讲师教务在讲师教务表中是否存在
            $teacher_count = self::where('id',$body['teacher_id'])->count();
            if($teacher_count <= 0){
                //存储讲师教务的id值并且保存60s
                Redis::setex($key , 60 , $body['teacher_id']);
                return ['code' => 204 , 'msg' => '此讲师教务不存在'];
            }
        }

        //根据学员的id获取学员的状态
        $is_recommend = self::where('id',$body['teacher_id'])->pluck('is_recommend');

        //追加更新时间
        $data = [
            'is_recommend' => $is_recommend[0] > 0 ? 0 : 1 ,
            'update_at'    => date('Y-m-d H:i:s')
        ];

        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;

        //开启事务
        DB::beginTransaction();

        //根据讲师或教务id更新推荐状态
        if(false !== self::where('id',$body['teacher_id'])->update($data)){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $admin_id  ,
                'module_name'    =>  'Teacher' ,
                'route_url'      =>  'admin/teacher/doRecommendTeacher' ,
                'operate_method' =>  'recommend' ,
                'content'        =>  json_encode($body) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            //事务提交
            DB::commit();
            return ['code' => 200 , 'msg' => '操作成功'];
        } else {
            //事务回滚
            DB::rollBack();
            return ['code' => 203 , 'msg' => '操作失败'];
        }
    }

    /*
     * @param  descriptsion    启用/禁用的方法
     * @param  参数说明         body包含以下参数[
     *     teacher_id     讲师或教务id
     * ]
     * @param  author          dzj
     * @param  ctime           2020-06-17
     * return  array
     */
    public static function doForbidTeacher($body=[]) {
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断讲师或教务id是否合法
        if(!isset($body['teacher_id']) || empty($body['teacher_id']) || $body['teacher_id'] <= 0){
            return ['code' => 202 , 'msg' => '讲师或教务id不合法'];
        }

        //key赋值
        $key = 'teacher:forbid:'.$body['teacher_id'];

        //判断此学员是否被请求过一次(防止重复请求,且数据信息不存在)
        if(Redis::get($key)){
            return ['code' => 204 , 'msg' => '此讲师或教务不存在'];
        } else {
            //判断此讲师或教务在老师表中是否存在
            $teacher_count = self::where('id',$body['teacher_id'])->count();
            if($teacher_count <= 0){
                //存储讲师或教务的id值并且保存60s
                Redis::setex($key , 60 , $body['teacher_id']);
                return ['code' => 204 , 'msg' => '此讲师或教务不存在'];
            }
        }

        //根据讲师或教务的id获取讲师或教务的状态
        $is_forbid = self::where('id',$body['teacher_id'])->pluck('is_forbid');

        //追加更新时间
        $data = [
            'is_forbid'    => $is_forbid[0] >= 1 ? 0 : 1 ,
            'update_at'    => date('Y-m-d H:i:s')
        ];

        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;

        //开启事务
        DB::beginTransaction();

        //根据讲师或教务id更新老师状态
        if(false !== self::where('id',$body['teacher_id'])->update($data)){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $admin_id  ,
                'module_name'    =>  'Teacher' ,
                'route_url'      =>  'admin/teacher/doForbidTeacher' ,
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($body) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            //事务提交
            DB::commit();
            return ['code' => 200 , 'msg' => '操作成功'];
        } else {
            //事务回滚
            DB::rollBack();
            return ['code' => 203 , 'msg' => '操作失败'];
        }
    }
}

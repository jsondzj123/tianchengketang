<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AdminLog;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class Student extends Model {
    //指定别的表名
    public $table      = 'ld_student';
    //时间戳设置
    public $timestamps = false;

    public function collectionLessons() {
        return $this->belongsToMany('App\Models\Lesson', 'ld_collections')->withTimestamps();
    }
    
    /*
     * @param  description   添加学员方法
     * @param  data          数组数据
     * @param  author        dzj
     * @param  ctime         2020-04-27
     * return  int
     */
    public static function insertStudent($data) {
        return self::insertGetId($data);
    }

    /*
     * @param  descriptsion    根据学员id获取详细信息
     * @param  参数说明         body包含以下参数[
     *     student_id   学员id
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-27
     * return  array
     */
    public static function getStudentInfoById($body=[]) {
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断学员id是否合法
        if(!isset($body['student_id']) || empty($body['student_id']) || $body['student_id'] <= 0){
            return ['code' => 202 , 'msg' => '学员id不合法'];
        }
        
        //key赋值
        $key = 'student:studentinfo:'.$body['student_id'];

        //判断此学员是否被请求过一次(防止重复请求,且数据信息不存在)
        if(Redis::get($key)){
            return ['code' => 204 , 'msg' => '此学员不存在'];
        } else {
            //判断此学员在学员表中是否存在
            $student_count = self::where('id',$body['student_id'])->count();
            if($student_count <= 0){
                //存储学员的id值并且保存60s
                Redis::setex($key , 60 , $body['student_id']);
                return ['code' => 204 , 'msg' => '此学员不存在'];
            }
        }

        //根据id获取学员详细信息
        $student_info = self::where('id',$body['student_id'])->select('school_id','phone','real_name','sex','papers_type','papers_num','birthday','address_locus','age','educational','family_phone','office_phone','contact_people','contact_phone','email','qq','wechat','address','remark','head_icon','balance','reg_source','login_at')->first()->toArray();
        //判断头像是否为空
        if(empty($student_info['head_icon'])){
            $student_info['head_icon']  = 'https://longdeapi.oss-cn-beijing.aliyuncs.com/upload/2020-07-01/159359854490285efc6250a7852.png';
        }
        //证件类型
        $papers_type_array = [1=>'身份证',2=>'护照',3=>'港澳通行证',4=>'台胞证',5=>'军官证',6=>'士官证',7=>'其他'];
        //学历数组
        $educational_array = [1=>'小学',2=>'初中',3=>'高中',4=>'大专',5=>'大本',6=>'研究生',7=>'博士生',8=>'博士后及以上'];
        //注册来源
        $reg_source_array  = [0=>'官网注册',1=>'手机端',2=>'线下录入'];
        //备注
        $student_info['remark']  = $student_info['remark'] && !empty($student_info['remark']) ? $student_info['remark'] : '';
        $student_info['educational_name']  = $student_info['educational'] && $student_info['educational'] > 0 ? $educational_array[$student_info['educational']] : '';
        $student_info['papers_type_name']  = $student_info['papers_type'] && $student_info['papers_type'] > 0 ? $papers_type_array[$student_info['papers_type']] : '';
        $student_info['reg_source']   = isset($reg_source_array[$student_info['reg_source']]) && !empty($reg_source_array[$student_info['reg_source']]) ? $reg_source_array[$student_info['reg_source']] : '';
        
        //通过分校的id获取分校的名称
        if($student_info['school_id'] && $student_info['school_id'] > 0){
            $student_info['school_name']  = \App\Models\School::where('id',$student_info['school_id'])->value('name');
        } else {
            $student_info['school_name']  = '';
        }
        //余额
        $student_info['balance']  = $student_info['balance'] > 0 ? $student_info['balance'] : 0;
        //最后登录时间
        $student_info['login_at']  = $student_info['login_at'] && !empty($student_info['login_at']) ? $student_info['login_at'] : '';
        return ['code' => 200 , 'msg' => '获取学员信息成功' , 'data' => $student_info];
    }

    /*
     * @param  descriptsion    获取学员列表
     * @param  参数说明         body包含以下参数[
     *     student_id   学员id
     *     is_forbid    账号状态
     *     state_status 开课状态
     *     real_name    姓名
     *     pagesize     每页显示条数
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-27
     * return  array
     */
    public static function getStudentList($body=[]) {
        //每页显示的条数
        $pagesize = isset($body['pagesize']) && $body['pagesize'] > 0 ? $body['pagesize'] : 15;
        $page     = isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        
        //获取学员的总数量
        $student_count = self::where(function($query) use ($body){
            //判断报名状态是否选择
            if(isset($body['enroll_status']) && strlen($body['enroll_status']) > 0 && in_array($body['enroll_status'] , [1,2])){
                //已报名
                if($body['enroll_status'] > 0 && $body['enroll_status'] == 1){
                    $query->where('enroll_status' , '=' , 1);
                } else if($body['enroll_status'] > 0 && $body['enroll_status'] == 2){
                    $query->where('enroll_status' , '=' , 0);
                }
            }

            //判断开课状态是否选择
            if(isset($body['state_status']) && strlen($body['state_status']) > 0){
                $state_status = $body['state_status'] > 0 ? $body['state_status'] : 0;
                $query->where('state_status' , '=' , $state_status);
            }

            //判断账号状态是否选择
            if(isset($body['is_forbid']) && !empty($body['is_forbid']) && in_array($body['is_forbid'] , [1,2])){
                $query->where('is_forbid' , '=' , $body['is_forbid']);
            }

            //判断搜索内容是否为空
            if(isset($body['search']) && !empty($body['search'])){
                $query->where('real_name','like','%'.$body['search'].'%')->orWhere('phone','like','%'.$body['search'].'%');
            }
        })->count();
        
        //判断学员数量是否为空
        if($student_count > 0){
            //学员列表
            $student_list = self::where(function($query) use ($body){
                //判断学科id是否选择
                /*if(isset($body['subject_id']) && !empty($body['subject_id']) && $body['subject_id'] > 0){
                    $query->where('subject_id' , '=' , $body['subject_id']);
                }*/
                //判断报名状态是否选择
                if(isset($body['enroll_status']) && strlen($body['enroll_status']) > 0 && in_array($body['enroll_status'] , [1,2])){
                    //已报名
                    if($body['enroll_status'] > 0 && $body['enroll_status'] == 1){
                        $query->where('enroll_status' , '=' , 1);
                    } else if($body['enroll_status'] > 0 && $body['enroll_status'] == 2){
                        $query->where('enroll_status' , '=' , 0);
                    }
                }
                
                //判断开课状态是否选择
                if(isset($body['state_status']) && strlen($body['state_status']) > 0){
                    $state_status = $body['state_status'] > 0 ? $body['state_status'] : 0;
                    $query->where('state_status' , '=' , $state_status);
                }

                //判断账号状态是否选择
                if(isset($body['is_forbid']) && !empty($body['is_forbid']) && in_array($body['is_forbid'] , [1,2])){
                    $query->where('is_forbid' , '=' , $body['is_forbid']);
                }
                
                //判断搜索内容是否为空
                if(isset($body['search']) && !empty($body['search'])){
                    $query->where('real_name','like','%'.$body['search'].'%')->orWhere('phone','like','%'.$body['search'].'%');
                }
            })->select('id as student_id','real_name','phone','create_at','enroll_status','state_status','is_forbid','papers_type','papers_num')->orderByDesc('create_at')->offset($offset)->limit($pagesize)->get();
            return ['code' => 200 , 'msg' => '获取学员列表成功' , 'data' => ['student_list' => $student_list , 'total' => $student_count , 'pagesize' => $pagesize , 'page' => $page]];
        }
        return ['code' => 200 , 'msg' => '获取学员列表成功' , 'data' => ['student_list' => [] , 'total' => 0 , 'pagesize' => $pagesize , 'page' => $page]];
    }

    /*
     * @param  description   修改学员的方法
     * @param  参数说明       body包含以下参数[
     *     student_id   学员id
     *     phone        手机号
     *     real_name    学员姓名
     *     sex          性别(1男,2女)
     *     papers_type  证件类型(1代表身份证,2代表护照,3代表港澳通行证,4代表台胞证,5代表军官证,6代表士官证,7代表其他)
     *     papers_num   证件号码
     *     birthday     出生日期
     *     address_locus户口所在地
     *     age          年龄
     *     educational  学历(1代表小学,2代表初中,3代表高中,4代表大专,5代表大本,6代表研究生,7代表博士生,8代表博士后及以上)
     *     family_phone 家庭电话号
     *     office_phone 办公电话
     *     contact_people  紧急联系人
     *     contact_phone   紧急联系电话
     *     email           邮箱
     *     qq              QQ号码
     *     wechat          微信
     *     address         地址
     *     remark          备注
     * ]
     * @param author    dzj
     * @param ctime     2020-04-27
     * return string
     */
    public static function doUpdateStudent($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }
        
        //判断学员id是否合法
        if(!isset($body['student_id']) || empty($body['student_id']) || $body['student_id'] <= 0){
            return ['code' => 202 , 'msg' => '学员id不合法'];
        }

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
        if(isset($body['sex']) && !empty($body['sex']) && !in_array($body['sex'] , [1,2])){
            return ['code' => 202 , 'msg' => '性别不合法'];
        }

        //判断证件类型是否合法
        if(isset($body['papers_type']) && !empty($body['papers_type']) && !in_array($body['papers_type'] , [1,2,3,4,5,6,7])){
            return ['code' => 202 , 'msg' => '证件类型不合法'];
        }
        
        //判断年龄是否为空
        if(isset($body['age']) && !empty($body['age']) && $body['age'] < 0){
            return ['code' => 201 , 'msg' => '请输入年龄'];
        }
        
        //判断最高学历是否合法
        if(isset($body['educational']) && !empty($body['educational']) && !in_array($body['educational'] , [1,2,3,4,5,6,7,8])){
            return ['code' => 202 , 'msg' => '最高学历类型不合法'];
        }
        
        //获取学员id
        $student_id = $body['student_id'];
        
        //key赋值
        $key = 'student:update:'.$student_id;

        //判断此学员是否被请求过一次(防止重复请求,且数据信息不存在)
        if(Redis::get($key)){
            return ['code' => 204 , 'msg' => '此学员不存在'];
        } else {
            //判断此学员在学员表中是否存在
            $student_count = self::where('id',$student_id)->count();
            if($student_count <= 0){
                //存储学员的id值并且保存60s
                Redis::setex($key , 60 , $student_id);
                return ['code' => 204 , 'msg' => '此学员不存在'];
            }
        }
        
        //组装学员数组信息
        $student_array = [
            'phone'         =>   $body['phone'] ,
            'real_name'     =>   $body['real_name'] ,
            'sex'           =>   isset($body['sex']) && $body['sex'] == 1 ? 1 : 2 ,
            'papers_type'   =>   isset($body['papers_type']) && in_array($body['papers_type'] , [1,2,3,4,5,6,7]) ? $body['papers_type'] : 0 ,
            'papers_num'    =>   isset($body['papers_num']) && !empty($body['papers_num']) ? $body['papers_num'] : '' ,
            'birthday'      =>   isset($body['birthday']) && !empty($body['birthday']) ? $body['birthday'] : '' ,
            'address_locus' =>   isset($body['address_locus']) && !empty($body['address_locus']) ? $body['address_locus'] : '' ,
            'age'           =>   isset($body['age']) && $body['age'] > 0 ? $body['age'] : 0 ,
            'educational'   =>   isset($body['educational']) && in_array($body['educational'] , [1,2,3,4,5,6,7,8]) ? $body['educational'] : 0 ,
            'family_phone'  =>   isset($body['family_phone']) && !empty($body['family_phone']) ? $body['family_phone'] : '' ,
            'office_phone'  =>   isset($body['office_phone']) && !empty($body['office_phone']) ? $body['office_phone'] : '' ,
            'contact_people'=>   isset($body['contact_people']) && !empty($body['contact_people']) ? $body['contact_people'] : '' ,
            'contact_phone' =>   isset($body['contact_phone']) && !empty($body['contact_phone']) ? $body['contact_phone'] : '' ,
            'email'         =>   isset($body['email']) && !empty($body['email']) ? $body['email'] : '' ,
            'qq'            =>   isset($body['qq']) && !empty($body['qq']) ? $body['qq'] : '' ,
            'wechat'        =>   isset($body['wechat']) && !empty($body['wechat']) ? $body['wechat'] : '' ,
            'address'       =>   isset($body['address']) && !empty($body['address']) ? $body['address'] : '' ,
            'remark'        =>   isset($body['remark']) && !empty($body['remark']) ? $body['remark'] : '' ,
            'update_at'     =>   date('Y-m-d H:i:s')
        ];
        
        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        
        //开启事务
        DB::beginTransaction();
        
        //根据学员id获取学员信息
        $student_info = self::find($student_id);
        if($student_info['phone'] != $body['phone']){
            //根据手机号判断是否注册
            $is_mobile_exists = self::where("phone" , $body['phone'])->count();
            if($is_mobile_exists > 0){
                return ['code' => 205 , 'msg' => '此手机号已存在'];
            }
        }

        //根据学员id更新信息
        if(false !== self::where('id',$student_id)->update($student_array)){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $admin_id  ,
                'module_name'    =>  'Student' ,
                'route_url'      =>  'admin/student/doUpdateStudent' , 
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
     * @param  description   添加学员的方法
     * @param  参数说明       body包含以下参数[
     *     phone        手机号
     *     real_name    学员姓名
     *     sex          性别(1男,2女)
     *     papers_type  证件类型(1代表身份证,2代表护照,3代表港澳通行证,4代表台胞证,5代表军官证,6代表士官证,7代表其他)
     *     papers_num   证件号码
     *     birthday     出生日期
     *     address_locus户口所在地
     *     age          年龄
     *     educational  学历(1代表小学,2代表初中,3代表高中,4代表大专,5代表大本,6代表研究生,7代表博士生,8代表博士后及以上)
     *     family_phone 家庭电话号
     *     office_phone 办公电话
     *     contact_people  紧急联系人
     *     contact_phone   紧急联系电话
     *     email           邮箱
     *     qq              QQ号码
     *     wechat          微信
     *     address         地址
     *     remark          备注
     * ]
     * @param author    dzj
     * @param ctime     2020-04-27
     * return string
     */
    public static function doInsertStudent($body=[]){
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

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
        if(isset($body['sex']) && !empty($body['sex']) && !in_array($body['sex'] , [1,2])){
            return ['code' => 202 , 'msg' => '性别不合法'];
        }

        //判断证件类型是否合法
        if(isset($body['papers_type']) && !empty($body['papers_type']) && !in_array($body['papers_type'] , [1,2,3,4,5,6,7])){
            return ['code' => 202 , 'msg' => '证件类型不合法'];
        }
        
        //判断年龄是否为空
        if(isset($body['age']) && !empty($body['age']) && $body['age'] < 0){
            return ['code' => 201 , 'msg' => '请输入年龄'];
        }
        
        //判断最高学历是否合法
        if(isset($body['educational']) && !empty($body['educational']) && !in_array($body['educational'] , [1,2,3,4,5,6,7,8])){
            return ['code' => 202 , 'msg' => '最高学历类型不合法'];
        }
        
        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        $school_id= isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
        
        //组装学员数组信息
        $student_array = [
            'phone'         =>   $body['phone'] ,
            'password'      =>   password_hash('123456' , PASSWORD_DEFAULT) ,
            'real_name'     =>   $body['real_name'] ,
            'sex'           =>   isset($body['sex']) && $body['sex'] == 1 ? 1 : 2 ,
            'papers_type'   =>   isset($body['papers_type']) && in_array($body['papers_type'] , [1,2,3,4,5,6,7]) ? $body['papers_type'] : 0 ,
            'papers_num'    =>   isset($body['papers_num']) && !empty($body['papers_num']) ? $body['papers_num'] : '' ,
            'birthday'      =>   isset($body['birthday']) && !empty($body['birthday']) ? $body['birthday'] : '' ,
            'address_locus' =>   isset($body['address_locus']) && !empty($body['address_locus']) ? $body['address_locus'] : '' ,
            'age'           =>   isset($body['age']) && $body['age'] > 0 ? $body['age'] : 0 ,
            'educational'   =>   isset($body['educational']) && in_array($body['educational'] , [1,2,3,4,5,6,7,8]) ? $body['educational'] : 0 ,
            'family_phone'  =>   isset($body['family_phone']) && !empty($body['family_phone']) ? $body['family_phone'] : '' ,
            'office_phone'  =>   isset($body['office_phone']) && !empty($body['office_phone']) ? $body['office_phone'] : '' ,
            'contact_people'=>   isset($body['contact_people']) && !empty($body['contact_people']) ? $body['contact_people'] : '' ,
            'contact_phone' =>   isset($body['contact_phone']) && !empty($body['contact_phone']) ? $body['contact_phone'] : '' ,
            'email'         =>   isset($body['email']) && !empty($body['email']) ? $body['email'] : '' ,
            'qq'            =>   isset($body['qq']) && !empty($body['qq']) ? $body['qq'] : '' ,
            'wechat'        =>   isset($body['wechat']) && !empty($body['wechat']) ? $body['wechat'] : '' ,
            'address'       =>   isset($body['address']) && !empty($body['address']) ? $body['address'] : '' ,
            'remark'        =>   isset($body['remark']) && !empty($body['remark']) ? $body['remark'] : '' ,
            'admin_id'      =>   $admin_id ,
            'school_id'     =>   $school_id ,
            'reg_source'    =>   2 ,
            'create_at'     =>   date('Y-m-d H:i:s')
        ];
        
        //开启事务
        DB::beginTransaction();
        
        //根据手机号判断是否注册
        $is_mobile_exists = self::where("phone" , $body['phone'])->count();
        if($is_mobile_exists > 0){
            return ['code' => 205 , 'msg' => '此手机号已存在'];
        }

        //将数据插入到表中
        if(false !== self::insertStudent($student_array)){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $admin_id  ,
                'module_name'    =>  'Student' ,
                'route_url'      =>  'admin/student/doInsertStudent' , 
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
     * @param  descriptsion    启用/禁用的方法
     * @param  参数说明         body包含以下参数[
     *     is_forbid      是否启用(1代表启用,2代表禁用)
     *     student_id     学员id
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-28
     * return  array
     */
    public static function doForbidStudent($body=[]) {
        //判断传过来的数组数据是否为空
        if(!$body || !is_array($body)){
            return ['code' => 202 , 'msg' => '传递数据不合法'];
        }

        //判断学员id是否合法
        if(!isset($body['student_id']) || empty($body['student_id']) || $body['student_id'] <= 0){
            return ['code' => 202 , 'msg' => '学员id不合法'];
        }

        //key赋值
        $key = 'student:forbid:'.$body['student_id'];

        //判断此学员是否被请求过一次(防止重复请求,且数据信息不存在)
        if(Redis::get($key)){
            return ['code' => 204 , 'msg' => '此学员不存在'];
        } else {
            //判断此学员在学员表中是否存在
            $student_count = self::where('id',$body['student_id'])->count();
            if($student_count <= 0){
                //存储学员的id值并且保存60s
                Redis::setex($key , 60 , $body['student_id']);
                return ['code' => 204 , 'msg' => '此学员不存在'];
            }
        }
        
        //根据学员的id获取学员的状态
        $is_forbid = self::where('id',$body['student_id'])->pluck('is_forbid');

        //追加更新时间
        $data = [
            'is_forbid'    => $is_forbid[0] > 1 ? 1 : 2 ,
            'update_at'    => date('Y-m-d H:i:s')
        ];
        
        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        
        //开启事务
        DB::beginTransaction();

        //根据学员id更新账号状态
        if(false !== self::where('id',$body['student_id'])->update($data)){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $admin_id  ,
                'module_name'    =>  'Student' ,
                'route_url'      =>  'admin/student/doForbidStudent' , 
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

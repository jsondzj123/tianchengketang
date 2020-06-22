<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

use App\Models\Teacher;
use App\Models\Admin;
use App\Tools\CurrentAdmin;
class School extends Model {
    //指定别的表名
    public $table = 'ld_school';
    //时间戳设置
    public $timestamps = false;

    public function lessons() {
        return $this->belongsToMany('App\Models\Lesson', 'ld_lesson_schools', 'school_id');
    }

    public function admins() {
        return $this->hasMany('App\Models\Admin');
    }
    //错误信息
     public static function message()
    {
        return [
            'page.required'  => json_encode(['code'=>'201','msg'=>'页码不能为空']),
            'page.integer'   => json_encode(['code'=>'202','msg'=>'页码类型不合法']),
            'limit.required' => json_encode(['code'=>'201','msg'=>'显示条数不能为空']),
            'limit.integer'  => json_encode(['code'=>'202','msg'=>'显示条数类型不合法']),
            'type.required' => json_encode(['code'=>'201','msg'=>'分类类型不能为空']),
            'type.integer'  => json_encode(['code'=>'202','msg'=>'分类类型不合法']),
            'school_id.required' => json_encode(['code'=>'201','msg'=>'学校标识不能为空']),
            'school_id.integer'  => json_encode(['code'=>'202','msg'=>'学校标识类型不合法']),
            'name.required' => json_encode(['code'=>'201','msg'=>'学校名称不能为空']),
            'name.unique' => json_encode(['code'=>'205','msg'=>'学校名称已存在']),
            'dns.required' => json_encode(['code'=>'201','msg'=>'学校域名不能为空']),
            'logo_url.required' => json_encode(['code'=>'201','msg'=>'学校LOGO不能为空']),
            'introduce.required' => json_encode(['code'=>'201','msg'=>'学校简介不能为空']),
            'username.required' => json_encode(['code'=>'201','msg'=>'账号不能为空']),
            'username.unique' => json_encode(['code'=>'205','msg'=>'账号已存在']),
            'password.required' => json_encode(['code'=>'201','msg'=>'密码不能为空']),
            'pwd.required' => json_encode(['code'=>'201','msg'=>'确认密码不能为空']),
            'mobile.required' => json_encode(['code'=>'201','msg'=>'联系方式不能为空']),
            'mobile.regex' => json_encode(['code'=>'202','msg'=>'联系方式类型不合法']),
            'id.required' => json_encode(['code'=>'201','msg'=>'学校标识不能为空']),
            'id.integer'  => json_encode(['code'=>'202','msg'=>'学校标识类型不合法']),
            'user_id.required' => json_encode(['code'=>'201','msg'=>'用户标识不能为空']),
            'user_id.integer'  => json_encode(['code'=>'202','msg'=>'用户标识类型不合法']),
            'realname.required'=> json_encode(['code'=>'201','msg'=>'联系人不能为空']),
            'role_id.required' => json_encode(['code'=>'201','msg'=>'角色标识不能为空']),
            'role_id.integer'  => json_encode(['code'=>'202','msg'=>'角色标识类型不合法']),  

        ];


    }


    /*
         * @param  descriptsion 获取学校信息
         * @param  $school_id   学校id
         * @param  $field   字段列
         * @param  $page   页码
         * @param  $limit  显示条件
         * @param  author       lys
         * @param  ctime   2020/4/29 
         * return  array
         */
    public static function getSchoolOne($where,$field = ['*']){
        $schoolInfo = self::where($where)->select($field)->first();
        if($schoolInfo){
            return ['code'=>200,'msg'=>'获取学校信息成功','data'=>$schoolInfo];
        }else{
            return ['code'=>204,'msg'=>'学校信息不存在'];
        }
    }
        /*
         * @param  descriptsion 获取学校信息
         * @param  $field   字段列
         * @param  author       lys
         * @param  ctime   2020/4/30
         * return  array
         */
    public static  function getSchoolAlls($field = ['*']){
        return  self::select($field)->get()->toArray();

    }

    /*
         * @param  分校列表
         * @param  author  苏振文
         * @param  ctime   2020/4/28 14:43
         * return  array
         */
    public static function SchoolAll($where=[],$field=['*']){
        $list = self::select($field)->where($where)->get()->toArray();
        return $list;
    }
    /*
         * @param  修改分校超级管理员信息
         * @param  author  lys
         * @param  ctime   2020/5/7
         * return  array
         */
    public static function doAdminUpdate($data){
        if(!$data || !is_array($data)){
             return ['code'=>202,'msg'=>'传参不合法'];
        }
        $update = [];
        if(isset($data['password']) && isset($data['pwd'])){
            if(!empty($data['password'])|| !empty($data['pwd']) ){
               if($data['password'] != $data['pwd'] ){
                    return ['code'=>206,'msg'=>'两个密码不一致'];
                }else{
                    $update['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
            }
        }
        if(isset($data['realname']) && !empty($data['realname'])){
             $update['realname'] =  $data['realname'];
        }
        if(isset($data['mobile']) && !empty($data['mobile'])){
             $update['mobile'] =  $data['mobile'];
        }
        $result = Admin::where('id',$data['user_id'])->update($update);
        if($result){
            AdminLog::insertAdminLog([
                'admin_id'       =>   CurrentAdmin::user()['id'] ,
                'module_name'    =>  'School' ,
                'route_url'      =>  'admin/school/doAdminUpdate' , 
                'operate_method' =>  'update',
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]); 
            return ['code'=>200,'msg'=>'更新成功'];
        }else{
            return ['code'=>203,'msg'=>'更新失败'];
        }
    }
    
    /*
     * @param  获取分校讲师列表
     * @param  author  lys
     * @param  ctime   2020/5/7
     * return  array
     */
    public static function getSchoolTeacherList($data){
            $school= School::find($data['school_id']);  //获取学校信息 
            $teacher = Teacher::where(['school_id'=>$data['school_id'],'is_del' =>0,'type'=>2])->select('id','head_icon','real_name','describe','school_id')->get()->toArray();//学校自己添加的讲师
            $teacher_id   =  Admin::select('*')
                            ->RightJoin('ld_role_auth','ld_role_auth.id','=','ld_admin.role_id')
                            ->select('ld_admin.teacher_id')
                            ->where(function($query) use($data){
                                if($data['school_id'] != ''){
                                    $query->where ('ld_admin.school_id',$data['school_id']);
                                }
                                    $query->where ('ld_role_auth.is_super',1);
                                    $query->where('ld_role_auth.is_del',1);  
                            })
                            ->first(); //获取总校信息讲师
            if(!empty($teacher_id)){
                $teacheIdArr   = explode(',', $teacher_id);
                $zong_teacher = Teacher::whereIn('id',$teacheIdArr)->where('type',2)->select('id','head_icon','real_name','describe','school_id')->get()->toArray();
                $teacher = array_merge($teacher,$zong_teacher);
            }
            foreach($teacher as $key => &$v){
                if($v['school_id'] != $data['school_id']){
                    $teacher[$key]['school_status'] = '总校讲师';
                }else{
                    $teacher[$key]['school_status'] = '分校讲师';
                }
            }   
            $arr = [
                    'code'=>200,
                    'msg'=>'Success',
                    'data'=>[
                            'school'=>$school,
                            'teacher'=>$teacher
                        ]
            ];
            return $arr;
    }  
}



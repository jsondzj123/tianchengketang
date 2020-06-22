<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Models\Admin as Adminuser;
use App\Models\Roleauth;
use App\Models\Authrules;
use App\Models\School;
use Illuminate\Support\Facades\Redis;
use App\Tools\CurrentAdmin;
use Illuminate\Support\Facades\Validator;
use App\Models\AdminLog;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller {

     /*
     * @param  description   获取用户列表
     * @param  参数说明       body包含以下参数[
     *     search       搜索条件 （非必填项）
     *     page         当前页码 （不是必填项）
     *     limit        每页显示条件 （不是必填项）
     *     school_id    学校id  （非必填项）
     * ]
     * @param author    lys
     * @param ctime     2020-04-29
     */
    public function getAdminUserList(){

        $result = Adminuser::getAdminUserList(self::$accept_data);
        if($result['code'] == 200){
            return response()->json($result);
        }else{
            return response()->json($result);
        }
    }

    /*
     * @param  description  更改用户状态（启用、禁用）
     * @param  参数说明       body包含以下参数[
     *     id           用户id
     * ]
     * @param author    lys
     * @param ctime     2020-04-29
     */

    public function upUserForbidStatus(){
        $data =  self::$accept_data;
        $where = [];
        $updateArr = [];
        if( !isset($data['id']) || empty($data['id']) || is_int($data['id']) ){
            return response()->json(['code'=>201,'msg'=>'账号id为空或缺少或类型不合法']);
        }
        $userInfo = Adminuser::getUserOne(['id'=>$data['id']]);
        if($userInfo['code'] !=200){
            return response()->json(['code'=>$userInfo['code'],'msg'=>$userInfo['msg']]); 
        }   
        if($userInfo['data']['is_forbid'] == 1)  $updateArr['is_forbid'] = 0;  else  $updateArr['is_forbid'] = 1; 
        $result = Adminuser::upUserStatus(['id'=>$data['id']],$updateArr);
        if($result){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   CurrentAdmin::user()['id'] ,
                'module_name'    =>  'Adminuser' ,
                'route_url'      =>  'admin/adminuser/upUserForbidStatus' , 
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return response()->json(['code'=>200,'msg'=>'Success']);    
        }else{
            return response()->json(['code'=>204,'msg'=>'网络超时，请重试']);    
        }

    }
    /*
     * @param  description  更改用户状态（删除）
     * @param  参数说明       body包含以下参数[
     *     id           用户id
     * ]
     * @param author    lys
     * @param ctime     2020-04-29
     */
    public function upUserDelStatus(){
        $data =  self::$accept_data;
        $where = [];
        $updateArr = [];
        if( !isset($data['id']) || empty($data['id']) || is_int($data['id']) ){
            return response()->json(['code'=>201,'msg'=>'账号id为空或缺少或类型不合法']);
        }
        $userInfo = Adminuser::findOrFail($data['id']);
        $userInfo->is_del = 0;
        if($userInfo->save()){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   CurrentAdmin::user()['id'] ,
                'module_name'    =>  'Adminuser' ,
                'route_url'      =>  'admin/adminuser/upUserDelStatus' , 
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return response()->json(['code'=>200,'msg'=>'Success']);    
        }else{
            return response()->json(['code'=>204,'msg'=>'网络超时，请重试']);    
        }
    }
    /*
     * @param  description   获取角色列表
     * @param  参数说明       body包含以下参数[
     *     search       搜索条件 （非必填项）
     *     page         当前页码 （不是必填项）
     *     limit        每页显示条件 （不是必填项）
     *  
     * ]
     * @param author    lys
     * @param ctime     2020-04-29
     */
    public function getAuthList(){
         $result =  Adminuser::getAuthList(self::$accept_data);
         return response()->json($result);
    }
    /*
     * @param  description   添加后台账号
     * @param  参数说明       body包含以下参数[
     *     school_id       所属学校id
     *     username         账号
     *     realname        姓名
     *     mobile          手机号
     *     sex             性别
     *     password        密码
     *     pwd             确认密码
     *     role_id         角色id
     *     teacher_id      关联讲师id串
     * ]
     * @param author    lys
     * @param ctime     2020-04-29   5.12修改账号唯一性验证
     */

    public function doInsertAdminUser(){
        $data = self::$accept_data;
        $validator = Validator::make($data,
                [
                    'school_id' => 'required|integer',
                    'username' => 'required',
                    'realname' => 'required',
                    'mobile' => 'required|regex:/^1[3456789][0-9]{9}$/',
                    'sex' => 'required|integer',
                    'password' => 'required',
                    'pwd' => 'required',
                    'role_id' => 'required|integer',
                ],
                Adminuser::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $data['teacher_id'] = !isset($data['teacher_id']) || empty($data['teacher_id'])? 0: $data['teacher_id'];
        if($data['password'] != $data['pwd']){
            return response()->json(['code'=>206,'msg'=>'登录密码不一致']);
        }  
        $count  = Adminuser::where('username',$data['username'])->where('school_id',$data['school_id'])->where('is_del',1)->count();
        if($count>0){
            return response()->json(['code'=>205,'msg'=>'用户名已存在']);
        }
        unset($data['pwd']);
        if(isset($data['/admin/adminuser/doInsertAdminUser'])){
            unset($data['/admin/adminuser/doInsertAdminUser']);
        }
        $data['school_status']=CurrentAdmin::user()['school_status'] == 1 ?1:0;
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['admin_id'] = CurrentAdmin::user()['id'];
        $result = Adminuser::insertAdminUser($data);
        if($result>0){

            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   CurrentAdmin::user()['id'] ,
                'module_name'    =>  'Adminuser' ,
                'route_url'      =>  'admin/adminuser/doInsertAdminUser' , 
                'operate_method' =>  'insert' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return   response()->json(['code'=>200,'msg'=>'添加成功']);
        }else{
            return  response()->json(['code'=>203,'msg'=>'网络超时，请重试']);
        }
    }
    /*
     * @param  description   获取账号信息（编辑）
     * @param  参数说明       body包含以下参数[
     *      id => 账号id
     * ]
     * @param author    lys
     * @param ctime     2020-05-04
     */

    public function getAdminUserUpdate(){
        $data = self::$accept_data;
        if( !isset($data['id']) || empty($data['id']) ){
            return response()->json(['code'=>201,'msg'=>'用户表示缺少或为空']);
        }
        $adminUserArr = Adminuser::getUserOne(['id'=>$data['id']]);
        if($adminUserArr['code'] != 200){
            return response()->json(['code'=>204,'msg'=>'用户不存在']);
        }
        $adminUserArr['data']['school_name']  = School::getSchoolOne(['id'=>$adminUserArr['data']['school_id'],'is_forbid'=>1,'is_del'=>1],['name'])['data']['name'];
        $roleAuthArr = Roleauth::getRoleAuthAlls(['school_id'=>$adminUserArr['data']['school_id'],'is_del'=>1],['id','role_name']);
        $teacherArr = [];
        $adminUserArr['data']['teacher_name'] = '';
        if(!empty($adminUserArr['data']['teacher_id'])){
            // $teacher_id_arr = explode(',', $adminUserArr['data']['teacher_id']);
             // $teacherArr= Teacher::whereIn('id',$teacher_id_arr)->where('is_del','!=',1)->where('is_forbid','!=',1)->select('id as teacher_id','real_name','type')->get();
            $adminUserArr['data']['teacher_name'] = Teacher::where('id',$adminUserArr['data']['teacher_id'])->where('is_del','!=',1)->where('is_forbid','!=',1)->select('real_name')->get();
        }
        $arr = [
            'admin_user'=> $adminUserArr['data'],
            // 'teacher' =>   $teacherArr,  //讲师组
            'role_auth' => $roleAuthArr, //权限组
        ];
        return response()->json(['code'=>200,'msg'=>'获取信息成功','data'=>$arr]);

    }
    /*
     * @param  description   账号信息（编辑）
     * @param  参数说明       body包含以下参数[
     *      id => 账号id 
            school_id => 学校id  
            username => 账号名称
            realname => 真实姓名
            mobile => 联系方式
            sex => 性别
            password => 登录密码 
            pwd => 确认密码
            role_id => 角色id
            teacher_id => 老师id组
     * ]
     * @param author    lys
     * @param ctime     2020-05-04
     */

    public function doAdminUserUpdate(){
        $data = self::$accept_data;
        $validator = Validator::make($data,
                [
                'id' => 'required|integer',
                'school_id' => 'required|integer',
                'username' => 'required',
                'realname' => 'required',
                'mobile' => 'required|regex:/^1[3456789][0-9]{9}$/',
                'sex' => 'required|integer',
                'password' => 'required',
                'pwd' => 'required',
                'role_id' => 'required|integer',
                ],
                Adminuser::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $data['teacher_id']= !isset($data['teacher_id']) || empty($data['teacher_id']) ?0 :$data['teacher_id'];
     
        if($data['password'] != $data['pwd']){
            return response()->json(['code'=>206,'msg'=>'登录密码不一致']);
        }
        if(isset($data['/admin/adminuser/doAdminUserUpdate'])){
            unset($data['/admin/adminuser/doAdminUserUpdate']);
        }
        $where['school_id'] = $data['school_id'];
        $where['username']   = $data['username'];
        $where['is_del'] = 1;
        $count = Adminuser::where($where)->where('id','!=',$data['id'])->count();
        if($count >=1 ){
             return response()->json(['code'=>205,'msg'=>'用户名已存在']);
        }  
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['pwd']);
        $admin_id  = CurrentAdmin::user()['id'];
      
            DB::beginTransaction();
            // if(Adminuser::where(['school_id'=>$data['school_id'],'is_del'=>1])->count() == 1){  //判断该账号是不是分校超管 5.14
            //     if(Roleauth::where(['school_id'=>$data['school_id'],'is_del'=>1])->count() <1){
            //         $roleAuthArr = Roleauth::where(['id'=>$data['role_id']])->select('auth_id')->first()->toArray();
            //         $roleAuthArr['role_name'] = '超级管理员';
            //         $roleAuthArr['auth_desc'] = '拥有所有权限';
            //         $roleAuthArr['is_super'] = 1;
            //         $roleAuthArr['school_id'] = $data['school_id'];
            //         $roleAuthArr['admin_id']  = $admin_id;
            //         $role_id = Roleauth::insertGetId($roleAuthArr);
            //         if($role_id<=0){
            //              return   response()->json(['code'=>500,'msg'=>'角色创建失败']);
            //         }
            //         $data['role_id'] = $role_id;
            //     }
            // }
            
            $result = Adminuser::where('id','=',$data['id'])->update($data);
            if($result){
             //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id ,
                    'module_name'    =>  'Adminuser' ,
                    'route_url'      =>  'admin/adminuser/doAdminUserUpdate' , 
                    'operate_method' =>  'update' ,
                    'content'        =>  json_encode($data),
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                DB::commit();
                return   response()->json(['code'=>200,'msg'=>'更改成功']);
            }else{
                 DB::rollBack();
                return   response()->json(['code'=>203,'msg'=>'网络超时，请重试']);    
            }
            
            
        
    }  
    /*
     * @param  description   登录账号权限（菜单栏）
     * @param  参数说明       body包含以下参数[
     *      id => 角色id
     * ]
     * @param author    lys
     * @param ctime     2020-05-05
     */

    public function getAdminUserLoginAuth($admin_role_id){
        if(empty($admin_role_id) || !intval($admin_role_id)){
            return ['code'=>201,'msg'=>'参数值为空或参数类型错误'];
        }
        $adminRole =  Roleauth::getRoleOne(['id'=>$admin_role_id,'is_del'=>1],['id','role_name','auth_id']);
        if($adminRole['code'] != 200){
            return ['code'=>$adminRole['code'],'msg'=>$adminRole['msg']];
        }
        $adminRuths = Authrules::getAdminAuthAll($adminRole['data']['auth_id']);
        if($adminRuths['code'] != 200){
            return ['code'=>$adminRuths['code'],'msg'=>$adminRuths['msg']];
        }
        return ['code'=>200,'msg'=>'success','data'=>$adminRuths['data']];
    }
}

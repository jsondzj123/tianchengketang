<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Models\Admin as AdminUser;
use App\Models\Roleauth;
use App\Models\Authrules;
use Illuminate\Support\Facades\Redis;
use Validator;
use App\Tools\CurrentAdmin;
use App\Models\AdminLog;
use Illuminate\Support\Facades\DB;
class RoleController extends Controller {
  
    /*
     * @param  getUserList   获取角色列表
     * @param  search  搜索条件
     * @param  page    当前页
     * @param  limit   显示条数
     * @param  return  array  
     * @param  author    lys
     * @param  ctime     2020-04-28 13:27
     */
    public function getAuthList(Request $request){
        $data =  $request->post();
        $where= [];
        if( !isset($data['search']) || !isset($data['page']) || !isset($data['limit']) ){
            return response()->json(['code'=>202,'msg'=>'缺少参数']);
        }else{
              $where['search'] = $data['search'];
        }
        if(  empty($data['page']) || $data['page']<=1 ){
            $data['page'] =1;
        }
        if(  empty($data['limit']) || $data['page']<1 ){
            $data['limit'] = 10;
        }
        $where['school_id'] = 1;
        $authArr = Roleauth::getRoleAuthAll($where,$data['page'],$data['limit']);
        $arr = [
            'data' => $authArr,
            'page' => $data['page'],
            'limit' => $data['limit'],
        ];
        return response()->json(['code'=>200,'msg'=>'Success','data'=>$arr]);    
    }
     /*
     * @param  upRoleStatus  角色删除
     * @param  id      角色id
     * @param  return  array  状态信息
     * @param  author    lys
     * @param  ctime     2020-04-28 13:27
     */
    public function doRoleDel(){
        $data = self::$accept_data;
        if( !isset($data['id']) || empty($data['id'])  || $data['id']<=0 ){
            return response()->json(['code'=>201,'msg'=>'角色标识为空或缺少或类型不合法']);
        }
        if(AdminUser::where(['role_id'=>$data['id'],'is_del'=>1])->count()  >0){  //  角色使用中无法删除    5.14  
            return response()->json(['code'=>205,'msg'=>'角色使用中,不能删除']);
        }
        $role = Roleauth::findOrfail($data['id']);
        $role->is_del = 0;
        if($role->save()){
            AdminLog::insertAdminLog([
                'admin_id'       =>   CurrentAdmin::user()['id'] ,
                'module_name'    =>  'Role' ,
                'route_url'      =>  'admin/role/upRoleStatus' , 
                'operate_method' =>  'update' ,
                'content'        =>  json_encode(['id'=>$data['id']]),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return response()->json(['code'=>200,'msg'=>'更改成功']);
        }else{
            return response()->json(['code'=>203,'msg'=>'更改失败']);
        }
    }   
    /*
     * @param  upRoleStatus   添加角色
     * @param  $data=[
                'r_name'=> 角色名称
                'auth_id'=> 权限串
                'auth_desc'=> 角色描述
                'admin_id'=> 添加人
                'school_id'=> 所属学校id  
        ]                 添加数组
     * @param  author    lys
     * @param  ctime     2020-04-30
     */
    //注：隐含问题 是不是超级管理员权限
    public function doRoleInsert(){
        $data = self::$accept_data;
        if(!isset($data['role_name']) || empty($data['role_name'])){
           return response()->json(['code'=>201,'msg'=>'角色名称为空或缺少']);
        }
        if(!isset($data['auth_id']) || empty($data['auth_id'])){
          return response()->json(['code'=>201,'msg'=>'权限组id为空或缺少']);
        }
        if(!isset($data['auth_desc']) || empty($data['auth_desc'])){
            return response()->json(['code'=>201,'msg'=>'权限描述为空或缺少']);
        }
        $data['admin_id'] = CurrentAdmin::user()['id'];
        $data['school_id'] = CurrentAdmin::user()['school_id'];
        $role = Roleauth::where(['role_name'=>$data['role_name'],'school_id'=>$data['school_id'],'is_del'=>1])->first();
        if($role){
             return response()->json(['code'=>205,'msg'=>'角色已存在']);
        }
        $role = Roleauth::where(['school_id'=> $data['school_id'],'is_super'=>'1'])->first();
        $data['create_time'] = date('Y-m-d H:i:s');
        if($role){  $data['is_super'] = 0; }
        else{       $data['is_super'] = 1; }
        if(Roleauth::create($data)){
             AdminLog::insertAdminLog([
                'admin_id'       =>   CurrentAdmin::user()['id'] ,
                'module_name'    =>  'Role' ,
                'route_url'      =>  'admin/role/doRoleInsert' , 
                'operate_method' =>  'insert' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return response()->json(['code'=>200,'msg'=>'添加成功']);
        }else{
            return response()->json(['code'=>201,'msg'=>'添加失败']);
        }
        
    } 
    /*
     * @param  descriptsion   获取角色信息（编辑）
     * @param  $data=[
                'id'=> 角色id
        ]                 查询条件
     * @param  author    lys
     * @param  ctime     2020-04-30
     */
    public function getRoleAuthUpdate(){
        $data = self::$accept_data;
        $where = [];
        $updateArr = [];
        if( !isset($data['id']) ||  empty($data['id'])){
            return response()->json(['code'=>201,'msg'=>'参数为空或缺少参数']);
        }
        $roleAuthData = Roleauth::getRoleOne(['id'=>$data['id'],'is_del'=>1],['id','role_name','auth_desc','auth_id','school_id']);

        if($roleAuthData['code'] != 200){
            return response()->json(['code'=>$roleAuthData['code'],'msg'=>$roleAuthData['msg']]); 
        }
        $roleAuthArr = Roleauth::getRoleAuthAlls(['school_id'=>$roleAuthData['data']['school_id'],'is_del'=>1],['id','role_name','auth_desc','auth_id']); 
        $data['school_status'] = CurrentAdmin::user()['school_status'];

        if($data['school_status'] == 1){
            // echo '总校';
            //总校
             $authArr = \App\Models\Authrules::getAuthAlls([],['id','name','title','parent_id']);
        }else{
             // echo '分校';
            //分校
                $schoolData = \App\Models\Roleauth::getRoleOne(['school_id'=>$roleAuthData['data']['school_id'],'is_del'=>1,'is_super'=>1],['id','role_name','auth_desc','auth_id']);
                if( $schoolData['code'] != 200){    
                     return response()->json(['code' => 403 , 'msg' => '请联系总校超级管理员' ]);
                }
                $auth_id_arr = explode(',',$schoolData['data']['auth_id']);
                if(!$auth_id_arr){
                     $auth_id_arr = [$auth_id];
                }
                $authArr = \App\Models\Authrules::getAuthAlls(['id'=>$auth_id_arr],['id','name','title','parent_id']);
        }   
        $authArr  = getAuthArr($authArr);
        $arr = [
            'code'=>200,
            'msg'=>'获取角色成功',
            'data'=>[
                    'id' => $data['id'], //角色id
                    'role_auth_arr'=>$roleAuthArr,
                    'role_auth_data' =>$roleAuthData['data'],
                    'auth' =>$authArr
                ]
        ]; 
        return  response()->json($arr);
    }   
    /*
     * @param  descriptsion   编辑角色信息（编辑）
     * @param  $data=[
                'id'=> 角色id
                'role_name'=> 角色名称
                'auth_desc'=> 权限描述
                'auth_id'=> 权限id组

        ]           
     * @param  author    lys
     * @param  ctime     2020-04-30
     */
    public function doRoleAuthUpdate(){
        $data = self::$accept_data;
        $where = [];
        $updateArr = [];
        if( !isset($data['id']) ||  empty($data['id'])){
            return response()->json(['code'=>201,'msg'=>'角色id为空或缺少']);
        }
        if( !isset($data['role_name']) ||  empty($data['role_name'])){
            return response()->json(['code'=>201,'msg'=>'角色名称为空或缺少']);
        }
        if( !isset($data['auth_desc']) ||  empty($data['auth_desc'])){
            return response()->json(['code'=>201,'msg'=>'角色权限描述为空或缺少']);
        }
        if( !isset($data['auth_id']) ||  empty($data['auth_id'])){
            return response()->json(['code'=>201,'msg'=>'权限组id为空或缺少']);
        }
        if(isset($data['/admin/role/doRoleAuthUpdate'])){
            unset($data['/admin/role/doRoleAuthUpdate']);
        }
        $admin = CurrentAdmin::user();
        $school_id = $admin['school_id'];
        $admin_id = $admin['id'];
        $count = Roleauth::where('role_name','=',$data['role_name'])->where('id','!=',$data['id'])->where('school_id','=',$school_id)->count();
        if($count>=1){
            return response()->json(['code'=>205,'msg'=>'角色名称已存在']); 
        }
        $auths_id = Authrules::where(['is_del'=>1,'is_show'=>1,'is_forbid'=>1])->pluck('id')->toarray();
        $auth_id = explode(',', $data['auth_id']);
        foreach ($auth_id as $v) {
            if(in_array($v,$auths_id)){
                $arr[]= $v;
            }
        }
        try {  //5.15  
            DB::beginTransaction();
            $data['update_time'] = date('Y-m-d H:i:s');
            $data['auth_id'] = implode(',', $arr);
            AdminLog::insertAdminLog([
                'admin_id'       =>   CurrentAdmin::user()['id'] ,
                'module_name'    =>  'Role' ,
                'route_url'      =>  'admin/role/doRoleAuthUpdate' , 
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            if(Roleauth::where('id','=',$data['id'])->update($data)){
                  DB::commit();
                return response()->json(['code'=>200,'msg'=>'更改成功']); 
            }else{
                 DB::rollBack();
                return response()->json(['code'=>203,'msg'=>'更改成功']); 
            }
          
        } catch (Exception $e) {
           
            return response()->json(['code'=>500,'msg'=>$e->getMessage()]);
        }
    }   




   
    

   
}

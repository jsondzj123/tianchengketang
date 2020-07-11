<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Models\Admin as Adminuser;
use App\Models\Roleauth;
use App\Models\Authrules;
use App\Models\School;
use App\Models\PaySet;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use App\Tools\CurrentAdmin;
use App\Models\AdminLog;
use Illuminate\Support\Facades\DB;
use App\Models\CouresSubject;
class SchoolController extends Controller {
  

    public function details(){
        $data = self::$accept_data;
        $validator = Validator::make($data, 
                ['school_id' => 'required|integer'],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $arr = School::where(['id'=>$data['school_id'],'is_del'=>1])->select('name','logo_url','introduce','dns')->first();
        return response()->json(['code'=>200,'msg'=>'success','data'=>$arr]);
    }
     /*
     * @param  description 获取分校列表  
     * @param  参数说明       body包含以下参数[
     *     school_name       搜索条件
     *     school_dns        分校域名
     *     page         当前页码  
     *     limit        每页显示条数
     * ]
     * @param author    lys
     * @param ctime     2020-05-05
     */
    public function getSchoolList(){
            $data = self::$accept_data;
                
            $pagesize = isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 15;
            $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;

            $offset   = ($page - 1) * $pagesize;
            $where['name'] = empty($data['school_name']) || !isset($data['school_name']) ?'':$data['school_name'];
            $where['dns'] = empty($data['school_dns']) || !isset($data['school_dns']) ?'':$data['school_dns'];
            $school_count = School::where(function($query) use ($where){
                    if($where['name'] != ''){
                        $query->where('name','like','%'.$where['name'].'%');
                    }
                    if($where['dns'] != ''){
                        $query->where('dns','like','%'.$where['dns'].'%');
                    }
                    $query->where('is_del','=',1);
                })->count();
            $sum_page = ceil($school_count/$pagesize);
            if($school_count > 0){
                $schoolArr = School::where(function($query) use ($where){
                    if($where['name'] != ''){
                        $query->where('name','like','%'.$where['name'].'%');
                    }
                    if($where['dns'] != ''){
                        $query->where('dns','like','%'.$where['dns'].'%');
                    }
                    $query->where('is_del','=',1);
                })->select('id','name','logo_url','dns','is_forbid','logo_url')->offset($offset)->limit($pagesize)->get();

                return response()->json(['code'=>200,'msg'=>'Success','data'=>['school_list' => $schoolArr , 'total' => $school_count , 'pagesize' => $pagesize , 'page' => $page,'sum_page'=>$sum_page,'name'=>$where['name'],'dns'=>$where['dns']]]);           
            }
            return response()->json(['code'=>200,'msg'=>'Success','data'=>['school_list' => [] , 'total' => 0 , 'pagesize' => $pagesize , 'page' => $page,'sum_page'=>$sum_page,'name'=>$where['name'],'dns'=>$where['dns']]]);           
    }
    /*
     * @param  description 修改分校状态 (删除)
     * @param  参数说明       body包含以下参数[
     *     school_id      分校id
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function doSchoolDel(){
        $data = self::$accept_data;
        $validator = Validator::make($data, 
                ['school_id' => 'required|integer'],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        try{
            DB::beginTransaction();
            $school = School::find($data['school_id']);
            $school->is_del = 0; 
            if(!$school->save()){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '删除失败,请重试']);
            }
            if(Adminuser::upUserStatus(['school_id'=>$school['id']],['is_del'=>0])){
                AdminLog::insertAdminLog([
                    'admin_id'       =>   CurrentAdmin::user()['id'] ,
                    'module_name'    =>  'School' ,
                    'route_url'      =>  'admin/school/doSchoolForbid' , 
                    'operate_method' =>  'update' ,
                    'content'        =>  json_encode($data),
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '删除成功']);
            } else {
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '删除失败,请重试']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 203 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description 修改分校状态 (启禁)
     * @param  参数说明       body包含以下参数[
     *     school_id      分校id
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function doSchoolForbid(){
        $data = self::$accept_data;
        $validator = Validator::make($data, 
                ['school_id' => 'required|integer'],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        try{
            DB::beginTransaction();
            $school = School::find($data['school_id']);
            if($school['is_forbid'] != 1){
                $school->is_forbid = 1; 
                $is_forbid = 1;

                $pay_status = 1;
                $wx_pay_state = 1;
                $zfb_pay_state = 1;
                $hj_wx_pay_state = 1;
                $hj_zfb_pay_state = 1;
           
            }else{
                $school->is_forbid = 0; 
                $is_forbid = 0;
                $pay_status = -1;
                $wx_pay_state = -1;
                $zfb_pay_state = -1;
                $hj_wx_pay_state = -1;
                $hj_zfb_pay_state = -1;
            }   
            if(!$school->save()){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '更新失败']);
            }
            if(!Adminuser::upUserStatus(['school_id'=>$school['id']],['is_forbid'=>$is_forbid])){
                 DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '更新失败']);
            }
            if(PaySet::where('school_id',$school['id'])->update(['pay_status'=>$pay_status,'wx_pay_state'=>$wx_pay_state,'zfb_pay_state'=>$zfb_pay_state,'hj_wx_pay_state'=>$hj_wx_pay_state,'hj_zfb_pay_state'=>$hj_zfb_pay_state,'update_at'=>date('Y-m-d H:i:s')] ) ){
                AdminLog::insertAdminLog([
                    'admin_id'       =>   CurrentAdmin::user()['id'] ,
                    'module_name'    =>  'School' ,
                    'route_url'      =>  'admin/school/doSchoolDel' , 
                    'operate_method' =>  'update',
                    'content'        =>  json_encode($data),
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '更新成功']);
            } else {
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '更新失败']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }

    }
    /*
     * @param  description 学校添加 
     * @param  参数说明       body包含以下参数[
     *  'name' =>分校名称
        'dns' =>分校域名
        'logo_url' =>分校logo
        'introduce' =>分校简介
        'username' =>登录账号
        'password' =>登录密码
        'pwd' =>确认密码
        'realname' =>联系人(真实姓名)
        'mobile' =>联系方式
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function doInsertSchool(){
        $data = self::$accept_data;
        $validator = Validator::make(
                $data, 
                ['name' => 'required|unique:ld_school',
                 'dns' => 'required',
                 'logo_url'=>'required',
                 'introduce'=>'required',
                 'username'=>'required',
                 'password'=>'required',
                 'pwd' =>'required',
                 'realname'=>'required',
                 'mobile'=>'required|regex:/^1[3456789][0-9]{9}$/',
                ],
                School::message());

        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        if($data['password'] != $data['pwd']){
            return response()->json(['code'=>206,'msg'=>'两次密码不一致']);
        }
        try{
            DB::beginTransaction();
            $school = [
                'name' =>$data['name'],
                'dns'  =>$data['dns'],
                'logo_url'  =>$data['logo_url'],
                'introduce'  =>$data['introduce'],
                'admin_id'  => CurrentAdmin::user()['id'],
                'account_name'=>!isset($data['account_name']) || empty($data['account_name']) ?'':$data['account_name'],
                'account_num'=>!isset($data['account_num']) || empty($data['account_num']) ?'':$data['account_num'],
                'open_bank'=>!isset($data['open_bank']) || empty($data['open_bank']) ?'':$data['open_bank'],
                'create_time'=>date('Y-m-d H:i:s')
            ];
            $school_id = School::insertGetId($school);
            if($school_id <0){
                DB::rollBack();
                return response()->json(['code'=>203,'msg'=>'创建学校未成功']);  
            }
            $admin =[
                'username' =>$data['username'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'realname' =>$data['realname'],
                'mobile' =>  $data['mobile'], 
                'role_id' => 0,  
                'admin_id'  => CurrentAdmin::user()['id'],
                'school_id' =>$school_id,
                'school_status' => 0,
            ];
            $admin_id = Adminuser::insertGetId($admin);
            if($admin_id < 0){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '创建账号未成功!']);
            } 
            $schoolRes = School::where('school_id',$school_id)->update(['super_id'=>$admin_id,'update_time'=>date('Y-m-d H:i:s')]); 
            if(!$schoolRes){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '创建账号未成功!!']);
            }
            $payconfig = [
                'admin_id' => CurrentAdmin::user()['id'],
                'school_id'=> $school_id,
                'create_at'=> date('Y-m-d H:i:s')
            ];
            if(PaySet::insertGetId($payconfig)>0){
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '创建账号成功']);
            }else{
                 DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '创建账号未成功!!!']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
     * @param  description 获取学校信息 
     * @param  参数说明       body包含以下参数[
     *  'school_id' =>学校id
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function getSchoolUpdate(){
        $data = self::$accept_data;
        $validator = Validator::make(
                $data, 
                ['school_id' => 'required|integer'],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $school = School::where('id',$data['school_id'])->select('id','name','dns','logo_url','introduce','account_name','account_num','open_bank')->first();
        return response()->json(['code' => 200 , 'msg' => 'Success','data'=>$school]);
    }
    /*
     * @param  description 修改分校信息 
     * @param  参数说明       body包含以下参数[
     *  'id'=>分校id
        'name' =>分校名称
        'dns' =>分校域名
        'logo_url' =>分校logo
        'introduce' =>分校简介
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */
    public function doSchoolUpdate(){
        $data = self::$accept_data;

        $validator = Validator::make(
                $data, 
                [
                    'id' => 'required|integer',
                    'name' => 'required',
                    'dns' => 'required',
                    'logo_url' => 'required',
                    'introduce' => 'required'
                ],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        if(School::where(['name'=>$data['name'],'is_del'=>1])->where('id','!=',$data['id'])->count()>0){
             return response()->json(['code' => 422 , 'msg' => '学校已存在']);
        }
        if(isset($data['/admin/school/doSchoolUpdate'])){
            unset($data['/admin/school/doSchoolUpdate']);
        }
        $data['account_name']  = !isset($data['account_name']) || empty($data['account_name']) ?'':$data['account_name'];
        $data['account_num']  = !isset($data['account_num']) || empty($data['account_num']) ?'':$data['account_num'];
        $data['open_bank']  = !isset($data['open_bank']) || empty($data['open_bank']) ?'':$data['open_bank'];
        $data['update_time'] = date('Y-m-d H:i:s');
        if(School::where('id',$data['id'])->update($data)){
                AdminLog::insertAdminLog([
                    'admin_id'       =>   CurrentAdmin::user()['id'] ,
                    'module_name'    =>  'School' ,
                    'route_url'      =>  'admin/school/doSchoolUpdate' , 
                    'operate_method' =>  'update',
                    'content'        =>  json_encode($data),
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
            return response()->json(['code' => 200 , 'msg' => '更新成功']);
        }else{
            return response()->json(['code' => 200 , 'msg' => '更新成功']);
        }
    }
    /*
     * @param  description 修改分校信息---权限管理
     * @param  参数说明       body包含以下参数[
     *      'id'=>分校id
     * ]
     * @param author    lys
     * @param ctime     2020-05-06
     */  
    public function getSchoolAdminById(){
        $data = self::$accept_data;
        $validator = Validator::make(
                $data, 
                ['id' => 'required|integer'],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $schoolData = School::select(['name'])->find($data['id']);
        if(!$schoolData){
             return response()->json(['code'=>422,'msg'=>'无学校信息']);
        }
        $roleAuthId = Roleauth::where(['school_id'=>$data['id'],'is_super'=>1])->select('id','auth_id')->first(); //查询学校是否有超管人员角色
        if(is_null($roleAuthId)){
            //无
            $adminUser = Adminuser::where(['school_id'=>$data['id'],'is_del'=>1])->select('id','username','realname','mobile')->first();  
        }else{
            //有
            $adminUser = Adminuser::where(['school_id'=>$data['id'],'role_id'=>$roleAuthId['id'],'is_del'=>1])->select('id','username','realname','mobile')->first();  
        }

        $adminUser['role_id'] = $roleAuthId['id'] > 0 ? $roleAuthId['id']  : 0;
        $adminUser['auth_id'] = $roleAuthId['auth_id'] ? $roleAuthId['auth_id']  : '';
        $adminUser['school_name'] =  !empty($schoolData['name']) ? $schoolData['name']  : '';
        $authRules = Authrules::getAuthAlls([],['id','name','title','parent_id']);
        $authRules = getAuthArr($authRules);
        $arr = [
            'admin' =>$adminUser,
            'auth_rules'=>$authRules,
        ];
        return response()->json(['code'=>200,'msg'=>'success','data'=>$arr]);
    }
    /*
     * @param  description 修改分校信息---权限管理 给分校超管赋权限
     * @param  参数说明       body包含以下参数[
     *      'id'=>分校id
            'role_id'=>角色id,
            'auth_id'=>权限组id 
            'user_id'=>账号id
     * ]
     * @param author    lys
     * @param ctime     2020-05-15
     */  
    public function doSchoolAdminById(){
        $data = self::$accept_data;
        $validator = Validator::make(
                $data, 
                [
                    'id' => 'required|integer',
                    'role_id'=>'required|integer',
                    'user_id'=>'required|integer',
                ],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        if(!isset($data['auth_id'])){
             return response()->json(['code'=>201,'msg'=>'权限组标识缺少']);
        }
        if($data['role_id']>0){
            if(empty($data['auth_id'])){
                return response()->json(['code'=>201,'msg'=>'权限组标识不能为空']);
            }
        }
        $arr = [];
        if(!empty($data['auth_id'])){
            $auths_id = Authrules::where(['is_del'=>1,'is_show'=>1,'is_forbid'=>1])->pluck('id')->toArray();
            $auth_id = explode(',', $data['auth_id']);
            foreach ($auth_id as $v) {
                if(in_array($v,$auths_id)){
                    $arr[]= $v;
                }
            }
        }
        
        $roleAuthArr = Roleauth::where(['school_id'=>$data['id'],'is_super'=>1,'is_del'=>1])->first();
        if(isset($data['admin/school/doSchoolAdminById'])) unset($data['admin/school/doSchoolAdminById']);
        DB::beginTransaction();
        if(is_null($roleAuthArr)){
            //无
            $insert =[
                    'role_name'=>'超级管理员',
                    'auth_desc'=>'拥有所有权限',
                    'auth_id' => empty($arr)?$arr:implode(",",$arr),
                    'school_id'=>$data['id'],
                    'is_super'=>1,
                    'admin_id'=>CurrentAdmin::user()['id'],
                    'create_time' => date('Y-m-d H:i:s')
            ]; 
            $role_id = Roleauth::insertGetId($insert);

             AdminLog::insertAdminLog([
                'admin_id'       =>   CurrentAdmin::user()['id'] ,
                'module_name'    =>  'School' ,
                'route_url'      =>  'admin/school/doSchoolAdminById' , 
                'operate_method' =>  'insert/update' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            if(Adminuser::where('id',$data['user_id'])->update(['role_id'=>$role_id])){
                 DB::commit();
                return response()->json(['code'=>200,'msg'=>'赋权成功']);
            }else{
                 DB::rollBack();
                return response()->json(['code'=>203,'msg'=>'网络错误，请重试']);
            }
        }else{
            //有             
            $super  = Roleauth::where(['id'=>$data['role_id']])->select('is_super')->first();
            if($super['is_super']<1){ //判断是否为超管
                return response()->json(['code'=>404,'msg'=>'非法请求']);
            }
            //如果是超管，那么删除权限，那么其他角色权限也都有被删除，不管是否正在使用中。
            $fen_role_auth_arr = Roleauth::where(['is_del'=>1,'is_super'=>0])->where('school_id',$data['id'])->select('auth_id','id')->get()->toArray();
            foreach ($fen_role_auth_arr as $k => $v) {
                $fen_roles_id = explode(",", $v['auth_id']); 
                $new_arr = array_diff($fen_roles_id,$arr);
                $new_qita_role_ids = array_diff($fen_roles_id,$new_arr);
                if(!empty($new_qita_role_ids)){
                    $res = Roleauth::where(['id'=>$v['id']])->update(['auth_id'=>implode(",", $new_qita_role_ids),'update_time'=>date('Y-m-d H:i:s')]);
                    if(!$res){
                        DB::rollBack();
                        return response()->json(['code'=>203,'msg'=>'赋权成功']);
                    }
                }
                // foreach ($new_arr as $vv) {
                //     if(in_array($vv,$fen_roles_id)){
                //         return response()->json(['code'=>204,'msg'=>'该校其他角色在使用中，不能修改']);
                //     }
                // }
            }
            AdminLog::insertAdminLog([
                'admin_id'       =>   CurrentAdmin::user()['id'] ,
                'module_name'    =>  'School' ,
                'route_url'      =>  'admin/school/doSchoolAdminById' , 
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            if(Roleauth::where('id',$data['role_id'])->update(['auth_id'=>empty($arr)?$arr:implode(",",$arr),'update_time'=>date('Y-m-d H:i:s')])){
                DB::commit();
                return response()->json(['code'=>200,'msg'=>'赋权成功']);
            }else{
                DB::rollBack();
                return response()->json(['code'=>203,'msg'=>'网络错误，请重试']);
            }
        } 
    }
    /*
     * @param  description 修改分校信息---权限管理-账号编辑（获取）
     * @param  参数说明       body包含以下参数[
     *      'user_id'=>用户id
     * ]
     * @param author    lys
     * @param ctime     2020-05-07
     */
    public function getAdminById(){
        $data = self::$accept_data;
        $validator = Validator::make(
                $data, 
                ['user_id' => 'required|integer'],
                School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $admin = Adminuser::where('id',$data['user_id'])->select('id','realname','mobile')->get();
        return response()->json(['code'=>200,'msg'=>'success','data'=>$admin]);
    }
    /*
     * @param  description 修改分校信息---权限管理-账号编辑
     * @param  参数说明       body包含以下参数[
     *      'id'=>用户id
     * ]
     * @param author    lys
     * @param ctime     2020-05-07
     */
    public function doAdminUpdate(){
        
        $data = self::$accept_data;
        $validator = Validator::make(
            $data, 
                [
                'user_id' => 'required|integer',
                'mobile' => 'regex:/^1[3456789][0-9]{9}$/',
                ],School::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $result = School::doAdminUpdate($data);
        return response()->json(['code'=>$result['code'],'msg'=>$result['msg']]);
    }
    /*
     * @param  description 获取分校讲师列表
     * @param  参数说明       body包含以下参数[
     *      'school_id'=>学校id
     * ]
     * @param author    lys
     * @param ctime     2020-05-07
     */
    public function getSchoolTeacherList(){
            $validator = Validator::make(self::$accept_data, 
                ['school_id' => 'required|integer'],
                School::message());
            if ($validator->fails()) {
                return response()->json(json_decode($validator->errors()->first(),1));
            }
            $result = School::getSchoolTeacherList(self::$accept_data);
            return response()->json($result);
    }
    /*
     * @param  description 获取分校课程列表
     * @param  参数说明       body包含以下参数[
     *      'school_id'=>学校id
     * ]
     * @param author    lys
     * @param ctime     2020-05-11
     *///7.4调整
    public function getLessonLists(){
          
            $validator = Validator::make(self::$accept_data, 
                ['school_id' => 'required|integer'],
                School::message());
            if ($validator->fails()) {
                return response()->json(json_decode($validator->errors()->first(),1));
            }
            $result = School::getSchoolLessonList(self::$accept_data);
            return response()->json($result);
    }
   
    /*
     * @param  description 获取网校公开课列表
     * @param  参数说明       body包含以下参数[
     *      'school_id'=>学校id
     * ]
     * @param author    lys
     * @param ctime     2020-7-4
     */
    public function getOpenLessonList(){

            $validator = Validator::make(self::$accept_data, 
                ['school_id' => 'required|integer'],
                School::message());
            if ($validator->fails()) {
                return response()->json(json_decode($validator->errors()->first(),1));
            }
            $result = School::getOpenLessonList(self::$accept_data);
            return response()->json($result);
    }

    public function getSubjectList(){
            $validator = Validator::make(self::$accept_data, 
                [
                  'school_id' => 'required|integer',
                  'is_public'=> 'required|integer'  
                ],
                School::message());
            if ($validator->fails()) {
                return response()->json(json_decode($validator->errors()->first(),1));
            }
            $result = School::getSubjectList(self::$accept_data);
            return response()->json($result);
    }

}

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
use App\Models\AuthMap;
use App\Models\FootConfig;
use Illuminate\Support\Facades\DB;
use App\Models\CouresSubject;
use Log;
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
                    'route_url'      =>  'admin/school/doSchoolDel' , 
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
            $school = School::where(['id'=>$data['school_id'],'is_del'=>1])->first();
            if($school['is_forbid'] != 1){
                $school->is_forbid = 1; 
                $is_forbid = 1;
                $wx_pay_state = 1;
                $zfb_pay_state = 1;
                $hj_wx_pay_state = 1;
                $hj_zfb_pay_state = 1;
                $yl_pay_state = 1;
           
            }else{
                $school->is_forbid = 0; 
                $is_forbid = 0;
                $wx_pay_state = -1;
                $zfb_pay_state = -1;
                $hj_wx_pay_state = -1;
                $hj_zfb_pay_state = -1;
                $yl_pay_state = -1;
            }   
            if(!$school->save()){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '更新失败']);
            }
            if(!Adminuser::upUserStatus(['school_id'=>$school['id']],['is_forbid'=>$is_forbid])){
                 DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '更新失败']);
            }
            if(PaySet::where('school_id',$school['id'])->update(['wx_pay_state'=>$wx_pay_state,'zfb_pay_state'=>$zfb_pay_state,'hj_wx_pay_state'=>$hj_wx_pay_state,'hj_zfb_pay_state'=>$hj_zfb_pay_state,'yl_pay_state'=>$yl_pay_state,'update_at'=>date('Y-m-d H:i:s')] ) ){
                $data['is_forbid'] = $is_forbid; //修改后的状态
                AdminLog::insertAdminLog([
                    'admin_id'       =>   CurrentAdmin::user()['id'] ,
                    'module_name'    =>  'School' ,
                    'route_url'      =>  'admin/school/doSchoolForbid' , 
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
        $user_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        $data = self::$accept_data;
        $validator = Validator::make(
                $data, 
                ['name' => 'required',
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
        $count  = School::where('name',$data['name'])->where('is_del',1)->count();
        if($count>0){
            return response()->json(['code'=>205,'msg'=>'网校名称已存在']);
        }
        $count  = Adminuser::where('username',$data['username'])->count();
        if($count>0){
            return response()->json(['code'=>205,'msg'=>'用户名已存在']);
        }
        $date = date('Y-m-d H:i:s');
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
                'create_time'=>$date
            ];
            $school_id = School::insertGetId($school);
            if($school_id <1){
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
            $schoolRes = School::where('id',$school_id)->update(['super_id'=>$admin_id,'update_time'=>date('Y-m-d H:i:s')]); 
            if(!$schoolRes){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '创建账号未成功!!']);
            }

            $page_head_logo_insert = [
                ['parent_id'=>0,'name'=>'首页','url'=>'/home','type'=>1,'sort'=>1,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'课程','url'=>'/onlineStudent','type'=>1,'sort'=>2,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'公开课','url'=>'/courses','type'=>1,'sort'=>3,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'题库','url'=>'/question','type'=>1,'sort'=>4,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'新闻','url'=>'/newsNotice','type'=>1,'sort'=>5,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'名师','url'=>'/teacher','type'=>1,'sort'=>6,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'对公购买','url'=>'/corporatePurchase','type'=>1,'sort'=>7,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'扫码支付','url'=>'/scanPay','type'=>1,'sort'=>8,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>0],
                
                
            ];
            $pany_insert =['parent_id'=>0,'name'=>$data['name'],'type'=>3,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1];
            $page_foot_pid_insert = [
                ['parent_id'=>0,'name'=>'服务声明','url'=>'/service/','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'关于我们','url'=>'/about/','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'联系我们','url'=>'/contactUs/','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'友情链接','url'=>'','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
            ];
            $my_insert = [
                ['parent_id'=>0,'name'=>'关于我们','text'=>'关于我们','type'=>5,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                ['parent_id'=>0,'name'=>'联系客服','text'=>'联系客服','type'=>5,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
            ];
            $footPidIds = $footOne = $fooTwo = $fooThree = $footFore = [];
            foreach ($page_foot_pid_insert as $key => $pid) {
                $footPidId = FootConfig::insertGetId($pid);
                if($footPidId<1){
                    DB::rollBack();
                    return response()->json(['code' => 203 , 'msg' => '页面配置创建未成功!']);
                }
                array_push($footPidIds,$footPidId);
            }
            foreach($footPidIds as $k=>$id){
                switch ($k) {
                    case '0':
                        $footOne = [
                            ['parent_id'=>$id,'name'=>'服务规则','url'=>'rule','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'课程使用','url'=>'courseUse','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'免责声明','url'=>'disclaimer','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'退费服务','url'=>'refund','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                        ];   
                        break;
                    case '1':
                        $fooTwo = [
                            ['parent_id'=>$id,'name'=>'产品服务','url'=>'productService','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'名师简介','url'=>'teacherDetail','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'企业文化','url'=>'orgCulture','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'公司声明','url'=>'companyStatement','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                        ];   
                        break;
                    case '2':
                        $fooThree = [
                            ['parent_id'=>$id,'name'=>'电话咨询','url'=>'phoneCall','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'分校查询','url'=>'branchSchoolSearch','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'招商加盟','url'=>'joinIn','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                        ];   
                        break;
                    case '3':
                       $footFore = [
                            ['parent_id'=>$id,'name'=>'位置一','url'=>'','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'位置一','url'=>'','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'位置一','url'=>'','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                            ['parent_id'=>$id,'name'=>'位置一','url'=>'','type'=>2,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1],
                        ];   
                        break;    
                }
            }
            $icp_insert = ['parent_id'=>0,'logo'=>$data['logo_url'],'type'=>4,'sort'=>8,'sort'=>0,'school_id' =>$school_id,'admin_id'=>$user_id,'create_at'=>$date,'status'=>1];

            $icp_res = FootConfig::insert($icp_insert);
            if(!$icp_res){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '页面配置创建未成功!!']);
            }
            $payname_res = FootConfig::insert($pany_insert);
            if(!$icp_res){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '页面配置创建未成功!!!']);
            }
            $page_head_logo_res = FootConfig::insert($page_head_logo_insert);
            if(!$page_head_logo_res){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '页面配置创建未成功!!!!']);
            }
            $footInsert = array_merge($footOne,$fooTwo,$fooThree,$footFore);
            $footRes = FootConfig::insert($footInsert);
            if(!$footRes){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '页面配置创建未成功!!!!!']);
            }
            $my_res = FootConfig::insert($my_insert);
            if(!$my_res){
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '页面配置创建未成功!!']);
            }
            $payconfig = [
                'zfb_app_public_key'=>'',
                'zfb_public_key'=>'',
                'admin_id' => CurrentAdmin::user()['id'],
                'school_id'=> $school_id,
                'create_at'=> date('Y-m-d H:i:s')
            ];
            if(PaySet::insertGetId($payconfig)>0){
                AdminLog::insertAdminLog([
                    'admin_id'       =>   CurrentAdmin::user()['id'] ,
                    'module_name'    =>  'School' ,
                    'route_url'      =>  'admin/school/doInsertSchool' , 
                    'operate_method' =>  'update',
                    'content'        =>  json_encode($data),
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
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
        $roleAuthId = Roleauth::where(['school_id'=>$data['id'],'is_super'=>1])->select('id','auth_id','map_auth_id')->first(); //查询学校是否有超管人员角色
        if(is_null($roleAuthId)){
            //无
            $adminUser = Adminuser::where(['school_id'=>$data['id'],'is_del'=>1])->select('id','username','realname','mobile')->first();  
        }else{
            //有
            $adminUser = Adminuser::where(['school_id'=>$data['id'],'role_id'=>$roleAuthId['id'],'is_del'=>1])->select('id','username','realname','mobile')->first();  
        }

        $adminUser['role_id'] = $roleAuthId['id'] > 0 ? $roleAuthId['id']  : 0;
        // $adminUser['auth_id'] = $roleAuthId['map_auth_id'] ? $roleAuthId['map_auth_id']:null;  
        $adminUser['map_auth_id'] = $roleAuthId['map_auth_id'] ? $roleAuthId['map_auth_id']:null;  // 
        $adminUser['school_name'] =  !empty($schoolData['name']) ? $schoolData['name']  : '';
        $authRules = AuthMap::getAuthAlls(['is_del'=>0,'is_forbid'=>0],['id','title','parent_id']);
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
            
            $auths_id = AuthMap::where(['is_del'=>0,'is_show'=>0,'is_forbid'=>0])->pluck('id')->toArray();
            $auth_id = explode(',', $data['auth_id']);
            $auth_id = array_unique($auth_id);
            $auth_id = array_diff($auth_id,['0']);
            foreach ($auth_id as $v) {
                if(in_array($v,$auths_id)){
                    $arr[]= $v;
                }
            }
        }
        //map 表里边的数据
        $mapAuthIds  = AuthMap::whereIn('id',$arr)->pluck('auth_id')->toArray();
        $publicAuth = Authrules::where(['is_del'=>1,'is_show'=>1,'is_forbid'=>1,'parent_id'=>-1])->pluck('id')->toArray();//公共权限
        $auth = array_merge($mapAuthIds,$publicAuth);
        $auth = implode(',', $auth);
        $auth = explode(',', $auth);    
        $auth = array_unique($auth);

        $roleAuthArr = Roleauth::where(['school_id'=>$data['id'],'is_super'=>1,'is_del'=>1])->first(); //判断该网校有无超级管理员
        if(isset($data['admin/school/doSchoolAdminById'])) unset($data['admin/school/doSchoolAdminById']);
        DB::beginTransaction();
        if(empty($roleAuthArr)){//无超级管理员
            //无
            $insert =[
                    'role_name'=>'超级管理员',
                    'auth_desc'=>'拥有所有权限',
                    'auth_id' => empty($auth)?$auth:implode(",",$auth),
                    'map_auth_id'=> empty($arr)?$arr:implode(",",$arr),
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

            $super  = Roleauth::where(['id'=>$data['role_id']])->select('is_super')->first()->toArray();
       
            if($super['is_super']<1){ //判断是否为超管
                return response()->json(['code'=>404,'msg'=>'非法请求']);
            }
            //如果是超管，那么删除权限，那么其他角色权限也都有被删除，不管是否正在使用中。
            $fen_role_auth_arr = Roleauth::where(['is_del'=>1,'is_super'=>0])->where('school_id',$data['id'])->select('map_auth_id','id')->get()->toArray();
            if(!empty($fen_role_auth_arr)){
                foreach ($fen_role_auth_arr as $k => $v) {
                    $fen_roles_id = explode(",", $v['map_auth_id']); 
                    $new_arr = array_diff($fen_roles_id,$arr);//取差集
                    $new_qita_role_ids = array_diff($fen_roles_id,$new_arr);//取共同的差集

                    $fen_roles_id = AuthMap::whereIn('id',$new_qita_role_ids)->where(['is_del'=>0,'is_forbid'=>0,'is_show'=>0])->pluck('auth_id')->toArray(); //取数据
                         
                    $publicAuthArr =  Authrules::where(['is_del'=>1,'is_forbid'=>1,'is_show'=>1,'parent_id'=>-1])->pluck('id')->toArray();//公共的部分
                    $updateAuthids = array_merge($fen_roles_id,$publicAuthArr);

                    $updateAuthids = implode(',', $updateAuthids);

                    $updateAuthids = explode(',', $updateAuthids);  

                    $updateAuthids = array_unique($updateAuthids);
                    
                    if(!empty($new_qita_role_ids)){
                        $res = Roleauth::where(['id'=>$v['id']])->update(['map_auth_id'=>implode(",", $new_qita_role_ids),'auth_id'=>implode(",", $updateAuthids),'update_time'=>date('Y-m-d H:i:s')]);
                        if(!$res){
                            DB::rollBack();
                            return response()->json(['code'=>203,'msg'=>'赋权成功']);
                        }
                    }
                }
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
        } 
        $auth = empty($auth)?$auth:implode(",",$auth);
        $arr = empty($arr)?$arr:implode(",",$arr);
 
        $update = ['auth_id'=>$auth,'map_auth_id'=>$arr,'update_time'=>date('Y-m-d H:i:s')];
        Log::info('数据.', ['data' => $update]);
        if(Roleauth::where('id',$data['role_id'])->update($update)){
            DB::commit();
            return response()->json(['code'=>200,'msg'=>'赋权成功']);
        }else{
            DB::rollBack();
            return response()->json(['code'=>203,'msg'=>'网络错误，请重试']);
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
        AdminLog::insertAdminLog([
                'admin_id'       =>   CurrentAdmin::user()['id'] ,
                'module_name'    =>  'School' ,
                'route_url'      =>  'admin/school/doAdminUpdate' , 
                'operate_method' =>  'update' ,
                'content'        =>  json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
        ]);    
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

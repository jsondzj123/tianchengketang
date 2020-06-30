<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LiveClass extends Model {

    //指定别的表名
    public $table = 'ld_course_shift_no';

    //时间戳设置
    public $timestamps = false;

        /*
         * @param  获取班号列表
         * @param  parent_id   所属学科大类id
         * @param  nature   资源属性
         * @param  is_forbid   资源状态
         * @param  name     课程单元名称
         * @param  author  zzk
         * @param  ctime   2020/6/23
         * return  array
         */

        public static function getLiveClassList($data){
            //每页显示的条数
            $pagesize = isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 15;
            $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
            $offset   = ($page - 1) * $pagesize;
            //直播单元id
            $resource_id = $data['resource_id'];
            //获取总条数
            $total = self::where(['is_del'=>0,'resource_id'=>$resource_id])->get()->count();
            //获取数据
            $list = self::where(['is_del'=>0,'resource_id'=>$resource_id])->offset($offset)->limit($pagesize)->get();
            //添加总课次
            //已上课次
            //待上课次
            //课次信息
            if($total > 0){
                return ['code' => 200 , 'msg' => '获取班号列表成功' , 'data' => ['LiveClass_list' => $list, 'total' => $total , 'pagesize' => $pagesize , 'page' => $page]];
            }else{
                return ['code' => 200 , 'msg' => '获取班号列表成功' , 'data' => ['LiveClass_list' => [], 'total' => $total , 'pagesize' => $pagesize , 'page' => $page]];
            }
        }

        /*
         * @param  添加直播单元班号
         * @param  resource_id   课程单元id
         * @param  name   班号名称
         * @param  content   班号信息
         * @param  author  zzk
         * @param  ctime   2020/6/28
         * return  array
         */
        public static function AddLiveClass($data){
            //课程单元id
            if(empty($data['resource_id']) || !isset($data['resource_id'])){
                return ['code' => 201 , 'msg' => '直播单元id不能为空'];
            }
            //班号名称
            if(empty($data['name']) || !isset($data['name'])){
                return ['code' => 201 , 'msg' => '班号名称不能为空'];
            }
            //班号信息
            if(empty($data['content']) || !isset($data['content'])){
                return ['code' => 201 , 'msg' => '班号信息不能为空'];
            }

            //缓存查出用户id和分校id
            $data['school_id'] = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            $data['admin_id'] = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;

            $data['create_at'] = date('Y-m-d H:i:s');
            $data['update_at'] = date('Y-m-d H:i:s');
            $add = self::insert($data);
            if($add){
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $data['admin_id']  ,
                    'module_name'    =>  'LiveClass' ,
                    'route_url'      =>  'admin/LiveClass/add' ,
                    'operate_method' =>  'insert' ,
                    'content'        =>  '新增数据'.json_encode($data) ,
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                return ['code' => 200 , 'msg' => '添加成功'];
            }else{
                return ['code' => 202 , 'msg' => '添加失败'];
            }
        }
        /*
         * @param  更新直播单元班号
         * @param  resource_id   课程单元id
         * @param  name   班号名称
         * @param  content   班号信息
         * @param  id   班号id
         * @param  author  zzk
         * @param  ctime   2020/6/29
         * return  array
         */
        public static function updateLiveClass($data){
            //班号id
            if(empty($data['id']) || !isset($data['id'])){
                return ['code' => 201 , 'msg' => '直播单元id不能为空'];
            }
            //课程单元id
            if(empty($data['resource_id']) || !isset($data['resource_id'])){
                return ['code' => 201 , 'msg' => '直播单元id不能为空'];
            }
            //班号名称
            if(empty($data['name']) || !isset($data['name'])){
                return ['code' => 201 , 'msg' => '班号名称不能为空'];
            }
            //班号信息
            if(empty($data['content']) || !isset($data['content'])){
                return ['code' => 201 , 'msg' => '班号信息不能为空'];
            }
            //获取后端的操作员id
            $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
            $data['admin_id'] = $admin_id;

            $data['update_at'] = date('Y-m-d H:i:s');
            $id = $data['id'];
            unset($data['id']);
            $res = self::where(['id'=>$id])->update($data);
            if($res){
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $data['admin_id']  ,
                    'module_name'    =>  'LiveClass' ,
                    'route_url'      =>  'admin/updateLiveClass' ,
                    'operate_method' =>  'update' ,
                    'content'        =>  '更新数据'.json_encode($data) ,
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                return ['code' => 200 , 'msg' => '更新成功'];
            }else{
                return ['code' => 202 , 'msg' => '更新失败'];
            }
        }

        /*
         * @param  更改直播单元班号状态
         * @param  id   班号id
         * @param  author  zzk
         * @param  ctime   2020/6/28
         * return  array
         */
        public static function updateLiveClassStatus($data){
            if(empty($data['id']) || !isset($data['id'])){
                return ['code' => 201 , 'msg' => '参数为空或格式错误'];
            }
            $LiveClassOne = self::where(['id'=>$data['id']])->first();
            if(!$LiveClassOne){
                return ['code' => 201 , 'msg' => '参数不对'];
            }
            //查询是否和课程关联
            //等学科写完继续
            $is_forbid = ($LiveClassOne['is_forbid']==1)?0:1;
            $update = self::where(['id'=>$data['id']])->update(['is_forbid'=>$is_forbid,'update_at'=>date('Y-m-d H:i:s')]);
            if($update){
                //获取后端的操作员id
                $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id  ,
                    'module_name'    =>  'LiveClass' ,
                    'route_url'      =>  'admin/updateLiveClassStatus' ,
                    'operate_method' =>  'update' ,
                    'content'        =>  '操作'.json_encode($data) ,
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                return ['code' => 200 , 'msg' => '修改成功'];
            }else{
                return ['code' => 202 , 'msg' => '修改失败'];
            }
        }

        /*
         * @param  直播单元班号删除
         * @param  id   班号id
         * @param  author  zzk
         * @param  ctime   2020/6/28
         * return  array
         */
        public static function updateLiveClassDelete($data){
            //判断直播资源id
            if(empty($data['id'])|| !isset($data['id'])){
                return ['code' => 201 , 'msg' => '参数为空或格式错误'];
            }
            $LiveClassOne = self::where(['id'=>$data['id']])->first();
            if(!$LiveClassOne){
                return ['code' => 204 , 'msg' => '参数不正确'];
            }
            //查询是否关联课程
            //等学科写完继续
            $update = self::where(['id'=>$data['id']])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
            if($update){
                //获取后端的操作员id
                $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id  ,
                    'module_name'    =>  'LiveClass' ,
                    'route_url'      =>  'admin/deleteLiveClass' ,
                    'operate_method' =>  'delete' ,
                    'content'        =>  '软删除id为'.$data['id'],
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                return ['code' => 200 , 'msg' => '删除成功'];
            }else{
                return ['code' => 202 , 'msg' => '删除失败'];
            }
        }
}


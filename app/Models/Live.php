<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Subject;
use App\Models\LiveClass;
use App\Models\Admin;
use App\Models\CourseLiveResource;
class Live extends Model {

    //指定别的表名
    public $table = 'ld_course_livecast_resource';
    //时间戳设置
    public $timestamps = false;
/*
         * @param  获取直播资源列表
         * @param  parent_id   所属学科大类id
         * @param  nature   资源属性
         * @param  is_forbid   资源状态
         * @param  name     课程单元名称
         * @param  author  zzk
         * @param  ctime   2020/6/23
         * return  array
         */
        public static function getLiveList($data){
            //每页显示的条数
            $pagesize = isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 15;
            $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
            $offset   = ($page - 1) * $pagesize;
            //获取总条数
            $total = self::join('ld_course_subject','ld_course_subject.id','=','ld_course_livecast_resource.parent_id')->select('*','ld_course_livecast_resource.parent_id','ld_course_livecast_resource.child_id')->where(function($query) use ($data){
                // //获取后端的操作员id
                // $admin_id= isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                // //操作员id
                // $query->where('ld_course_livecast_resource.admin_id' , '=' , $admin_id);
                //删除状态
                $query->where('ld_course_livecast_resource.is_del' , '=' , 0);
                //判断学科大类id是否为空
                if(isset($data['parent_id']) && !empty(isset($data['parent_id']))){
                    $s_id = json_decode($data['parent_id']);
                    $data['parent_id'] = $s_id[0];
                    if(!empty($s_id[1])){
                        $data['child_id'] = $s_id[1];
                    }else{
                        $data['child_id'] = 0;
                    }
                    $query->where('ld_course_livecast_resource.parent_id' , '=' , $data['parent_id']);
                }
                //判断学科小类
                if(isset($data['child_id']) && !empty(isset($data['child_id']))){
                    $query->where('ld_course_livecast_resource.child_id' , '=' , $data['child_id']);
                }
                //判断资源属性是否为空
                if(isset($data['nature']) && !empty(isset($data['nature']))){
                    $query->where('ld_course_livecast_resource.nature' , '=' , $data['nature']);
                }
                //判断资源状态是否为空
                if(isset($data['status']) && !empty(isset($data['status']))){
                    $query->where('ld_course_livecast_resource.status' , '=' , $data['status']);
                }
                //判断课程单元名称是否为空
                if(isset($data['name']) && !empty(isset($data['name']))){
                    $query->where('name','like',$data['name'].'%');
                }
            })->get()->count();
            //获取所有列表
            if($total > 0){
                $list = self::join('ld_course_subject','ld_course_subject.id','=','ld_course_livecast_resource.parent_id')->select('*','ld_course_livecast_resource.parent_id','ld_course_livecast_resource.child_id','ld_course_livecast_resource.id')->where(function($query) use ($data){
                    // //获取后端的操作员id
                    // $admin_id= isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                    // //操作员id
                    // $query->where('ld_course_livecast_resource.admin_id' , '=' , $admin_id);
                    //删除状态
                    $query->where('ld_course_livecast_resource.is_del' , '=' , 0);
                    //判断学科大类id是否为空
                    if(isset($data['parent_id']) && !empty(isset($data['parent_id']))){
                        $s_id = json_decode($data['parent_id']);
                        $data['parent_id'] = $s_id[0];
                        if(!empty($s_id[1])){
                            $data['child_id'] = $s_id[1];
                        }else{
                            $data['child_id'] = 0;
                        }
                        $query->where('ld_course_livecast_resource.parent_id' , '=' , $data['parent_id']);
                    }
                    //判断学科小类
                    if(isset($data['child_id']) && !empty(isset($data['child_id']))){
                        $query->where('ld_course_livecast_resource.child_id' , '=' , $data['child_id']);
                    }
                    //判断资源属性是否为空
                    if(isset($data['nature']) && !empty(isset($data['nature']))){
                        $query->where('ld_course_livecast_resource.nature' , '=' , $data['nature']);
                    }
                    //判断资源状态是否为空
                    if(isset($data['status']) && !empty(isset($data['status']))){
                        $query->where('ld_course_livecast_resource.status' , '=' , $data['status']);
                    }
                    //判断课程单元名称是否为空
                    if(isset($data['name']) && !empty(isset($data['name']))){
                        $query->where('name','like',$data['name'].'%');
                    }

                })->offset($offset)->limit($pagesize)->get();
                foreach($list as $k => $live){

                    //状态是否被课程使用

                    //获取班号数量
                    $live['class_num'] = 1;
                    $live['admin_name'] = Admin::where("is_del",0)->where("id",$live['admin_id'])->select("username")->first()['username'];
                    $live['subject_child_name'] = Subject::where("is_del",0)->where("id",$live['child_id'])->select("subject_name")->first()['subject_name'];
                }
                return ['code' => 200 , 'msg' => '获取直播资源列表成功' , 'data' => ['Live_list' => $list, 'total' => $total , 'pagesize' => $pagesize , 'page' => $page]];
            }else{
                return ['code' => 200 , 'msg' => '获取直播资源列表成功' , 'data' => ['Live_list' => [], 'total' => $total , 'pagesize' => $pagesize , 'page' => $page]];
            }
        }

        /*
         * @param  获取直播资源详情
         * @param  id   资源id
         * @param  author  zzk
         * @param  ctime   2020/6/28
         * return  array
         */
        public static function getLiveOne($data){
            if(empty($data['id'])){
                return ['code' => 201 , 'msg' => '直播资源id不合法' , 'data' => []];
            }
            $one = self::where("is_del",0)->where("id",$data['id'])->first();
            //获取学科小类和大类
            $one['subject_name'] = Subject::where("is_del",0)->where("id",$one['parent_id'])->select("subject_name")->first()['subject_name'];
            $one['subject_child_name'] = Subject::where("is_del",0)->where("id",$one['child_id'])->select("subject_name")->first()['subject_name'];
            //添加总课时  该资源下所有班号下课次的所有课时
            $one['sum_class_hour'] = LiveClass::join('ld_course_class_number','ld_course_shift_no.id','=','ld_course_class_number.shift_no_id')
            ->where("resource_id",$one['id'])->sum("class_hour");
            if(!empty($one['child_id'])){
                $one['parent_id'] = [$one['parent_id'],$one['child_id']];
            }
            $one['parent_id'] = [$one['parent_id']];
            unset($one['child_id']);
            return ['code' => 200 , 'msg' => '获取直播资源列表成功' , 'data' => $one];

        }

        /*
         * @param  更改资源状态
         * @param  id   资源id
         * @param  author  zzk
         * @param  ctime   2020/6/28
         * return  array
         */
        public static function updateLiveStatus($data){
            if(empty($data['id']) || !isset($data['id'])){
                return ['code' => 201 , 'msg' => '参数为空或格式错误'];
            }
            $LiveOne = self::where(['id'=>$data['id']])->first();
            if(!$LiveOne){
                return ['code' => 201 , 'msg' => '参数不对'];
            }
            //查询是否和课程关联
            //等学科写完继续
            $is_forbid = ($LiveOne['is_forbid']==2)?0:2;
            $update = self::where(['id'=>$data['id']])->update(['is_forbid'=>$is_forbid,'update_at'=>date('Y-m-d H:i:s')]);
            if($update){
                //获取后端的操作员id
                $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id  ,
                    'module_name'    =>  'Live' ,
                    'route_url'      =>  'admin/updateLiveStatus' ,
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
         * @param  直播资源删除
         * @param  id   资源id
         * @param  author  zzk
         * @param  ctime   2020/6/28
         * return  array
         */
        public static function updateLiveDelete($data){
            //判断直播资源id
            if(empty($data['id'])|| !isset($data['id'])){
                return ['code' => 201 , 'msg' => '参数为空或格式错误'];
            }
            $LiveOne = self::where(['id'=>$data['id']])->first();
            if(!$LiveOne){
                return ['code' => 204 , 'msg' => '参数不正确'];
            }
            //查询是否关联课程
            //等学科写完继续

            //查询是否是授权资源
            $LiveOnes = self::where(['id'=>$data['id'],'nature'=>0])->first();
            if(!$LiveOnes){
                return ['code' => 204 , 'msg' => '该资源为授权资源，无法删除'];
            }
            $update = self::where(['id'=>$data['id']])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
            if($update){
                //获取后端的操作员id
                $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id  ,
                    'module_name'    =>  'Live' ,
                    'route_url'      =>  'admin/deleteLive' ,
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
        /*
         * @param  添加直播资源
         * @param  parent_id   所属学科大类id
         * @param  child_id   所属学科小类id
         * @param  nature   资源属性
         * @param  status   资源状态
         * @param  name   资源名称
         * @param  introduce   资源介绍
         * @param  author  zzk
         * @param  ctime   2020/6/28
         * return  array
         */
        public static function AddLive($data){
            //判断大类id
            unset($data['/admin/live/add']);
            if(empty($data['parent_id']) || !isset($data['parent_id'])){
                return ['code' => 201 , 'msg' => '请正确选择分类'];
            }
            //判断资源名称
            if(empty($data['name']) || !isset($data['name'])){
                return ['code' => 201 , 'msg' => '资源名称不能为空'];
            }
            //判断资源介绍
            if(empty($data['introduce']) || !isset($data['introduce'])){
                return ['code' => 201 , 'msg' => '资源介绍不能为空'];
            }
            $s_id = json_decode($data['parent_id']);
                $data['parent_id'] = $s_id[0];
            if(!empty($s_id[1])){
                $data['child_id'] = $s_id[1];
            }else{
                $data['child_id'] = 0;
            }
            //缓存查出用户id和分校id
            $data['school_id'] = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            $data['admin_id'] = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;

            //nature资源属性
            $data['nature'] = 0;
            $data['create_at'] = date('Y-m-d H:i:s');
            $data['update_at'] = date('Y-m-d H:i:s');
            $add = self::insert($data);
            if($add){
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $data['admin_id']  ,
                    'module_name'    =>  'Live' ,
                    'route_url'      =>  'admin/Live/add' ,
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
         * @param  更改直播资源
         * @param  parent_id   所属学科大类id
         * @param  child_id   所属学科小类id
         * @param  nature   资源属性
         * @param  status   资源状态
         * @param  name   资源名称
         * @param  introduce   资源介绍
         * @param  author  zzk
         * @param  ctime   2020/6/28
         * return  array
         */
        public static function updateLive($data){
            //判断大类id
            unset($data['/admin/updateLive']);
            if(empty($data['parent_id']) || !isset($data['parent_id'])){
                return ['code' => 201 , 'msg' => '请正确选择分类'];
            }
            //判断资源名称
            if(empty($data['name']) || !isset($data['name'])){
                return ['code' => 201 , 'msg' => '资源名称不能为空'];
            }
            //判断资源介绍
            if(empty($data['introduce']) || !isset($data['introduce'])){
                return ['code' => 201 , 'msg' => '资源介绍不能为空'];
            }
            $s_id = json_decode($data['parent_id']);
                $data['parent_id'] = $s_id[0];
            if(!empty($s_id[1])){
                $data['child_id'] = $s_id[1];
            }else{
                $data['child_id'] = 0;
            }
            $id = $data['id'];
            unset($data['id']);
            //获取后端的操作员id
            $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
            $data['admin_id'] = $admin_id;
            $data['update_at'] = date('Y-m-d H:i:s');
            $res = self::where(['id'=>$id])->update($data);
            if($res){
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $data['admin_id']  ,
                    'module_name'    =>  'Live' ,
                    'route_url'      =>  'admin/updateLive' ,
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

        public static function liveRelationLesson($data){
            //直播资源id
            if(empty($data['resource_id']) || !isset($data['resource_id'])){
                return ['code' => 201 , 'msg' => '直播资源id不能为空'];
            }
            //课程id
            if(!isset($data['course_id'])){
                return ['code' => 201 , 'msg' => '课程id不能为空'];
            }
            if(!is_array($data['course_id'])){
                return ['code' => 201 , 'msg' => '课程id数据不正确'];
            }
            $data['course_id'] = implode(",",$data['course_id']);
            $res = explode(",", $data['course_id']);
            foreach($res as $k => $v){
                $data[$k]['resource_id'] = $data['resource_id'];
                $data[$k]['course_id'] = $v;
                $data[$k]['create_at'] = date('Y-m-d H:i:s');
                $data[$k]['update_at'] = date('Y-m-d H:i:s');
            }
            unset($data['resource_id']);
            unset($data['course_id']);
            $add = CourseLiveResource::insert($data);
            //获取后端的操作员id
            $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
            if($add){
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id  ,
                    'module_name'    =>  'liveRelationLesson' ,
                    'route_url'      =>  'admin/liveRelationLesson' ,
                    'operate_method' =>  'insert' ,
                    'content'        =>  '新增数据'.json_encode($data) ,
                    'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                    'create_at'      =>  date('Y-m-d H:i:s')
                ]);
                return ['code' => 200 , 'msg' => '关联成功'];
            }else{
                return ['code' => 202 , 'msg' => '关联失败'];
            }
        }
}


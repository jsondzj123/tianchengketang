<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model {


    public $table = 'ld_course_video_resource';
    //时间戳设置
    public $timestamps = false;
	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    /*
         * @param  获取录播资源列表
         * @param  parent_id   所属学科id
         * @param  resource_type   资源类型
         * @param  nature   资源属性
         * @param  status   资源状态
         * @param  resource_name   资源名称
         * @param  id   资源id
         * @param  author  zzk
         * @param  ctime   2020/6/23
         * return  array
         */
        public static function getVideoList($data){
            //每页显示的条数
            $pagesize = isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 15;
            $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
            $offset   = ($page - 1) * $pagesize;
            //获取总条数
            $total = self::join('ld_course_subject', 'ld_course_subject.id', '=', 'ld_course_video_resource.parent_id')->where(function($query) use ($data){
                // //获取后端的操作员id
                // $admin_id= isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                // //操作员id
                // $query->where('ld_course_video_resource.admin_id' , '=' , $admin_id);
                //删除状态
                $query->where('ld_course_video_resource.is_del' , '=' , 0);
                //判断学科id是否为空
                if(isset($data['parent_id']) && !empty(isset($data['parent_id']))){
                    $query->where('ld_course_video_resource.parent_id' , '=' , $data['parent_id']);
                }
                //判断资源类型是否为空
                if(isset($data['resource_type']) && !empty(isset($data['resource_type']))){
                    $query->where('ld_course_video_resource.resource_type' , '=' , $data['resource_type']);
                }
                //判断资源属性是否为空
                if(isset($data['nature']) && !empty(isset($data['nature']))){
                    $query->where('ld_course_video_resource.nature' , '=' , $data['nature']);
                }
                //判断资源状态是否为空
                if(isset($data['status']) && !empty(isset($data['status']))){
                    $query->where('ld_course_video_resource.status' , '=' , $data['status']);
                }
                //判断资源id是否为空
                if(isset($data['id']) && !empty(isset($data['id']))){
                    $query->where('ld_course_video_resource.id' , '=' , $data['id']);
                }
                //判断资源名称是否为空
                if(isset($data['resource_name']) && !empty(isset($data['resource_name']))){
                    $query->where('resource_name','like',$data['resource_name'].'%');
                }
            })->get()->count();
            //获取所有列表
            if($total > 0){
                $list = self::join('ld_course_subject', 'ld_course_subject.id', '=', 'ld_course_video_resource.parent_id')->select('*','ld_course_video_resource.parent_id')->where(function($query) use ($data){
                    // //获取后端的操作员id
                    // $admin_id= isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                    // //操作员id
                    // $query->where('ld_course_video_resource.admin_id' , '=' , $admin_id);
                    //删除状态
                    $query->where('ld_course_video_resource.is_del' , '=' , 0);
                    //判断学科id是否为空
                    if(isset($data['parent_id']) && !empty(isset($data['parent_id']))){
                        $query->where('ld_course_video_resource.parent_id' , '=' , $data['parent_id']);
                    }
                    //判断资源类型是否为空
                    if(isset($data['resource_type']) && !empty(isset($data['resource_type']))){
                        $query->where('ld_course_video_resource.resource_type' , '=' , $data['resource_type']);
                    }
                    //判断资源属性是否为空
                    if(isset($data['nature']) && !empty(isset($data['nature']))){
                        $query->where('ld_course_video_resource.nature' , '=' , $data['nature']);
                    }
                    //判断资源状态是否为空
                    if(isset($data['status']) && !empty(isset($data['status']))){
                        $query->where('ld_course_video_resource.status' , '=' , $data['status']);
                    }
                    //判断资源id是否为空
                    if(isset($data['id']) && !empty(isset($data['id']))){
                        $query->where('ld_course_video_resource.id' , '=' , $data['id']);
                    }
                    //判断资源名称是否为空
                    if(isset($data['resource_name']) && !empty(isset($data['resource_name']))){
                        $query->where('resource_name','like','%'.$data['resource_name'].'%');
                    }

                })->offset($offset)->limit($pagesize)->get();
                return ['code' => 200 , 'msg' => '获取录播资源列表成功' , 'data' => ['video_list' => $list, 'total' => $total , 'pagesize' => $pagesize , 'page' => $page]];
            }else{
                return ['code' => 200 , 'msg' => '获取录播资源列表成功' , 'data' => ['video_list' => [], 'total' => $total , 'pagesize' => $pagesize , 'page' => $page]];
            }
        }
        /*
         * @param  获取录播资源详情
         * @param  id   资源id
         * @param  author  zzk
         * @param  ctime   2020/6/23
         * return  array
         */
        public static function getVideoOne($data){
            if(empty($data['id'])){
                return ['code' => 201 , 'msg' => '录播资源id不合法' , 'data' => []];
            }
            $one = self::where("is_del",0)->where("id",$data['id'])->first()->toArray();
            return ['code' => 200 , 'msg' => '获取录播资源列表成功' , 'data' => $one];

        }
        /*
         * @param  更改资源状态
         * @param  id   资源id
         * @param  author  zzk
         * @param  ctime   2020/6/23
         * return  array
         */
        public static function updateVideoStatus($data){
            if(empty($data['id']) || !isset($data['id'])){
                return ['code' => 201 , 'msg' => '参数为空或格式错误'];
            }
            $videoOne = self::where(['id'=>$data['id']])->first();
            if(!$videoOne){
                return ['code' => 201 , 'msg' => '参数不对'];
            }
            //查询是否和课程关联
            //等学科写完继续
            $status = ($videoOne['status']==1)?0:1;
            $update = self::where(['id'=>$data['id']])->update(['status'=>$status,'update_at'=>date('Y-m-d H:i:s')]);
            if($update){
                //获取后端的操作员id
                $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id  ,
                    'module_name'    =>  'video' ,
                    'route_url'      =>  'admin/updateVideoStatus' ,
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
         * @param  录播资源删除
         * @param  id   资源id
         * @param  author  zzk
         * @param  ctime   2020/6/24
         * return  array
         */
        public static function updateVideoDelete($data){
            //判断录播资源id
            if(empty($data['id'])|| !isset($data['id'])){
                return ['code' => 201 , 'msg' => '参数为空或格式错误'];
            }
            $videoOne = self::where(['id'=>$data['id']])->first();
            if(!$videoOne){
                return ['code' => 204 , 'msg' => '参数不正确'];
            }
            //查询是否关联课程
            //等学科写完继续

            //查询是否是授权资源
            $videoOnes = self::where(['id'=>$data['id'],'nature'=>0])->first();
            if(!$videoOnes){
                return ['code' => 204 , 'msg' => '该资源为授权资源，无法删除'];
            }
            $update = self::where(['id'=>$data['id']])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
            if($update){
                //获取后端的操作员id
                $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id  ,
                    'module_name'    =>  'video' ,
                    'route_url'      =>  'admin/deleteVideo' ,
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


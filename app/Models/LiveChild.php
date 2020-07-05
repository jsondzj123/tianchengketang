<?php
namespace App\Models;
use App\Models\LiveClassChildTeacher;
use App\Models\CourseLiveClassChild;
use Illuminate\Database\Eloquent\Model;
use App\Tools\MTCloud;
class LiveChild extends Model {
    //指定别的表名
    public $table = 'ld_course_class_number';

    //时间戳设置
    public $timestamps = false;

        /*
         * @param  获取班号课次列表
         * @param  shift_no_id   班号id
         * @param  author  zzk
         * @param  ctime   2020/6/23
         * return  array
         */

        public static function getLiveClassChildList($data){
            //每页显示的条数
            $pagesize = isset($data['pagesize']) && $data['pagesize'] > 0 ? $data['pagesize'] : 15;
            $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
            $offset   = ($page - 1) * $pagesize;
            //直播单元id
            $shift_no_id = $data['shift_no_id'];
            //获取总条数
            $total = self::where(['is_del'=>0,'shift_no_id'=>$shift_no_id])->get()->count();
            //获取数据
            $list = self::where(['is_del'=>0,'shift_no_id'=>$shift_no_id])->offset($offset)->limit($pagesize)->get();
            //添加总课次
            //已上课次
            //待上课次
            //课次信息
            if($total > 0){
                return ['code' => 200 , 'msg' => '获取班号课次列表成功' , 'data' => ['LiveClassChild_list' => $list, 'total' => $total , 'pagesize' => $pagesize , 'page' => $page]];
            }else{
                return ['code' => 200 , 'msg' => '获取班号课次成功' , 'data' => ['LiveClassChild_list' => [], 'total' => $total , 'pagesize' => $pagesize , 'page' => $page]];
            }
        }
        /*
         * @param  添加直播单元班号课次
         * @param  shift_no_id   班号id
         * @param  start_at 课次开始时间
         * @param  end_at 课次结束时间
         * @param  name   课次名称
         * @param  class_hour  课时
         * @param  is_free  是否收费(1代表是,0代表否)
         * @param  is_bullet  是否弹幕(1代表是,0代表否)
         * @param  live_type  选择模式(1语音云3大班5小班6大班互动)
         * @param  author  zzk
         * @param  ctime   2020/6/29
         * return  array
         */
        public static function AddLiveClassChild($data){
            unset($data['/admin/liveChild/add']);
            //班号id
            if(empty($data['shift_no_id']) || !isset($data['shift_no_id'])){
                return ['code' => 201 , 'msg' => '班号id不能为空'];
            }
            //课次开始时间
            if(empty($data['start_at']) || !isset($data['start_at'])){
                return ['code' => 201 , 'msg' => '课次开始时间不能为空'];
            }
            //课次结束时间
            if(empty($data['end_at']) || !isset($data['end_at'])){
                return ['code' => 201 , 'msg' => '课次结束时间不能为空'];
            }
            //课次名称
            if(empty($data['name']) || !isset($data['name'])){
                return ['code' => 201 , 'msg' => '课次名称不能为空'];
            }
            //课时
            if(empty($data['class_hour']) || !isset($data['class_hour'])){
                return ['code' => 201 , 'msg' => '课时不能为空'];
            }
            //选择模式
            if(empty($data['live_type']) || !isset($data['live_type'])){
                return ['code' => 201 , 'msg' => '选择模式不能为空'];
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
                    'module_name'    =>  'LiveClassChild' ,
                    'route_url'      =>  'admin/liveChild/add' ,
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
         * @param  更新直播单元班号课次
         * @param  shift_no_id   班号id
         * @param  start_at 课次开始时间
         * @param  end_at 课次结束时间
         * @param  name   课次名称
         * @param  class_hour  课时
         * @param  is_free  是否收费(1代表是,0代表否)
         * @param  is_bullet  是否弹幕(1代表是,0代表否)
         * @param  live_type  选择模式(1语音云3大班5小班6大班互动)
         * @param  id  课次id
         * @param  author  zzk
         * @param  ctime   2020/6/29
         * return  array
         */
        public static function updateLiveClassChild($data){
            //课次id
            unset($data['/admin/updateLiveChild']);
            if(empty($data['id']) || !isset($data['id'])){
                return ['code' => 201 , 'msg' => '课次id不能为空'];
            }
            //班号id
            if(empty($data['shift_no_id']) || !isset($data['shift_no_id'])){
                return ['code' => 201 , 'msg' => '班号id不能为空'];
            }
            //课次开始时间
            if(empty($data['start_at']) || !isset($data['start_at'])){
                return ['code' => 201 , 'msg' => '课次开始时间不能为空'];
            }
            //课次结束时间
            if(empty($data['end_at']) || !isset($data['end_at'])){
                return ['code' => 201 , 'msg' => '课次结束时间不能为空'];
            }
            //课次名称
            if(empty($data['name']) || !isset($data['name'])){
                return ['code' => 201 , 'msg' => '课次名称不能为空'];
            }
            //课时
            if(empty($data['class_hour']) || !isset($data['class_hour'])){
                return ['code' => 201 , 'msg' => '课时不能为空'];
            }
            //选择模式
            if(empty($data['live_type']) || !isset($data['live_type'])){
                return ['code' => 201 , 'msg' => '选择模式不能为空'];
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
                    'module_name'    =>  'LiveClassChild' ,
                    'route_url'      =>  'admin/updateLiveChild' ,
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
         * @param  ctime   2020/6/29
         * return  array
         */
        public static function updateLiveClassChildStatus($data){
            return ['code' => 200 , 'msg' => '暂未开放'];
        }

        /*
         * @param  直播单元班号课次删除
         * @param  id   课次id
         * @param  author  zzk
         * @param  ctime   2020/6/29
         * return  array
         */
        public static function updateLiveClassChildDelete($data){
            //课次id
            if(empty($data['id'])|| !isset($data['id'])){
                return ['code' => 201 , 'msg' => '参数为空或格式错误'];
            }
            $LiveClassOne = self::where(['id'=>$data['id']])->first();
            if(!$LiveClassOne){
                return ['code' => 204 , 'msg' => '参数不正确'];
            }
            $update = self::where(['id'=>$data['id'],'is_del'=>0])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
            if($update){
                //获取后端的操作员id
                $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id  ,
                    'module_name'    =>  'LiveClassChild' ,
                    'route_url'      =>  'admin/deleteLiveChild' ,
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
        //关联讲师教务
        public static function LiveClassChildTeacher($data){
            //课次id
            if(empty($data['class_id'])|| !isset($data['class_id'])){
                return ['code' => 201 , 'msg' => '课次id不能为空'];
            }
            //讲师id
            if(empty($data['teacher_id'])|| !isset($data['teacher_id'])){
                return ['code' => 201 , 'msg' => '讲师id不能为空'];
            }
            //教务id
            if(isset($data['senate_id'])){
                //教师id和课次关联
                $data['teacher_id'] = implode(",",$data['senate_id']).",".$data['teacher_id'];
                unset($data['senate_id']);
                $res = explode(",", $data['teacher_id']);
                foreach($res as $k => $v){
                    $data[$k]['class_id'] = $data['class_id'];
                    $data[$k]['teacher_id'] = $v;
                    $data[$k]['create_at'] = date('Y-m-d H:i:s');
                    $data[$k]['update_at'] = date('Y-m-d H:i:s');
                }
                unset($data['class_id']);
                unset($data['teacher_id']);
            }else{
                $data['create_at'] = date('Y-m-d H:i:s');
                $data['update_at'] = date('Y-m-d H:i:s');
            }
            //获取后端的操作员id
            $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
            $add = LiveClassChildTeacher::insert($data);
            if($add){
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id,
                    'module_name'    =>  'LiveClassChildTeacher' ,
                    'route_url'      =>  'admin/teacherLiveChild' ,
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
        //发布课次到欢拓
        public static function creationLiveClassChild($data){
            //课次id
            if(empty($data['class_id']) || !isset($data['class_id'])){
                return ['code' => 201 , 'msg' => '课次id不能为空'];
            }
            //查询该课次进行发布到欢拓
            $one = self::join('ld_course_class_teacher', 'ld_course_class_teacher.class_id', '=', 'ld_course_class_number.id')
                        ->join('ld_lecturer_educationa', 'ld_lecturer_educationa.id', '=', 'ld_course_class_teacher.teacher_id')
                        ->select(['*','ld_course_class_number.id'])
                        ->where(['ld_course_class_number.id'=>$data['class_id'],'ld_course_class_number.is_del'=>0,'ld_course_class_number.status'=>0,'ld_lecturer_educationa.type'=>2])
                        ->first();
            if(!$one){
                return ['code' => 204 , 'msg' => '参数不正确'];
            }
            $MTCloud = new MTCloud();
            $res = $MTCloud->courseAdd(
                $course_name = $one['name'],
                $account   = $one['teacher_id'],
                $start_time = date("Y-m-d H:i:s",$one['start_at']),
                $end_time   = date("Y-m-d H:i:s",$one['end_at']),
                $nickname   = $one['real_name']
            );
            if($res['code'] == 0){
                //更新发布状态
                $update = self::where(['id'=>$data['class_id'],'status'=>0])->update(['status'=>1,'update_at'=>date('Y-m-d H:i:s')]);
                if($update){
                    //获取后端的操作员id
                    $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                    //添加日志操作
                    AdminLog::insertAdminLog([
                        'admin_id'       =>   $admin_id  ,
                        'module_name'    =>  'LiveClassChild' ,
                        'route_url'      =>  'admin/updateStatusLiveChild' ,
                        'operate_method' =>  'update' ,
                        'content'        =>  '更新id为'.$data['class_id'],
                        'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                        'create_at'      =>  date('Y-m-d H:i:s')
                    ]);
                    //课次关联表添加数据
                    $insert['class_id'] = $data['class_id'];
                    $insert['admin_id'] = $admin_id;
                    $insert['course_name'] = $res['data']['course_name'];
                    $insert['account'] = $res['data']['partner_id'];
                    $insert['start_time'] = $res['data']['start_time'];
                    $insert['end_time'] = $res['data']['end_time'];
                    $insert['nickname'] = $one['real_name'];
                    $insert['accountIntro'] = $one['describe'];
                    $insert['partner_id'] = $res['data']['partner_id'];
                    $insert['bid'] = $res['data']['bid'];
                    $insert['course_id'] = $res['data']['course_id'];
                    $insert['zhubo_key'] = $res['data']['zhubo_key'];
                    $insert['admin_key'] = $res['data']['admin_key'];
                    $insert['user_key'] = $res['data']['user_key'];
                    $insert['add_time'] = $res['data']['add_time'];
                    $insert['status'] = 1;
                    $insert['create_at'] = date('Y-m-d H:i:s');
                    $insert['update_at'] = date('Y-m-d H:i:s');
                    $add = CourseLiveClassChild::insert($insert);
                    if($add){
                        //添加日志操作
                        AdminLog::insertAdminLog([
                            'admin_id'       =>   $insert['admin_id']  ,
                            'module_name'    =>  'LiveClassChild' ,
                            'route_url'      =>  'admin/liveChild/add' ,
                            'operate_method' =>  'insert' ,
                            'content'        =>  '新增数据'.json_encode($data) ,
                            'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                            'create_at'      =>  date('Y-m-d H:i:s')
                        ]);
                        return ['code' => 200 , 'msg' => '发布成功'];
                    }else{
                        return ['code' => 202 , 'msg' => '发布失败'];
                    }

                }else{
                    return ['code' => 202 , 'msg' => '更新发布状态失败'];
                }

            }else{
                return ['code' => 204 , 'msg' => '欢拓参数不正确'];
            }
        }


        //添加班号课次资料
        public static function uploadLiveClassChild($data){
            //课次id
            if(empty($data['parent_id']) || !isset($data['parent_id'])){
                return ['code' => 201 , 'msg' => '课次id不能为空'];
            }
            //资料类型
            if(empty($data['type']) || !isset($data['type'])){
                return ['code' => 201 , 'msg' => '资料类型不能为空'];
            }
            //资料的名称
            if(empty($data['material_name']) || !isset($data['material_name'])){
                return ['code' => 201 , 'msg' => '资料的名称不能为空'];
            }
            //资料的大小
            if(empty($data['material_size']) || !isset($data['material_size'])){
                return ['code' => 201 , 'msg' => '资料的大小不能为空'];
            }
            //资料的url
            if(empty($data['material_url']) || !isset($data['material_url'])){
                return ['code' => 201 , 'msg' => '资料的url不能为空'];
            }
            $data['mold'] = 3;
            //缓存查出用户id和分校id
            $data['school_id'] = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            $data['admin_id'] = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;

            $data['create_at'] = date('Y-m-d H:i:s');
            $data['update_at'] = date('Y-m-d H:i:s');
            $add = CourseMaterial::insert($data);
            if($add){
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $data['admin_id']  ,
                    'module_name'    =>  'LiveClassChildMaterial' ,
                    'route_url'      =>  'admin/uploadLiveClassChild' ,
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
        //获取课次资源列表
        public static function getLiveClassMaterial($data){
            //课次id
            if(empty($data['parent_id']) || !isset($data['parent_id'])){
                return ['code' => 201 , 'msg' => '课次id不能为空'];
            }
            $total = CourseMaterial::where(['is_del'=>0,'parent_id'=>$data['parent_id'],'mold'=>3])->get()->count();
            if($total > 0){
                $list = CourseMaterial::where(['is_del'=>0,'parent_id'=>$data['parent_id'],'mold'=>3])->get();
                return ['code' => 200 , 'msg' => '获取课次资料列表成功' , 'data' => ['LiveClass_list_child_Material' => $list]];
            }else{
                return ['code' => 200 , 'msg' => '获取课次资料列表成功' , 'data' => ['LiveClass_list_child_Material' => []]];
            }
        }
        //删除课次资料
        public static function deleteLiveClassMaterial($data){
            //资料id
            if(empty($data['id']) || !isset($data['id'])){
                return ['code' => 201 , 'msg' => '资料id不能为空'];
            }
            $update = CourseMaterial::where(['id'=>$data['id'],'mold'=>3])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
            if($update){
                //获取后端的操作员id
                $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
                //添加日志操作
                AdminLog::insertAdminLog([
                    'admin_id'       =>   $admin_id  ,
                    'module_name'    =>  'LiveClassChildMaterial' ,
                    'route_url'      =>  'admin/deleteLiveClassChildMaterial' ,
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


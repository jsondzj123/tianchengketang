<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Constraint\ExceptionMessage;

class CourseLiveResource extends Model {
    //指定别的表名
    public $table = 'ld_course_live_resource';
    //时间戳设置
    public $timestamps = false;
    //直播详情  szw
    public static function selectFind($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['course_id']) || empty($data['course_id'])){
            return ['code' => 201 , 'msg' => '课程id不能为空'];
        }
        //课程信息
        $course = Coures::select('id','title','sale_price','status')->where(['id'=>$data['course_id'],'is_del'=>0])->first();
        if(!$course){
            return ['code' => 201 , 'msg' => '课程无效'];
        }
        //取直播资源列表
        $where['is_del'] = 0;
        if(isset($data['parent']) && !empty($data['parent'])){
            $parent = json_decode($data['parent'],true);
            if(isset($parent[0]) && !empty($parent[0])){
                $where['parent_id'] = $parent[0];
            }
            if(isset($parent[1]) && !empty($parent[1])){
                $where['child_id'] = $parent[1];
            }
        }
        $livecast = Live::where($where)->where('is_forbid','<',2)->orderByDesc('id')->get()->toArray();
        foreach ($livecast as $k=>&$v){
            $ones = CouresSubject::where('id',$v['parent_id'])->first();
            $v['parent_name'] = $ones['subject_name'];
            $twos = CouresSubject::where('id',$v['child_id'])->first();
            $v['chind_name'] = $twos['subject_name'];
        }
        //已经加入的直播资源
        $existLive = self::select('ld_course_livecast_resource.*')
            ->leftJoin('ld_course_livecast_resource','ld_course_livecast_resource.id','=','ld_course_live_resource.resource_id')
            ->where(['ld_course_live_resource.is_del'=>0,'ld_course_livecast_resource.is_del'=>0,'ld_course_live_resource.course_id'=>$data['course_id']])
            ->orderByDesc('ld_course_live_resource.id')->get()->toArray();
        //加入课程总数
        $count = self::leftJoin('ld_course_livecast_resource','ld_course_livecast_resource.id','=','ld_course_live_resource.resource_id')
            ->where(['ld_course_live_resource.is_del'=>0,'ld_course_livecast_resource.is_del'=>0])->count();
        $res=[];
        if(empty($existLive)){
            $res  = $livecast;
        }else{
            foreach($existLive as $item) $tmpArr[$item['id']] = $item;
            foreach($livecast as $v) if(! isset($tmpArr[$v['id']])) $res[] = $v;
        }
        return ['code' => 200 , 'msg' => '获取成功','course'=>$course,'where'=>$data,'livecast'=>$res,'existlive'=>$existLive,'count'=>$count];
    }
    //删除直播资源  szw
    public static function delLiveCourse($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '资源id不能为空'];
        }
        $livecourse = Live::where(['id'=>$data['id']])->first();
        if($livecourse['nature'] == 1){
            return ['code' => 202 , 'msg' => '此课程单元为授权课程资源，如需删除请联系系统管理员'];
        }
        $care = Couresliveresource::where(['resource_id'=>$data['id'],'is_del'=>0])->get()->toArray();
        if(!empty($care)){
            return ['code' => 202 , 'msg' => '此课程单元已有被关联的课程,取消关联后删除班号'];
        }
        $del = Live::where(['id'=>$data['id']])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
        if($del){
            $user_id = AdminLog::getAdminInfo()->admin_user->id;
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'delLiveCourse' ,
                'route_url'      =>  'admin/Course/delLiveCourse' ,
                'operate_method' =>  'del' ,
                'content'        =>  '删除直播资源操作'.json_encode($data) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return ['code' => 200 , 'msg' => '删除成功'];
        }else{
            return ['code' => 202 , 'msg' => '删除失败'];
        }
    }
    //修改直播资源 szw
    public static function upLiveCourse($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '资源id不能为空'];
        }
        unset($data['/admin/course/liveCoursesUp']);
        $data['update_at'] = date('Y-m-d H:i:s');
        $up = Live::where(['id'=>$data['id']])->update($data);
        if($up){
            $user_id = AdminLog::getAdminInfo()->admin_user->id;
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'upLiveCourse' ,
                'route_url'      =>  'admin/Course/upLiveCourse' ,
                'operate_method' =>  'update' ,
                'content'        =>  '修改直播资源信息操作'.json_encode($data) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return ['code' => 200 , 'msg' => '修改成功'];
        }else{
            return ['code' => 202 , 'msg' => '修改失败'];
        }
    }
    //课程取消或关联直播资源 szw
    public static function liveToCourse($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '资源id不能为空'];
        }
        if(!isset($data['course_id']) || empty($data['course_id'])){
            return ['code' => 201 , 'msg' => '课程id不能为空'];
        }
        $resource = json_decode($data['id'],true);
        if(!empty($resource)){
            $glarr = self::where(['course_id'=>$data['course_id'],'is_del'=>0])->get();
            foreach ($glarr as $k=>$v){
                self::where(['id'=>$v['id']])->update(['is_del'=>1]);
                $findv = self::where(['is_del'=>0,'resource_id'=>$v['resource_id']])->count();
                if($findv <= 0){
                    Live::where(['id'=>$v['resource_id']])->update(['is_forbid'=>0,'update_at'=>date('Y-m-d H:i:s')]);
                }
            }
            foreach ($resource as $k=>$v){
                $resourceones = self::where(['course_id'=>$data['course_id'],'resource_id'=>$v])->first();
                if($resourceones){
                    self::where(['course_id'=>$data['course_id'],'resource_id'=>$v])->update(['is_del'=>0]);
                    Live::where(['id'=>$v])->update(['is_forbid'=>1,'update_at'=>date('Y-m-d H:i:s')]);
                }else{
                    $classid = CourseShiftNo::where(['resource_id'=>$v,'is_del'=>0,'is_forbid'=>0])->first();
                    if(!empty($classid)){
                        self::insert([
                            'resource_id' => $v,
                            'course_id' => $data['course_id'],
                            'shift_id' => $classid['id']
                        ]);
                    }else{
                        self::insert([
                            'resource_id' => $v,
                            'course_id' => $data['course_id'],
                            'shift_id' => 0
                        ]);
                    }
                }
            }
        }
        $user_id = AdminLog::getAdminInfo()->admin_user->id;
        //添加日志操作
        AdminLog::insertAdminLog([
            'admin_id'       =>   $user_id  ,
            'module_name'    =>  'liveToCourse' ,
            'route_url'      =>  'admin/Course/liveToCourse' ,
            'operate_method' =>  'update' ,
            'content'        =>  '课程与直播资源关联操作'.json_encode($data) ,
            'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
            'create_at'      =>  date('Y-m-d H:i:s')
        ]);
        return ['code' => 200 , 'msg' => '操作成功'];
    }
}


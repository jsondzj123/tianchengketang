<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Couresliveresource extends Model {
    //指定别的表名
    public $table = 'ld_course_live_resource';
    //时间戳设置
    public $timestamps = false;
    //直播详情
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
        $where['is_forbid'] = '< 2';
        isset($data['child_id'])?$where['child_id'] = $data['child_id']:'';
        $livecast = Coureslivecastresource::where($where)->orderByDesc('id')->get()->toArray();
        //已经加入的直播资源
        $existLive = self::select('ld_course_livecast_resource.*')
            ->leftJoin('ld_course_livecast_resource','ld_course_livecast_resource.id','=','ld_course_live_resource.resource_id')
            ->where(['ld_course_live_resource.is_del'=>0,'ld_course_livecast_resource.is_del'=>0])
            ->orderByDesc('ld_course_live_resource.id')->get()->toArray();
        //加入课程总数
        $count = self::leftJoin('ld_course_livecast_resource','ld_course_livecast_resource.id','=','ld_course_live_resource.resource_id')
            ->where(['ld_course_live_resource.is_del'=>0,'ld_course_livecast_resource.is_del'=>0])->count();
        $res=[];
        foreach($existLive as $item) $tmpArr[$item['id']] = $item;
        foreach($livecast as $v) if(! isset($tmpArr[$v['id']])) $res[] = $v;
        return ['code' => 200 , 'msg' => '获取成功','course'=>$course,'where'=>$data,'livecast'=>$res,'existlive'=>$existLive,'count'=>$count];
    }
    //删除直播资源
    public static function delLiveCourse($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '资源id不能为空'];
        }
        $livecourse = Coureslivecastresource::where(['id'=>$data['id']])->first();
        if($livecourse['nature'] == 1){
            return ['code' => 202 , 'msg' => '此课程单元为授权课程资源，如需删除请联系系统管理员'];
        }
        $care = Couresliveresource::where(['resource_id'=>$data['id'],'is_del'=>0])->get()->toArray();
        if(!empty($care)){
            return ['code' => 202 , 'msg' => '此课程单元已有被关联的课程,取消关联后删除班号'];
        }
        $del = Coureslivecastresource::where(['id'=>$data['id']])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
        if($del){
            return ['code' => 200 , 'msg' => '删除成功'];
        }else{
            return ['code' => 202 , 'msg' => '删除失败'];
        }
    }
    //修改直播资源
    public static function upLiveCourse($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '资源id不能为空'];
        }
        $data['update_at'] = date('Y-m-d H:i:s');
        $up = Coureslivecastresource::where(['id'=>$data['id']])->update($data);
        if($up){
            return ['code' => 200 , 'msg' => '修改成功'];
        }else{
            return ['code' => 202 , 'msg' => '修改失败'];
        }
    }
    //课程取消或关联直播资源
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
        $course_live_resource = Couresliveresource::where(['resource_id'=>$data['id'],'course_id'=>$data['course_id']])->first();
        if(!empty($course_live_resource)){
            $del = $course_live_resource['is_del'] == 1?0:1;
            $up = Couresliveresource::where(['resource_id'=>$data['id'],'course_id'=>$data['course_id']])->update(['is_del'=>$del,'update_at'=>date('Y-m-d H:i:s')]);
        }else{
            $up = Couresliveresource::insert([
                'resource_id' => $data['id'],
                'course_id' => $data['course_id']
            ]);
        }
        if($up){
            return ['code' => 200 , 'msg' => '操作成功'];
        }else{
            return ['code' => 202 , 'msg' => '操作失败'];
        }
    }
}

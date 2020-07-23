<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\Tools\MTCloud;
use Log;
use App\Models\Lesson;
use App\Models\LessonLive;
use App\Models\LiveChild;
use App\Models\CourseLiveClassChild;
use App\Models\Video;

class LiveChildController extends Controller {



    //课程直播目录
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
           'lesson_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $lives = Lesson::join("ld_course_live_resource","ld_course.id","=","ld_course_live_resource.course_id")
        ->join("ld_course_livecast_resource","ld_course_live_resource.resource_id","=","ld_course_livecast_resource.id")
        ->join("ld_course_shift_no","ld_course_livecast_resource.id","=","ld_course_shift_no.resource_id")
        ->select(['ld_course_livecast_resource.id as resource_id','ld_course_shift_no.id as shift_no_id'])
        ->where(["ld_course.status"=>1,"ld_course.is_del"=>0,'ld_course.id'=>$request->input('lesson_id')])->get();
        //获取班号
        //获取班号下所有课次'
        $childs = [];
        if(!empty($lives) && count($lives) > 0){
            foreach ($lives as $key => $value) {
                //直播中
                $live = LiveChild::join("ld_course_live_childs","ld_course_class_number.id","=","ld_course_live_childs.class_id")
                ->join("ld_course_shift_no","ld_course_class_number.shift_no_id","=","ld_course_shift_no.id")
                ->select('ld_course_class_number.id', 'ld_course_class_number.name as course_name', 'ld_course_class_number.start_at as start_time', 'ld_course_class_number.end_at as end_time', 'ld_course_live_childs.course_id', 'ld_course_live_childs.status','ld_course_shift_no.name as class_name')->where([
                    'ld_course_live_childs.is_del' => 0, 'ld_course_live_childs.is_forbid' => 0, 'ld_course_live_childs.status' => 2
                ])->get();
                //预告
                $advance = LiveChild::join("ld_course_live_childs","ld_course_class_number.id","=","ld_course_live_childs.class_id")
                ->join("ld_course_shift_no","ld_course_class_number.shift_no_id","=","ld_course_shift_no.id")
                ->select('ld_course_class_number.id', 'ld_course_class_number.name as course_name', 'ld_course_class_number.start_at as start_time', 'ld_course_class_number.end_at as end_time', 'ld_course_live_childs.course_id', 'ld_course_live_childs.status','ld_course_shift_no.name as class_name')->where([
                    'ld_course_live_childs.is_del' => 0, 'ld_course_live_childs.is_forbid' => 0, 'ld_course_live_childs.status' => 1
                ])->get();
                //回放
                $playback = LiveChild::join("ld_course_live_childs","ld_course_class_number.id","=","ld_course_live_childs.class_id")
                ->join("ld_course_shift_no","ld_course_class_number.shift_no_id","=","ld_course_shift_no.id")
                ->select('ld_course_class_number.id', 'ld_course_class_number.name as course_name', 'ld_course_class_number.start_at as start_time', 'ld_course_class_number.end_at as end_time', 'ld_course_live_childs.course_id', 'ld_course_live_childs.status','ld_course_shift_no.name as class_name')->where([
                    'ld_course_live_childs.is_del' => 0, 'ld_course_live_childs.is_forbid' => 0, 'ld_course_live_childs.status' => 3
                ])->get();
            }
            if(!empty($live->toArray())){
            array_push($childs, [
                    'title' => '正在播放',
                    'data'  => $live,
                ]);
            }
            if(!empty($advance->toArray())){
                array_push($childs, [
                        'title' => '播放预告',
                        'data'  => $advance,
                    ]);
            }
            if(!empty($playback->toArray())){
                array_push($childs, [
                        'title' => '历史课程',
                        'data'  => $playback,
                    ]);
            }

        }
        foreach($childs as $k => $v){
            foreach($v['data'] as $kk =>$vv){
                $vv['start_time']  = date("Y:m:d H:i:s",$vv['start_time']);
                $vv['end_time']  = date("Y:m:d H:i:s",$vv['end_time']);
            }
        }
        return $this->response($childs);
    }




    //进入直播课程
    public function courseAccess(Request $request)
    {
        $validator = Validator::make($request->all(), [
           'course_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $course_id = $request->input('course_id');
        $student_id = self::$accept_data['user_info']['user_id'];
        if(empty(self::$accept_data['user_info']['nickname'])){
            $nickname = self::$accept_data['user_info']['real_name'];
        }else{
            $nickname = self::$accept_data['user_info']['nickname'];
        }
        $MTCloud = new MTCloud();
        $liveChild = CourseLiveClassChild::where('course_id', $course_id)->first();
        $video = Video::where('course_id', $course_id)->first();
        if(empty($liveChild) && empty($video)){
            return $this->response('course_id不存在', 202);
        }
        if(!empty($liveChild)){
            if($liveChild->status == 2){
                 $res = $MTCloud->courseAccess($course_id, $student_id, $nickname, 'user');
                 $res['data']['is_live'] = 1;
            }elseif($liveChild->status == 3 && $liveChild->playback == 1){
                $res = $MTCloud->courseAccessPlayback($course_id, $student_id, $nickname, 'user');
                $res['data']['is_live'] = 0;
            }else{
                return $this->response('不是进行中的直播', 202);
            }
        }
        if(!empty($video)){
            $res = $MTCloud->courseAccessPlayback($course_id, $student_id, $nickname, 'user');
            $res['data']['is_live'] = 0;
        }

        if(!array_key_exists('code', $res) && !$res['code'] == 0){
            Log::error('进入直播间失败:'.json_encode($res));
            return $this->response('进入直播间失败', 500);
        }
        return $this->response($res['data']);
    }
}

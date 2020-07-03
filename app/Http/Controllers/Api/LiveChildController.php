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
        ->select(['ld_course_livecast_resource.id as resource_id'])
        ->where(["ld_course.status"=>1,"ld_course.is_del"=>0,'ld_course.id'=>$request->input('course_id')])->get();
        //获取班号
        //获取班号下所有课次
        $childs = [];
        if(!empty($lives)){
            foreach ($lives as $key => $value) {
                //直播中
                $live = LiveChild::select('id', 'course_name', 'start_time', 'end_time', 'course_id', 'status')->where([
                    'is_del' => 0, 'is_forbid' => 0, 'status' => 2, 'live_id' => $value['id']
                ])->get();
                //预告
                $advance = LiveChild::select('id', 'course_name', 'start_time', 'end_time', 'course_id', 'status')->where([
                    'is_del' => 0, 'is_forbid' => 0, 'status' => 1, 'live_id' => $value['id']
                ])->get();
                //回放
                $playback = LiveChild::select('id', 'course_name', 'start_time', 'end_time', 'course_id', 'status')->where([
                    'is_del' => 0, 'is_forbid' => 0, 'status' => 3, 'live_id' => $value['id']
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
        $nickname = self::$accept_data['user_info']['nickname'];
        $MTCloud = new MTCloud();
        $liveChild = LiveChild::where('course_id', $course_id)->first();
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

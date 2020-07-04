<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LessonChild;
use App\Models\LessonVideo;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Tools\MTCloud;
use Validator;
use Illuminate\Support\Facades\Redis;

class LessonChildController extends Controller {

    /**
     * @param  小节列表
     * @param  pagesize   page
     * @param  author  孙晓丽
     * @param  ctime   2020/5/26
     * @return  array
     */
    public function index(Request $request){
        $validator = Validator::make($request->all(), [
            'course_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $course_id = $request->input('course_id');
        if(isset(self::$accept_data['user_token']) && !empty(self::$accept_data['user_token'])){
            //判断token值是否合法
            $redis_token = Redis::hLen("user:regtoken:".self::$accept_data['user_token']);
                if($redis_token && $redis_token > 0) {
                    //通过token获取用户信息
                    $json_info = Redis::hGetAll("user:regtoken:".self::$accept_data['user_token']);
                    $uid       = $json_info['user_id'];
                } else {
                    return $this->response('请登录账号', 401);
                }

        }

        $pid = $request->input('pid') ?: 0;
        $lessons =  LessonChild::select('id', 'name', 'description', 'pid')
                ->where(['is_del'=> 0, 'is_forbid' => 0, 'pid' => 0, 'course_id' => $course_id])
                ->orderBy('created_at', 'desc')->get();
        foreach ($lessons as $key => $value) {
            $lesson = LessonChild::with(['videos' => function ($query) {
                    $query->select('id', 'course_id', 'mt_duration');
                }])
                ->where(['is_del'=> 0, 'is_forbid' => 0, 'pid' => $value->id, 'course_id' => $course_id])->get();

            foreach ($lesson as $k => $v) {
                $arr_v = $v->toArray();
                if(!empty($arr_v) && !empty($arr_v['videos'])){
                    $videos = $arr_v['videos'];
                    $v['course_id'] = $videos[0]['course_id'];
                    $v['mt_duration'] = $videos[0]['mt_duration'];
                }else{
                    $v['course_id'] = 0;
                    $v['mt_duration'] = 0;
                }
                unset($v['videos']);
                $v['use_duration']  =  "未学习";
                //获取用户使用课程时长
                if(isset(self::$accept_data['user_token']) && !empty(self::$accept_data['user_token'])){
                    $course_id = $v['course_id'];
                    $MTCloud = new MTCloud();
                    $v['use_duration']  =  $MTCloud->coursePlaybackVisitorList($course_id,1,50)['data'];
                }
            }
            $value['childs'] = $lesson;

            if(isset(self::$accept_data['user_token']) && !empty(self::$accept_data['user_token'])){
                foreach($value['childs'] as $k => $v){
                    if(count($v['use_duration']) > 0){
                        foreach($v['use_duration'] as $k => $vv){
                            if($vv['uid'] == $uid){
                                $v['use_duration'] = $vv['duration'];
                            }else{
                                if(is_array($v['use_duration'])){
                                    $v['use_duration'] = 0;
                                }
                            }
                        }
                    }else{
                        $value['childs'][$k]['use_duration'] = 0;
                    }

                }
            }


        }

        foreach($lessons as $k => $v){
                foreach($v['childs'] as $k1 =>$vv){
                    if($vv['use_duration'] == 0){
                        $vv['use_duration'] = "未学习";
                    }else{
                        $vv['use_duration'] =  "已学习".  sprintf("%01.2f", $vv['use_duration']/$vv['mt_duration']*100).'%';;
                    }
                    $seconds = $vv['mt_duration'];
                    $hours = intval($seconds/3600);
                    $vv['mt_duration'] = $hours.":".gmdate('i:s', $seconds);
                }

            }

        return $this->response($lessons);
    }


    /**
     * @param  小节详情
     * @param  课程id
     * @param  author  孙晓丽
     * @param  ctime   2020/5/1
     * return  array
     */
    public function show(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $lesson = LessonChild::with(['lives' => function ($query) {
                $query->with('childs');
            }])->find($request->input('id'));
        if(empty($lesson)){
            return $this->response('课程小节不存在', 404);
        }
        return $this->response($lesson);
    }
}

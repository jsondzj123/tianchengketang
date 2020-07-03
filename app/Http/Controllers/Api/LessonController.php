<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;
use App\Tools\MTCloud;
use DB;
use Validator;

class LessonController extends Controller {

    /**
     * @param  课程列表
     * @param  current_count   count
     * @param  author  孙晓丽
     * @param  ctime   2020/5/1
     * return  array
     */
    public function index(Request $request){
        $pagesize = $request->input('pagesize') ?: 15;
        $page     = $request->input('page') ?: 1;
        $offset   = ($page - 1) * $pagesize;
        $subject_id = $request->input('subject_id') ?: 0;
        $child_id = $request->input('child_id') ?: 0;
        if($subject_id == 0 && $child_id == 0){
            $subjectId = 0;
        }elseif($subject_id != 0 && $child_id == 0){
            $subjectId = $subject_id;
        }elseif($subject_id != 0 && $child_id != 0){
            $subjectId = $child_id;
        }elseif($subject_id == 0 && $child_id != 0){
            $subjectId = $subject_id;
        }
        $keyWord = $request->input('keyword') ?: 0;
        $method = $request->input('method_id') ?: 0;
        $sort = $request->input('sort_id') ?: 0;
        if($sort == 0){
            $sort_name = 'created_at';
        }elseif($sort == 1){
            $sort_name = 'watch_num';
        }elseif($sort == 2){
            $sort_name = 'price';
        }elseif($sort == 3){
            $sort_name = 'price';
        }
        $where = ['is_del'=> 0, 'is_forbid' => 0, 'status' => 2];
        $sort_type = $request->input('sort_type') ?: 'asc';
        $data =  Lesson::with('subjects', 'methods')
                ->select('id', 'admin_id', 'title', 'cover', 'price', 'favorable_price', 'buy_num', 'status', 'is_del', 'is_forbid')
                ->where(['is_del'=> 0, 'is_forbid' => 0, 'status' => 2])
                ->orderBy($sort_name, $sort_type)
                ->whereHas('subjects', function ($query) use ($subjectId)
                {
                    if($subjectId != 0){
                        $query->where('id', $subjectId);
                    }
                })
                ->whereHas('methods', function ($query) use ($method)
                {
                    if($method != 0){
                        $query->where('id', $method);
                    }
                })
                ->where(function($query) use ($keyWord){
                    if(!empty($keyWord)){
                        $query->where('title', 'like', '%'.$keyWord.'%');
                    }
                })
                ->skip($offset)->take($pagesize)->get();
        $lessons = [];
        foreach ($data as $value) {
            if($value['is_auth'] == 1 || $value['is_auth'] == 2){
                $lessons[] = $value;
            }
        }
        $total = count($lessons);
        $lessons = $lessons;
        $data = [
            'page_data' => $lessons,
            'total' => $total,
        ];
        return $this->response($data);
    }


    /**
     * @param  课程详情
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
        $lesson = Lesson::find($request->input('id'));
        if(empty($lesson)){
            return $this->response('课程不存在', 404);
        }
        //Lesson::where('id', $request->input('id'))->update(['watch_num' => DB::raw('watch_num + 1')]);
        return $this->response($lesson);
    }


    /**
     * @param  公开课
     * @param  author  zzk
     * @param  ctime   2020/6/16
     * return  array
     */
    public function OpenCourse(Request $request) {
        $course_id = $request->input('course_id');
        $student_id = self::$accept_data['user_info']['user_id'];
        $nickname = self::$accept_data['user_info']['nickname'];
        if(empty($course_id)){
            return $this->response('course_id错误', 202);
        }
        if(empty($student_id)){
            return $this->response('student_id不存在', 202);
        }
        if(empty($nickname)){
            return $this->response('nickname不存在', 202);
        }
        $MTCloud = new MTCloud();

        $res = $MTCloud->courseAccessPlayback($course_id = "737835", $student_id, $nickname, 'user');
        if(!array_key_exists('code', $res) && !$res['code'] == 0){
            Log::error('进入直播间失败:'.json_encode($res));
            return $this->response('进入直播间失败', 500);
        }
        $res['data']['course_id'] = $course_id;
        $res['data']['is_live'] = 0;
        return $this->response($res['data']);
    }
}

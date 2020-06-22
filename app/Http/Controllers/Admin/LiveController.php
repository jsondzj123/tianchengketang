<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Live;
use App\Models\Subject;
use Illuminate\Http\Request;
use  App\Tools\CurrentAdmin;
use Validator;
use App\Tools\MTCloud;
use App\Models\LessonLive;
use App\Models\Lesson;
use App\Models\LessonChild;
use App\Models\LiveClassChild;
use App\Models\LiveChild;
use App\Models\Teacher;

class LiveController extends Controller {

    /*
     * @param  课程关联的直播列表
     * @param  current_count   count
     * @param  author  孙晓丽
     * @param  ctime   2020/6/14
     * return  array
     */
    public function lessonRelatedLive(Request $request){
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $lesson_id = $request->input('lesson_id');
        $data = Live::with('lessons')->select('id', 'admin_id', 'name', 'created_at')
                ->with(['class' => function ($query) {
                    $query->where(['is_del' => 0, 'is_forbid' => 0])->select('id', 'name', 'live_id');
                }])
                ->where('is_del', 0)
                ->orderBy('created_at', 'desc')
                ->whereHas('lessons', function ($query) use ($lesson_id)
                    {
                        $query->where('id', $lesson_id);
                    });
        $total = $data->count();
        $live = $data->orderBy('created_at', 'desc')->get();
        $lives = [];
        foreach ($live as $key => $value) {
            $lives[$key]['name'] = $value['name'];
            $lives[$key]['class'] = $value['class'];
        }
        $data = [
            'page_data' => $lives,
            'total' => $total,
        ];
        return $this->response($data);
    }

    /*
     * @param  直播列表
     * @param  current_count   count
     * @param  author  孙晓丽
     * @param  ctime   2020/5/18 
     * return  array
     */
    public function index(Request $request){
        $pagesize = $request->input('pagesize') ?: 15;
        $page     = $request->input('page') ?: 1;
        $offset   = ($page - 1) * $pagesize;
        $subject_id = $request->input('subject_id') ?: 0;
        $keyWord = $request->input('keyword') ?: 0;
        $data = Live::select('id', 'admin_id', 'name', 'created_at')->with('subjects')
                ->where('is_del', 0)
                ->orderBy('created_at', 'desc')
                ->whereHas('subjects', function ($query) use ($subject_id)
                    {
                       if($subject_id != 0){
                            $query->where('id', $subject_id);
                        }
                    })
                ->where(function($query) use ($keyWord){
                    if(!empty($keyWord)){
                        $query->where('name', 'like', '%'.$keyWord.'%');
                    }
                });
        $total = $data->count();
        $live = $data->orderBy('created_at', 'desc')
                ->skip($offset)->take($pagesize)
                ->get();
        $data = [
            'page_data' => $live,
            'total' => $total,
        ];
        return $this->response($data);
    }


    /*
     * @param  未删除和未禁用的直播列表
     * @param  pagesize   page
     * @param  author  孙晓丽
     * @param  ctime   2020/6/13 
     * return  array
     */
    public function list(Request $request){
        $subject_id = $request->input('subject_id') ?: 0;
        $keyWord = $request->input('keyword') ?: 0;
        $data = Live::select('id', 'admin_id', 'name', 'created_at')->with('subjects')
                ->where(['is_del' => 0, 'is_forbid' => 0])
                ->orderBy('created_at', 'desc')
                ->whereHas('subjects', function ($query) use ($subject_id)
                    {
                       if($subject_id != 0){
                            $query->where('id', $subject_id);
                        }
                    })
                ->where(function($query) use ($keyWord){
                    if(!empty($keyWord)){
                        $query->where('name', 'like', '%'.$keyWord.'%');
                    }
                });
        $total = $data->count();
        $lives = [];
        foreach ($data->get()->toArray() as $key => $value) {
            $lives[$key]['id'] =  $value['id'];
            $lives[$key]['name'] =  $value['name'];
            $lives[$key]['subject_id'] =  $value['subject_id'];
            $lives[$key]['subject_first_name'] =  $value['subject_first_name'];
            $lives[$key]['subject_second_name'] =  $value['subject_second_name'];
        }
        $data = [
            'page_data' => $lives,
            'total' => $total,
        ];
        return $this->response($data);
    }

    /*
     * @param  直播详情
     * @param  直播id
     * @param  author  孙晓丽
     * @param  ctime   2020/5/18 
     * return  array
     */
    public function show(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $live = Live::with('subjects')->findOrFail($request->input('id'));
        return $this->response($live);
    }


    /*
     * @param  班号列表
     * @param  直播id
     * @param  author  孙晓丽
     * @param  ctime   2020/5/18 
     * return  array
     */
    public function classList(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $lesson_id = LessonLive::where('live_id', $request->input('id'))->first()['lesson_id'];
        $lessons = LessonChild::select('id', 'name', 'description', 'url', 'is_forbid')->where(['lesson_id' => $lesson_id, 'pid' => 0])->get();
        foreach ($lessons as $key => $value) {
            $childs = LiveChild::select('id', 'course_name', 'start_time', 'end_time', 'account')->whereIn('id' ,LiveClassChild::where('lesson_child_id', $value->id)->pluck('live_child_id'))->get();
            foreach ($childs as $k => $val) {
                $childs[$k]['teacher_name'] = Teacher::find($val->account)->real_name;
            }
            $lessons[$key]['childs'] = $childs;
        }
        return $this->response($lessons);
    }


    /**
     * 添加直播资源.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required',
            'name' => 'required',
            'description' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $subjectIds = json_decode($request->input('subject_id'), true);
        $user = CurrentAdmin::user();
        try {
            $live = Live::create([
                        'admin_id' => intval($user->id),
                        'name' => $request->input('name'),
                        'description' => $request->input('description'),
                    ]);
            if(!empty($subjectIds)){
                $live->subjects()->attach($subjectIds);
            }
        } catch (Exception $e) {
            Log::error('创建失败:'.$e->getMessage());
            return $this->response($e->getMessage(), 500);
        }
        return $this->response('创建成功');
    }

    /**
     * 获取直播关联课程ID.
     *
     * @param  live_id
     * @return array
     */
    public function lessonId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'live_id' => 'required|json',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $live = Live::find($request->input('live_id'));
        if(empty($live)){
            return $this->response('直播资源不存在', 202);
        }
        try {
            $lessonIds = $live->lessons()->pluck('lesson_id');
        } catch (Exception $e) {
            Log::error('创建失败:'.$e->getMessage());
            return $this->response($e->getMessage(), 500);
        }
        return $this->response($lessonIds);
    }

    /**
     * 直播批量关联课程.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function lesson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'lesson_id' => 'required|json',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $lessonIds = json_decode($request->input('lesson_id'), true);
        try {
            $live = Live::find($request->input('id'));
            if(!empty($lessonIds)){
                $live->lessons()->attach($lessonIds); 
            }
        } catch (Exception $e) {
            Log::error('创建失败:'.$e->getMessage());
            return $this->response($e->getMessage(), 500);
        }
        return $this->response('创建成功');
    }


    /**
     * 修改直播资源
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $subjectIds = json_decode($request->input('subject_id'), true);
        try {
            $live = Live::findOrFail($request->input('id'));
            $live->name = $request->input('name') ?: $live->name;
            $live->description = $request->input('description') ?: $live->description;
            $live->save();
            $live->subjects()->sync($subjectIds);
            return $this->response("修改成功");
        } catch (Exception $e) {
            Log::error('修改课程信息失败' . $e->getMessage());
            return $this->response("修改成功");
        }
    }


    /**
     * 启用/禁用
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $live = Live::findOrFail($request->input('id'));
        if($live->is_forbid == 1){
            $live->is_forbid = 0;
        }else{
            $live->is_forbid = 1;
        }
        if (!$live->save()) {
            return $this->response("操作失败", 500);
        }
        return $this->response("操作成功");
    }

    /**
     * 删除
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $live = Live::findOrFail($request->input('id'));
        $live->is_del = 1;
        if (!$live->save()) {
            return $this->response("操作失败", 500);
        }
        return $this->response("操作成功");
    }
}

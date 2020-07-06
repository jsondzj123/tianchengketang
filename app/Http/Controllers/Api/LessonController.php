<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Tools\MTCloud;
use Illuminate\Support\Facades\DB;
use Validator;

class LessonController extends Controller {

    /**
     * @param  课程列表
     * @param  current_count   count
     * @param  author  zzk
     * @param  ctime   2020/7/3
     * return  array
     */
    public function index(Request $request){
        $pagesize = $request->input('pagesize') ?: 15;
        $page     = $request->input('page') ?: 1;
        $offset   = ($page - 1) * $pagesize;
        $parent_id = $request->input('parent_id') ?: 0;
        $child_id = $request->input('child_id') ?: 0;
        if($parent_id == 0 && $child_id == 0){
            $subjectId = 0;
        }elseif($parent_id != 0 && $child_id == 0){
            $subjectId = $parent_id;
        }elseif($parent_id != 0 && $child_id != 0){
            $subjectId = $child_id;
        }elseif($parent_id == 0 && $child_id != 0){
            $subjectId = $parent_id;
        }
        $keyWord = $request->input('keyword') ?: 0;
        $method = $request->input('method_id') ?: 0;
        $sort = $request->input('sort_id') ?: 0;
        if($sort == 0){
            $sort_name = 'ld_course.create_at';
        }elseif($sort == 1){
            $sort_name = 'ld_course.watch_num';
        }elseif($sort == 2){
            $sort_name = 'ld_course.pricing';
        }elseif($sort == 3){
            $sort_name = 'ld_course.pricing';
        }
        $where['ld_course.is_del'] = 0;
        $where['ld_course.status'] = 1;
        if($parent_id > 0){
            $where['ld_course.parent_id'] = $parent_id;
        }
        if($child_id > 0){
            $where['ld_course.child_id'] = $child_id;
        }
        if($method > 0){
            $where['ld_course_method.method_id'] = $method;
        }
        $sort_type = $request->input('sort_type') ?: 'asc';
        $data =  Lesson::leftjoin("ld_course_subject","ld_course_subject.id","=","ld_course.parent_id")
                ->join("ld_course_method","ld_course_method.course_id","=","ld_course.id")
                ->select('ld_course.id', 'ld_course.admin_id','ld_course.child_id','ld_course.parent_id', 'ld_course.title', 'ld_course.cover', 'ld_course.pricing', 'ld_course.sale_price','ld_course.buy_num','ld_course.is_del','ld_course.status','ld_course.watch_num','ld_course.keywords','ld_course_subject.subject_name')
                ->where($where)
                ->orWhere(function ($query) use ($keyWord){
                    $query->where('ld_course.title', 'like', '%'.$keyWord.'%')->orWhere('ld_course.keywords', 'like', '%'.$keyWord.'%');
                })
                ->orderBy($sort_name, $sort_type)
                ->skip($offset)->take($pagesize)->get();
        foreach($data as $k => $v){
            //二级分类
            $res = DB::table('ld_course_subject')->select('subject_name')->where(['id'=>$v['child_id']])->first();
            if(!empty($res)){
                $v['subject_child_name']   = $res->subject_name;
            }else{
                $v['subject_child_name']   = "无二级分类";
            }
            //购买数量
            $v['sold_num'] =  Order::where(['oa_status'=>1,'class_id'=>$v['id']])->count() + $v['buy_num'];
            //获取授课模式
            $v['methods'] = DB::table('ld_course')->select('method_id as id')->join("ld_course_method","ld_course.id","=","ld_course_method.course_id")->where(['ld_course.id'=>$v['id']])->get();
        }
        foreach($data as $k => $v){
            foreach($v['methods'] as $kk => $vv){
                if($vv->id == 1){
                    $vv->name = "直播";
                }else if($vv->id == 2){
                    $vv->name = "录播";
                }else{
                    $vv->name = "其他";
                }
            }
        }
        $total = count($data);
        $lessons = $data;
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
        $lesson = Lesson::select("*","pricing as price","sale_price as favorable_price","expiry as ttl","introduce as introduction","describe as description")->find($request->input('id'));
        if(empty($lesson)){
            return $this->response('课程不存在', 404);
        }
        //该课程为直播的时候所有的课时数
        //该课程下所有的
        // join('ld_course_class_number','ld_course_shift_no.id','=','ld_course_class_number.shift_no_id')
        // ->where("resource_id",$one['id'])->sum("class_hour");
        Lesson::where('id', $request->input('id'))->update(['watch_num' => DB::raw('watch_num + 1'),'update_at'=>date('Y-m-d H:i:s')]);
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

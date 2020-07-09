<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Models\Admin as Adminuser;
use App\Models\Roleauth;
use App\Models\Authrules;
use App\Models\School;
use Illuminate\Support\Facades\Redis;
use App\Tools\CurrentAdmin;
use Illuminate\Support\Facades\Validator;
use App\Models\AdminLog;
use Illuminate\Support\Facades\DB;
use App\Models\CourseSchool;
class CourseSchoolController extends Controller {

	

	/**
     * @param  授权课程ID
     * @param  school_id
     * @param  author  李银生
     * @param  ctime   2020/6/29 
     * @return  array  7.4 调整
     */
    public function courseIdList(){
    	$data = self::$accept_data;
    	$validator = Validator::make($data, 
        [
        	'school_id' => 'required|integer',
            'is_public' => 'required|integer',
       	],
        CourseSchool::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $result = CourseSchool::courseIds(self::$accept_data);
        return response()->json($result);
    }
    /**
     * @param  授权课程列表
     * @param  school_id
     * @param  author  李银生
     * @param  ctime   2020/6/30
     * @return  array  7.4 调整
     */
    public function courseList(){
        $validator = Validator::make(self::$accept_data, 
        [
            'school_id' => 'required|integer',
            'is_public' => 'required|integer' //是否为公开课  1公开课 0课程
        ],
        CourseSchool::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $result = CourseSchool::courseList(self::$accept_data);
        return response()->json($result);
    }
    /**
     * @param  批量授权添加课程
     * @param  school_id
     * @param  author  李银生
     * @param  ctime   2020/6/30
     * @return  array 7.4调整
     */
    public function store()
    { 
        $validator = Validator::make(self::$accept_data, 
        [
            'course_id' => 'required',
            'school_id' => 'required',
            'is_public' => 'required',
        ],
        CourseSchool::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }

        $result = CourseSchool::store(self::$accept_data);
        return response()->json($result);
    }
     /*
     * @param  description 授权课程列表学科大类
     * @param  参数说明       body包含以下参数[
     *      'id'=>学科id
     * ]
     * @param author    lys
     * @param ctime     2020-05-11
     */
     public function getNatureSubjectOneByid(){

            $validator = Validator::make(self::$accept_data, 
                [
                    'school_id' => 'required|integer',
                    'is_public'=> 'required|integer',
                ],
                CourseSchool::message());
            if ($validator->fails()) {
                return response()->json(json_decode($validator->errors()->first(),1));
            }
            $result = CourseSchool::getNatureSubjectOneByid(self::$accept_data);
            return response()->json($result);
    }
    /*
     * @param  description 授权课程列表学科小类
     * @param  参数说明       body包含以下参数[
     *      'subjectOne'=>学科id
     * ]
     * @param author    lys
     * @param ctime     2020-7-4
     */
     public function getNatureSubjectTwoByid(){
            $validator = Validator::make(self::$accept_data, 
                [
                    'subjectOne' => 'required',
                ],
                CourseSchool::message());
            if ($validator->fails()) {
                return response()->json(json_decode($validator->errors()->first(),1));
            }
            $result = CourseSchool::getNatureSubjectTwoByid(self::$accept_data);
            return response()->json($result);
    }
    //取消课程授权
    public function natureCourseDel(){
       $data = self::$accept_data; //课程id
       //学校id
       if($data['course_id']){
            $nature = CourseSchool::where(['from_school_id'=>$school_id,'course_id'=>$data['course_id'],'is_del'=>1])->get()->toArray();
            if(!empty($nature)){    
                foreach($nature as $key=>&$v){
                    $v['nature_buy_sum']  = Order::where(['school_id'=>$v['to_school_id'],'class_ld'=>$v['course_id']])->count();
                    $v['course_stocks_sum'] = CourseStocks::where(['school_id'=>$v['to_school_id'],'course_id'=>$v['course_id']])->sum('add_number'); 
                    $v['teacher'] = Couresteacher::where(['course_id'=>$v['course_id'],'is_del'=>1])->pluck('teacher_id')->toArray();
                    $v['lvboResource'] = CourseVideoResource::where(['school_id'=>$v['to_school_id'],'lesson_id'=>$v['course_id'],'is_del'=>0])->first(['id as lvboResourceId']);
                    $v['zhiboResource'] = CourseLiveResource::where(['course_id',$v['course_id']])->pluck('resource_id')->toArray();
                }
                foreach($nature as $key=>&$v){
                    if($v['nature_buy_sum']>=$v['course_stocks_sum']){
                        CourseRefteacher::where(['to_school_id'=>$v['to_school_id'],'is_public'=>0])->whereIn('teacher_id',$v['teacher'])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
                        CourseRefResource::where(['resource_id'=>$v['lvboResource'],'to_school_id'=>$v['to_school_id'],'is_type'=>2])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
                        CourseRefResource::where(['to_school_id'=>$v['to_school_id'],'is_type'=>1])->whereIn('resource_id',$v['zhiboResource'])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
                    }   
                }
            }   
       }
    }


}
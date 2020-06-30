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
     * @return  array
     */
    public function courseIdList(){
    	$data = self::$accept_data;
    	$validator = Validator::make($data, 
        [
        	'school_id' => 'required|integer',
       	],
        CourseSchool::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $courseIds = CourseSchool::where('school_id', $data['school_id'])->where('is_del',0)
                ->pluck('course_id');
        return $this->response($courseIds);
    }
    /**
     * @param  授权课程列表
     * @param  school_id
     * @param  author  李银生
     * @param  ctime   2020/6/30
     * @return  array
     */
    public function courseList(){
        $validator = Validator::make(self::$accept_data, 
        [
            'school_id' => 'required|integer',
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
     * @return  array
     */
    public function store()
    { 
        $validator = Validator::make(self::$accept_data, 
        [
            'course_id' => 'required',
            'school_id' => 'required',
        ],
        CourseSchool::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }

        $result = CourseSchool::store(self::$accept_data);
        return response()->json($result);

    }



}
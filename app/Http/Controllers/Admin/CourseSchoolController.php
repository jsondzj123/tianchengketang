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
        ],
        CourseSchool::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }

        $result = CourseSchool::store(self::$accept_data);
        return response()->json($result);
    }



}
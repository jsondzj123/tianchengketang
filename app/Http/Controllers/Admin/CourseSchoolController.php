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


}
<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Region;
use App\Models\School;
use App\Models\Student;

class UserController extends Controller {
    protected $school;
    protected $data;
    protected $userid;
    public function __construct(){
        $this->data = $_REQUEST;
        $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
        $this->userid = isset(AdminLog::getAdminInfo()->admin_user->id)?AdminLog::getAdminInfo()->admin_user->id:0;
    }
    /*
         * @param  个人信息
         * @param  author  苏振文
         * @param  ctime   2020/7/8 16:32
         * return  array
         */
    public function userDetail(){
        $user = Student::where(['id'=>39,'is_forbid'=>1])->first()->toArray();
        if(empty($user)){
            return response()->json(['code' => 201 , 'msg' => '成员不存在']);
        }
        unset($user['token']);
        unset($user['password']);
        //查询省
        if($user['province_id'] != ''){
            $province = Region::where(['id'=>$user['province_id']])->first();
            $user['province'] = $province['name'];
        }
        if($user['city_id'] != ''){
            $city = Region::where(['id'=>$user['city_id']])->first();
            $user['city'] = $city['name'];
        }
        return response()->json(['code' => 200 , 'msg' => '查询成功','data'=>$user]);
    }

}


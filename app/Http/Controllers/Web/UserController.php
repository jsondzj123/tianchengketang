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
        $user = Student::where(['id'=>$this->userid,'is_forbid'=>1])->first()->toArray();
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
    //用户修改基本信息
    public function userUpDetail(){

    }
    //用户修改联系方式
    public function userUpRelation(){

    }
    //用户更改手机号
    public function userUpPhone(){
        if(!isset($this->data['phone'])|| empty($this->data['phone'])){
            return response()->json(['code' => 201 , 'msg' => '手机号不能为空']);
        }
        $count = Student::where(['phone'=>$this->data['phone'],'is_forbid'=>1])->count();
        if($count >0){
            return response()->json(['code' => 201 , 'msg' => '手机号已被占用']);
        }
        if(!preg_match('#^13[\d]{9}$|^14[\d]{9}$|^15[\d]{9}$|^17[\d]{9}$|^18[\d]{9}|^16[\d]{9}$#', $this->data['phone'])) {
            return response()->json(['code' => 202 , 'msg' => '手机号不合法']);
        }
        $up = Student::where(['id'=>$this->userid])->update(['phone'=>$this->data['phone']]);
        if($up){

        }
    }
    //用户更改邮箱
    public function userUpEmail(){
        if(!isset($this->data['email']) || empty($this->data['email'])){
            return response()->json(['code' => 201 , 'msg' => '邮箱地址不能为空']);
        }
        if (filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
                $up = Student::where(['id'=>$this->userid])->update(['email'=>$this->data['email']]);
                if($up){
                    return response()->json(['code' => 200 , 'msg' => '修改成功']);
                }else{
                    return response()->json(['code' => 202 , 'msg' => '修改失败']);
                }
        }else{
            return response()->json(['code' => 201 , 'msg' => '邮箱格式不正确']);
        }
    }
    //用户修改头像
    public function userUpImg(){

    }
    //用户修改密码
    public function userUpPass(){

    }



}


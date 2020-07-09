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
            return response()->json(['code' => 200 , 'msg' => '修改成功']);
        }else{
            return response()->json(['code' => 203 , 'msg' => '修改失败']);
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

    //用户修改基本信息
    public function userUpDetail(){

    }
    //用户修改联系方式
    public function userUpRelation(){

    }

    //用户修改头像
    public function userUpImg(){
        if(!isset($this->data['head_icon']) || empty($this->data['head_icon'])){
            return response()->json(['code' => 201 , 'msg' => '头像为空']);
        }
        $up = Student::where(['id'=>$this->userid])->update(['user_icon'=>$this->data['head_icon']]);
        if($up){
            return response()->json(['code' => 200 , 'msg' => '修改成功']);
        }else{
            return response()->json(['code' => 203 , 'msg' => '修改失败']);
        }

    }
    //用户修改密码
    public function userUpPass(){
        if(!isset($this->data['old_pass']) || empty($this->data['old_pass'])){
            return response()->json(['code' => 201 , 'msg' => '请输入旧密码']);
        }
        if(!isset($this->data['new_pass']) || empty($this->data['new_pass'])){
            return response()->json(['code' => 201 , 'msg' => '请输入新密码']);
        }
        if(!isset($this->data['news_pass']) || empty($this->data['news_pass'])){
            return response()->json(['code' => 201 , 'msg' => '请再次输入新密码']);
        }
        $user = Student::where(['id'=>$this->userid])->first()->toArray();
        $olds_pass = password_hash($this->data['old_pass'] , PASSWORD_DEFAULT);
        if($olds_pass != $user['password']){
            return response()->json(['code' => 202 , 'msg' => '密码错误']);
        }
        if($this->data['new_pass'] != $this->data['news_pass']){
            return response()->json(['code' => 202 , 'msg' => '两次输入不一致']);
        }
        $news_pass = password_hash($this->data['new_pass'] , PASSWORD_DEFAULT);
        $up = Student::where(['id'=>$this->userid])->update(['pass'=>$news_pass]);
        if($up){
            return response()->json(['code' => 200 , 'msg' => '修改成功']);
        }else{
            return response()->json(['code' => 203 , 'msg' => '修改失败']);
        }
    }



}


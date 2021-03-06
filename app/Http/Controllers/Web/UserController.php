<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Collection;
use App\Models\Coures;
use App\Models\Couresmethod;
use App\Models\Couresteacher;
use App\Models\CourseSchool;
use App\Models\Order;
use App\Models\Region;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentCollect;
use App\Models\Teacher;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller {
    protected $school;
    protected $data;
    protected $userid;
    public function __construct(){
        $this->data = $_REQUEST;
        $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
        $this->userid = isset($_REQUEST['user_info']['user_id'])?$_REQUEST['user_info']['user_id']:0;
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
        //判断验证码是否为空
        if(!isset($this->data['verifycode']) || empty($this->data['verifycode'])){
            return response()->json(['code' => 201 , 'msg' => '请输入验证码']);
        }
        //验证码合法验证
        $verify_code = Redis::get('user:register:'.$this->data['phone']);
        if(!$verify_code || empty($verify_code)){
            return ['code' => 201 , 'msg' => '请先获取验证码'];
        }
        //判断验证码是否一致
        if($verify_code != $this->data['verifycode']){
            return ['code' => 202 , 'msg' => '验证码错误'];
        }
        $first = Student::where(['phone'=>$this->data['phone']])->first()->toArray();
        if(!empty($first)){
            if($first['is_forbid'] == 2){
                return response()->json(['code' => 201 , 'msg' => '手机号已被禁用']);
            }
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
    //地区三级联动
    public function address(){
        $this->data['region_id'] = isset($this->data['region_id'])?$this->data['region_id']:0;
        $address = $this->getRegionDataList($this->data);
        return response()->json($address);
    }
    //用户修改基本信息
    public function userUpDetail(){
        if(!isset($this->data['real_name']) || empty($this->data['real_name'])){
            return response()->json(['code' => 201 , 'msg' => '姓名不能为空']);
        }
        $res['real_name'] = $this->data['real_name'];
        $res['update_at'] = date('Y-m-d H:i:s');
        if(isset($this->data['sex'])){
            $res['sex'] = $this->data['sex'];
        }
        if(isset($this->data['nickname'])){
            $res['nickname'] = $this->data['nickname'];
        }
        if(isset($this->data['age'])){
            $res['age'] = $this->data['age'];
        }
        if(isset($this->data['papers_type'])){
            $res['papers_type'] = $this->data['papers_type'];
        }
        if(isset($this->data['educational'])){
        }
        if(isset($this->data['papers_num'])){
            $res['papers_num'] = $this->data['papers_num'];
        }
        if(isset($this->data['address_locus'])){
            $res['address_locus'] = $this->data['address_locus'];
        }
        if(isset($this->data['birthday'])){
            $res['birthday'] = $this->data['birthday'];
        }
        if(isset($this->data['sign'])){
            $res['sign'] = $this->data['sign'];
        }
        $up = Student::where(['id'=>$this->userid])->update($res);
        if($up){
            return response()->json(['code' => 200 , 'msg' => '修改成功']);
        }else{
            return response()->json(['code' => 203 , 'msg' => '修改失败']);
        }
    }
    //用户修改联系方式
    public function userUpRelation(){
        $res['update_at'] = date('Y-m-d H:i:s');
        if(isset($this->data['family_phone']) || !empty($this->data['family_phone'])){
            $res['family_phone'] = $this->data['family_phone'];
        }
        if(isset($this->data['office_phone']) || !empty($this->data['office_phone'])){
            $res['office_phone'] = $this->data['office_phone'];
        }
        if(isset($this->data['contact_people']) || !empty($this->data['contact_people'])){
            $res['contact_people'] = $this->data['contact_people'];
        }
        if(isset($this->data['contact_phone']) || !empty($this->data['contact_phone'])){
            $res['contact_phone'] = $this->data['contact_phone'];
        }
        if(isset($this->data['email']) || !empty($this->data['email'])){
            $res['email'] = $this->data['email'];
        }
        if(isset($this->data['qq']) || !empty($this->data['qq'])){
            $res['qq'] = $this->data['qq'];
        }
        if(isset($this->data['wechat']) || !empty($this->data['wechat'])){
            $res['wechat'] = $this->data['wechat'];
        }
        $up = Student::where(['id'=>$this->userid])->update($res);
        if($up){
            return response()->json(['code' => 200 , 'msg' => '修改成功']);
        }else{
            return response()->json(['code' => 203 , 'msg' => '修改失败']);
        }
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
        if(strlen($this->data['new_pass']) <6 || strlen($this->data['new_pass']) >15){
            return response()->json(['code' => 201 , 'msg' => '密码长度为6到15']);
        }
        $user = Student::where(['id'=>$this->userid])->first()->toArray();
        //验证密码是否合法
        if(password_verify($this->data['old_pass']  , $user['password']) === false){
            return response()->json(['code' => 203 , 'msg' => '密码错误']);
        }
        if($this->data['new_pass'] != $this->data['news_pass']){
            return response()->json(['code' => 202 , 'msg' => '两次输入不一致']);
        }
        $news_pass = password_hash($this->data['new_pass'] , PASSWORD_DEFAULT);
        $up = Student::where(['id'=>$this->userid])->update(['password'=>$news_pass]);
        if($up){
            return response()->json(['code' => 200 , 'msg' => '修改成功']);
        }else{
            return response()->json(['code' => 203 , 'msg' => '修改失败']);
        }
    }
    /*
         * @param  个人信息
         * @param  author  苏振文
         * @param  ctime   2020/7/9 19:38
         * return  array
         */

    //我的收藏
    public function myCollect(){
        $methods = isset($this->data['method'])?$this->data['method']:0;
        $collect = Collection::where(['student_id'=>$this->userid,'is_del'=>0])->get();
        $coursearr=[];
        if(!empty($collect)) {
            foreach ($collect as $k => &$v) {
                if ($v['nature'] == 1) {
                    $course = CourseSchool::where(['id' => $v['lesson_id'], 'is_del' => 0, 'status' => 1])->first();
                    $courseid = $course['course_id'];
                    $course['nature'] = 1;
                } else {
                    $course = Coures::where(['id' => $v['lesson_id'], 'is_del' => 0, 'status' => 1])->first();
                    $courseid = $course['id'];
                    $course['nature'] = 0;
                }
                $method = Couresmethod::select('method_id')->where(['course_id' => $courseid, 'is_del' => 0])
                    ->where(function ($query) use ($methods) {
                        if ($methods != '' && $methods != 0) {
                            $query->where('method_id', $methods);
                        }
                    })->get()->toArray();
//                $coursemethod= array_column($method, 'method_id');
                if (!empty($method)) {
                    foreach ($method as $key => &$val) {
                        if ($val['method_id'] == 1) {
                            $val['method_name'] = '直播';
                        }
                        if ($val['method_id'] == 2) {
                            $val['method_name'] = '录播';
                        }
                        if ($val['method_id'] == 3) {
                            $val['method_name'] = '其他';
                        }
                    }
                    $course['method'] = $method;
                    $coursearr[] = $course;
                } else {
                    unset($collect[$k]);
                }
            }
        }
        return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>$coursearr]);
    }

    //我的课程
    public function myCourse(){
        $order = Order::where(['student_id'=>$this->userid,'status'=>2])
            ->whereIn('pay_status',[3,4])
            ->get()->toArray();
        $courses = [];
        if(!empty($order)){
            foreach ($order as $k=>$v){
                if($v['nature'] == 1){
                    $course = CourseSchool::where(['id'=>$v['class_id'],'is_del'=>0,'status'=>1])->first();
                    if(!empty($course)){
                        $course['nature'] = 1;
                        //查讲师
                        $teacherlist = Couresteacher::where(['course_id'=>$course['course_id'],'is_del'=>0])->get();
                        $string=[];
                        if(!empty($teacherlist)){
                            foreach ($teacherlist as $ks=>$vs){
                                $teacher = Teacher::where(['id'=>$vs['teacher_id'],'is_del'=>0,'type'=>2])->first();
                                $string[] = $teacher['real_name'];
                            }
                            $course['teachername'] = implode(',',$string);
                        }else{
                            $course['teachername']='';
                        }
                        //查授课方式
                        $method = Couresmethod::select('method_id')->where(['course_id' => $course['course_id'], 'is_del' => 0])->get()->toArray();
                        if (!empty($method)) {
                            foreach ($method as $key => &$val) {
                                if ($val['method_id'] == 1) {
                                    $val['method_name'] = '直播';
                                }
                                if ($val['method_id'] == 2) {
                                    $val['method_name'] = '录播';
                                }
                                if ($val['method_id'] == 3) {
                                    $val['method_name'] = '其他';
                                }
                            }
                            $course['method'] = $method;
                        }
                        //查询有效期
                        $date1 = time();
                        $date2 = strtotime($v['validity_time']);
                        if($date1 >= $date2){
                            $course['day'] = '已过期';
                        }else{
                            $interval = $date2 -$date1;
                            $a = $interval / 86400;
                            $day = floor($a);
                            if($course['expiry'] == 0){
                                $course['day'] = '无期限';
                            }else{
                                if($day > 0){
                                    $course['day'] = $day.'天';
                                }else{
                                    $course['day'] = '已过期';
                                }
                            }
                        }
                        $courses[] = $course;
                    }
                }else {
                    $course = Coures::where(['id' => $v['class_id'], 'is_del' => 0, 'status' => 1])->first();
                    if (!empty($course)) {
                        $course['nature'] = 0;
                        //查讲师
                        $teacherlist = Couresteacher::where(['course_id' => $v['class_id'], 'is_del' => 0])->get();
                        $string = [];
                        if (!empty($teacherlist)) {
                            foreach ($teacherlist as $ks => $vs) {
                                $teacher = Teacher::where(['id' => $vs['teacher_id'], 'is_del' => 0, 'type' => 2])->first();
                                $string[] = $teacher['real_name'];
                            }
                            $course['teachername'] = implode(',', $string);
                        } else {
                            $course['teachername'] = '';
                        }
                        //查授课方式
                        $method = Couresmethod::select('method_id')->where(['course_id' => $v['class_id'], 'is_del' => 0])->get()->toArray();
                        if (!empty($method)) {
                            foreach ($method as $key => &$val) {
                                if ($val['method_id'] == 1) {
                                    $val['method_name'] = '直播';
                                }
                                if ($val['method_id'] == 2) {
                                    $val['method_name'] = '录播';
                                }
                                if ($val['method_id'] == 3) {
                                    $val['method_name'] = '其他';
                                }
                            }
                            $course['method'] = $method;
                        }
                        //查询有效期
                        $date1 = time();
                        $date2 = strtotime($v['validity_time']);
                        if($date1 >= $date2){
                            $course['day'] = '已过期';
                        }else{
                            $interval = $date2 -$date1;
                            $a = $interval / 86400;
                            $day = floor($a);
                            if($course['expiry'] == 0){
                                $course['day'] = '无期限';
                            }else{
                                if($day > 0){
                                    $course['day'] = $day.'天';
                                }else{
                                    $course['day'] = '已过期';
                                }
                            }
                        }
                        $courses[] = $course;
                    }
                }
            }
        }
        return response()->json(['code' => 200 , 'msg' => '获取成功','data'=>$courses]);
    }
    //我的订单  status 1已完成2未完成3已失效
    public function myOrder(){
        $status = isset($this->data['status'])?$this->data['status']:'';
        $order = Order::where(['student_id'=>$this->userid])
            ->where(function($query) use ($status) {
                //状态判断
                if($status == 1){
                    $query->where('status',1)
                    ->orwhere('status',2);
                }
                if($status == 2){
                    $query->where('status',0);
                }
                if($status == 3){
                    $query->where('status',5);
                }
            })
            ->where('status','!=',3)
            ->where('status','!=',4)
            ->orderByDesc('id')->get()->toArray();
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                if($v['nature'] == 1){
                    $course = CourseSchool::select('title')->where(['id'=>$v['class_id'],'is_del'=>0])->first();
                }else{
                    $course = Coures::select('title')->where(['id'=>$v['class_id'],'is_del'=>0])->first();
                }
                $v['title'] = isset($course['title'])?$course['title']:'';
            }
        }
        //所有总数
        $success1 = Order::where(['student_id'=>$this->userid,'status'=>1])->count();
        $success2 = Order::where(['student_id'=>$this->userid,'status'=>2])->count();
        $success = $success1 + $success2;
        $unfinished = Order::where(['student_id'=>$this->userid,'status'=>0])->count();
        $error = Order::where(['student_id'=>$this->userid,'status'=>5])->count();
        $count = [
            0=>!empty($success)?$success:0,
            1=>!empty($unfinished)?$unfinished:0,
            2=>!empty($error)?$error:0
        ];
        return response()->json(['code' => 200, 'msg' => '获取成功','data'=>$order,'count'=>$count]);
    }
    //订单单条详情
    public function orderFind(){
        if(!isset($this->data['id'])||empty($this->data['id'])){
            return response()->json(['code' => 201 , 'msg' => '订单id为空']);
        }
        $order = Order::where('id',$this->data['id'])->first()->toArray();
        if(!empty($order)){
            if($order['nature'] == 1){
                $course = CourseSchool::select('title')->where(['id'=>$order['class_id'],'is_del'=>0,'status'=>1])->first()->toArray();
            }else{
                $course = Coures::select('title')->where(['id'=>$order['class_id'],'is_del'=>0,'status'=>1])->first()->toArray();
            }
            $order['title'] = isset($course['title'])?$course['title']:'';
        }
        return response()->json(['code' => 200, 'msg' => '获取成功','data'=>$order]);
    }
}


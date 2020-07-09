<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Coures;
use App\Models\School;

class PublicpayController extends Controller {
    protected $school;
    protected $data;
    protected $userid;
    public function __construct(){
        $this->data = $_REQUEST;
        if(!isset($this->data['school_dns']) || empty($this->data['school_dns'])){
            return response()->json(['code' => 201 , 'msg' => '请传域名']);
        }
        $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
        $this->userid = isset(AdminLog::getAdminInfo()->admin_user->id)?AdminLog::getAdminInfo()->admin_user->id:0;
    }
   /*
        * @param  对公信息
        * @param  author  苏振文
        * @param  ctime   2020/7/8 15:52
        * return  array
        */
   public function Transfer(){
       //根据学校查询支付信息
       return response()->json(['code' => 200 , 'msg' => '查询成功','data'=>$this->school]);
   }
   /*
        * @param  OA流转订单
        * @param  author  苏振文
        * student_id  用户id
        * order_number 订单号
        * third_party_number 第三方支付订单号
        * pay_status 1定金2尾款3最后一笔款4全款
        * pay_type 1微信2支付宝3银行转账4汇聚5余额
        * pay_time 支付时间
        * class_id 课程id
        * nature 1 授权 0自增
        * @param  ctime   2020/7/8 15:55
        * return  array
        */
   public function orderOAtoPay(){
       if(!isset($this->data['nature']) || empty($this->data['nature'])){
           return response()->json(['code' => 201 , 'msg' => '课程类型不能为空']);
       }
       if(!isset($this->data['class_id']) || empty($this->data['class_id'])){
           return response()->json(['code' => 201 , 'msg' => '课程不能为空']);
       }
       if(!isset($this->data['order_number']) || empty($this->data['order_number'])){
           return response()->json(['code' => 201 , 'msg' => '订单号不能为空']);
       }
       if(!isset($this->data['pay_time']) || empty($this->data['pay_time'])){
           return response()->json(['code' => 201 , 'msg' => '订单支付时间']);
       }
       if(!isset($this->data['pay_type']) || empty($this->data['pay_type'])){
           return response()->json(['code' => 201 , 'msg' => '支付方式不能为空']);
       }
       if(!isset($this->data['pay_time']) || empty($this->data['pay_status'])){
           return response()->json(['code' => 201 , 'msg' => '支付类型不能为空']);
       }
       if(!isset($this->data['student_id']) || empty($this->data['student_id'])){
           return response()->json(['code' => 201 , 'msg' => '学生不能为空']);
       }
       //查询课程信息
       if($this->data['nature'] == 1){
//            $course =
       }
   }
}


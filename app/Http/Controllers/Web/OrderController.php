<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Converge;
use App\Models\Coures;
use App\Models\Couresteacher;
use App\Models\CourseSchool;
use App\Models\Order;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Tools\AlipayFactory;
use App\Tools\WxpayFactory;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller {
    protected $school;
    protected $data;
    protected $userid;
    public function __construct(){
        $this->data = $_REQUEST;
        $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
        $this->userid = isset($this->data['user_info']['user_id'])?$this->data['user_info']['user_id']:0;
    }
    //用户生成订单
     public function userPay(){
        DB::beginTransaction();
        $nature = isset($this->data['nature'])?$this->data['nature']:0;
        if($nature == 1){
            $course = CourseSchool::where(['id'=>$this->data['id'],'is_del'=>0,'status'=>1])->first();
            //查讲师
            $teacherlist = Couresteacher::where(['course_id'=>$course['class_id'],'is_del'=>0])->get();
            $string=[];
            if(!empty($teacherlist)){
                foreach ($teacherlist as $ks=>$vs){
                    $teacher = Teacher::where(['id'=>$vs['teacher_id'],'is_del'=>0,'type'=>2])->first();
                    $string[] = $teacher['real_name'];
                }
            $course['teachername'] = implode(',',$string);
            }
        }else{
            $course = Coures::where(['id'=>$this->data['id'],'is_del'=>0,'status'=>1])->first();
            //查讲师
            $teacherlist = Couresteacher::where(['course_id'=>$course['id'],'is_del'=>0])->get();
            $string=[];
            if(!empty($teacherlist)){
                foreach ($teacherlist as $ks=>$vs){
                    $teacher = Teacher::where(['id'=>$vs['teacher_id'],'is_del'=>0,'type'=>2])->first();
                    $string[] = $teacher['real_name'];
                }
             $course['teachername'] = implode(',',$string);
            }
        }
        //生成订单
         $data['order_number'] = date('YmdHis', time()) . rand(1111, 9999);
         $data['admin_id'] = 0;  //操作员id
         $data['order_type'] = 2;        //1线下支付 2 线上支付
         $data['student_id'] = $this->userid;
         $data['price'] = $course['sale_price'];
         $data['student_price'] = $course['pricing'];
         $data['lession_price'] = $course['pricing'];
         $data['pay_status'] = 4;
         $data['pay_type'] = 0;
         $data['status'] = 0;
         $data['oa_status'] = 0;              //OA状态
         $data['nature'] = $nature;
         $data['class_id'] = $this->data['id'];
         $data['school_id'] = $this->school['id'];
         $add = Order::insertGetId($data);
         if($add){
             $course['order_id'] = $add;
             $course['order_number'] = $data['order_number'];
             DB::commit();
             return ['code' => 200 , 'msg' => '生成预订单成功','data'=>$course];
         }else{
             DB::rollback();
             return ['code' => 203 , 'msg' => '生成订单失败'];
         }
     }
     //用户进行支付  支付方式 1微信2支付宝
     public function userPaying(){
        $order = Order::where(['id'=>$this->data['order_id']])->first();
        if($this->data['pay_type'] == 1){
            $wxpay = new WxpayFactory();
            $number = date('YmdHis', time()) . rand(1111, 9999);
            $price = 0.01;
            $return = $wxpay->getPcPayOrder($number,$price);
        }
        if($this->data['pay_type'] == 2){
            $alipay = new AlipayFactory();
            $return = $alipay->createPcPay($order['order_number'],$order['price']);
            if($return['alipay_trade_precreate_response']['code'] == 10000){
//                $img = $this->generateQRfromGoogle($return['alipay_trade_precreate_response']['qr_code']);
//                echo $img;
                return ['code' => 200 , 'msg' => '支付','data'=>$return['alipay_trade_precreate_response']['qr_code']];
            }else{
                return ['code' => 202 , 'msg' => '生成二维码失败'];
            }
        }
     }
     //前端轮询查订单是否支付完成
    public function webajax(){
        if(!isset($this->data['order_number']) || empty($this->data['order_number'])){
            return ['code' => 201 , 'msg' => '订单号为空'];
        }
        $order = Order::where(['order_number'=>$this->data['order_number']])->first();
        if($order){
            if($order['status'] == 2){
                $fanb = 1;
            }else{
                $fanb = 0;
            }
            return ['code' => 200 , 'msg' => '查询成功','data'=>$fanb];
        }else{
            return ['code' => 201 , 'msg' => '订单号错误'];
        }
    }
    //0元购买接口
    public function chargeOrder(){
       $order = Order::where(['id'=>$this->data['order_id']])->first();
       if($order['price'] == 0){
           if($order['nature'] == 1){
               $lesson = CourseSchool::where(['id'=>$order['class_id']])->first();
           }else{
               $lesson = Coures::where(['id'=>$order['class_id']])->first();
           }
           if($lesson['expiry'] ==0){
               $validity = '3000-01-02 12:12:12';
           }else{
               $validity = date('Y-m-d H:i:s', strtotime('+' . $lesson['expiry'] . ' day'));
           }
           $arrs = array(
               'third_party_number'=>'',
               'validity_time'=>$validity,
               'status'=>2,
               'oa_status'=>1,
               'pay_time'=>date('Y-m-d H:i:s'),
               'update_at'=>date('Y-m-d H:i:s')
           );
           Order::where(['id'=>$order['id']])->update($arrs);
           $overorder = Order::where(['student_id'=>$order['student_id'],'status'=>2])->count(); //用户已完成订单
           $userorder = Order::where(['student_id'=>$order['student_id']])->count(); //用户所有订单
           if($overorder == $userorder){
               $state_status = 2;
           }else{
               if($overorder > 0 ){
                   $state_status = 1;
               }else{
                   $state_status = 0;
               }
           }
           Student::where(['id'=>$order['student_id']])->update(['enroll_status'=>1,'state_status'=>$state_status,'update_at'=>date('Y-m-d H:i:s')]);
           return ['code' => 200 , 'msg' => '购买成功'];
       }else{
           return ['code' => 201 , 'msg' => '订单不合法'];
       }
    }

    //汇聚支付宝支付
    public function converge(){
         if($this->data['nature'] == 1){
             $course = CourseSchool::where(['id'=>$this->data['id'],'is_del'=>0,'status'=>1])->first();
         }else{
             $course = Coures::where(['id'=>$this->data['id'],'is_del'=>0,'status'=>1])->first();
         }
         if(empty($course)){
             return response()->json(['code' => 201, 'msg' => '未查到此课程信息']);
         }
         if(!isset($this->data['phone']) || $this->data['phone'] == ''){
             return response()->json(['code' => 201, 'msg' => '请填写手机号']);
         }
         if(!isset($this->data['price']) || $this->data['price'] <= 0){
                return response()->json(['code' => 201, 'msg' => '金额不能为0']);
         }
         $arr = [
             'username' => $this->data['username'],
             'phone' => $this->data['phone'],
             'order_number' => date('YmdHis', time()) . rand(1111, 9999),
             'pay_status' => $this->data['pay_status'],
             'price' => $this->data['price'],
             'status' => 0,
             'parent_id' => $this->data['parent_id'],
             'chint_id' => $this->data['chint_id'],
             'course_id' => $this->data['course_id'],
             'nature' => $this->data['nature'],
             'school_id' => $this->school['id'],
         ];
        $add = Converge::insert($arr);
        if($add){
            //微信
            if($this->data['pay_status'] == 1){
                $notify = "http://".$_SERVER['HTTP_HOST']."/Web/course/wxhjnotify";
                $pay=[
                    'p0_Version'=>'1.0',
                    'p1_MerchantNo'=>'888108900009969',
                    'p2_OrderNo'=>$arr['order_number'],
                    'p3_Amount'=>$this->data['price'],
                    'p4_Cur'=>1,
                    'p5_ProductName'=>"龙德产品",
                    'p9_NotifyUrl'=>$notify,
                    'q1_FrpCode'=>'WEIXIN_NATIVE',
                    'q4_IsShowPic'=>1,
                    'qa_TradeMerchantNo'=>'777170100269422'
                ];
                $str = "15f8014fee1642fbb123fb5684cda48b";
                $token = $this->hjHmac($pay,$str);
                $pay['hmac'] = $token;
                $aaa = $this->hjpost($pay);
                print_r($aaa);die;
            }
            //支付宝
            if($this->data['pay_status'] == 2){
                $notify = "http://".$_SERVER['HTTP_HOST']."/Web/course/alihjnotify";
                $pay=[
                    'p0_Version'=>'1.0',
                    'p1_MerchantNo'=>'888108900009969',
                    'p2_OrderNo'=>$arr['order_number'],
                    'p3_Amount'=>$this->data['price'],
                    'p4_Cur'=>1,
                    'p5_ProductName'=>"龙德产品",
                    'p9_NotifyUrl'=>$notify,
                    'q1_FrpCode'=>'ALIPAY_NATIVE',
                    'q4_IsShowPic'=>1,
                    'qa_TradeMerchantNo'=>'777167300271170'
                ];
                $str = "15f8014fee1642fbb123fb5684cda48b";
                $token = $this->hjHmac($pay,$str);
                $pay['hmac'] = $token;
                $aaa = $this->hjpost($pay);
                print_r($aaa);die;
            }
        }
    }
    //汇聚签名
    public function hjHmac($arr,$str){
        $newarr = '';
        foreach ($arr as $k=>$v){
            $newarr =$newarr.$v;
        }
        return md5($newarr.$str);
    }
    public function hjpost($data){
        //简单的curl
        $ch = curl_init("https://www.joinpay.com/trade/uniPayApi.action");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    /**
     * google api 二维码生成【QRcode可以存储最多4296个字母数字类型的任意文本，具体可以查看二维码数据格式】
     * @param string $chl 二维码包含的信息，可以是数字、字符、二进制信息、汉字。
    不能混合数据类型，数据必须经过UTF-8 URL-encoded
     * @param int $widhtHeight 生成二维码的尺寸设置
     * @param string $EC_level 可选纠错级别，QR码支持四个等级纠错，用来恢复丢失的、读错的、模糊的、数据。
     *                            L-默认：可以识别已损失的7%的数据
     *                            M-可以识别已损失15%的数据
     *                            Q-可以识别已损失25%的数据
     *                            H-可以识别已损失30%的数据
     * @param int $margin 生成的二维码离图片边框的距离
     */
    function generateQRfromGoogle($chl,$widhtHeight ='150',$EC_level='L',$margin='0'){
        $chl = urlencode($chl);
        echo 'http://chart.apis.google.com/chart?chs='.$widhtHeight.'x'.$widhtHeight.'&cht=qr&chld='.$EC_level.'|'.$margin.'&chl='.$chl;
    }
}


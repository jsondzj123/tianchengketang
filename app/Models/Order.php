<?php
namespace App\Models;

use App\Providers\aop\AopClient\AopClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Order extends Model {
    //指定别的表名
    public $table = 'ld_order';
    //时间戳设置
    public $timestamps = false;
    /*
         * @param  订单列表
         * @param  $school_id  分校id
         * @param  $status  状态
         * @param  $state_time 开始时间
         * @param  $end_time 结束时间
         * @param  $order_number 订单号
         * @param  author  苏振文
         * @param  ctime   2020/5/4 14:41
         * return  array
         */
    public static function getList($data){
        unset($data['/admin/order/orderList']);
        //用户权限
        $role_id = isset(AdminLog::getAdminInfo()->admin_user->role_id) ? AdminLog::getAdminInfo()->admin_user->role_id : 0;
        //如果不是总校管理员，只能查询当前关联的网校订单
        if($role_id != 1){
            $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
            $data['school_id'] = $school_id;
        }
        $begindata=date('Y-m-01', strtotime(date("Y-m-d")));
        $enddate = date('Y-m-d',strtotime("$begindata +1 month -1 day"));
        $statetime = !empty($data['state_time'])?$data['state_time']:$begindata;
        $endtime = !empty($data['end_time'])?$data['end_time']:$enddate;
        $state_time = $statetime." 00:00:00";
        $end_time = $endtime." 23:59:59";
        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        //計算總數
        $count = self::leftJoin('ld_student','ld_student.id','=','ld_order.student_id')
            ->where(function($query) use ($data) {
                if(isset($data['school_id']) && !empty($data['school_id'])){
                    $query->where('ld_order.school_id',$data['school_id']);
                }
                if(isset($data['status']) && $data['status'] != -1){
                    $query->where('ld_order.status',$data['status']);
                }
                if(isset($data['order_number'])&& !empty($data['order_number'])){
                    $query->where('ld_order.order_number',$data['order_number']);
                }
            })
            ->whereBetween('ld_order.create_at', [$state_time, $end_time])
            ->count();
        $order = self::select('ld_order.id','ld_order.order_number','ld_order.order_type','ld_order.price','ld_order.pay_status','ld_order.pay_type','ld_order.status','ld_order.create_at','ld_order.oa_status','ld_order.student_id','ld_student.phone','ld_student.real_name')
            ->leftJoin('ld_student','ld_student.id','=','ld_order.student_id')
            ->where(function($query) use ($data) {
                if(isset($data['school_id']) && !empty($data['school_id'])){
                    $query->where('ld_order.school_id',$data['school_id']);
                }
                if(isset($data['status'])&& $data['status'] != -1){
                    $query->where('ld_order.status',$data['status']);
                }
                if(isset($data['order_number'])&& !empty($data['order_number'])){
                    $query->where('ld_order.order_number',$data['order_number']);
                }
            })
            ->whereBetween('ld_order.create_at', [$state_time, $end_time])
            ->orderByDesc('ld_order.id')
            ->offset($offset)->limit($pagesize)->get()->toArray();
        $schooltype = Article::schoolANDtype($role_id);
        $page=[
            'pageSize'=>$pagesize,
            'page' =>$page,
            'total'=>$count
        ];
        return ['code' => 200 , 'msg' => '查询成功','data'=>$order,'school'=>$schooltype[0],'where'=>$data,'page'=>$page];
    }
    /*
       * @param  线下学生报名 添加订单
       * @param  $student_id  用户id
       * @param  $lession_id 学科id
       * @param  $lession_price 原价
       * @param  $student_price 现价
       * @param  $payment_type 1定金2尾款3最后一笔尾款4全款
       * @param  $payment_method 1微信2支付宝3银行转账
       * @param  $payment_time 支付时间
       * @param  author  苏振文
       * @param  ctime   2020/5/6 9:57
       * return  array
       */
    public static function offlineStudentSignup($arr){
        //判断传过来的数组数据是否为空
        if(!$arr || !is_array($arr)){
            return ['code' => 201 , 'msg' => '传递数据不合法'];
        }
        //判断学生id
        if(!isset($arr['student_id']) || empty($arr['student_id'])){
            return ['code' => 201 , 'msg' => '报名学生为空或格式不对'];
        }
        //判断学科id
        if(!isset($arr['lession_id']) || empty($arr['lession_id'])){
            return ['code' => 201 , 'msg' => '学科为空或格式不对'];
        }
        //判断原价
        if(!isset($arr['lession_price']) || empty($arr['lession_price'])){
            return ['code' => 201 , 'msg' => '原价为空或格式不对'];
        }
        //判断付款类型
        if(!isset($arr['payment_type']) || empty($arr['payment_type']) || !in_array(  $arr['payment_type'],[1,2,3,4])){
            return ['code' => 201 , 'msg' => '付款类型为空或格式不对'];
        }
        //判断原价
        if(!isset($arr['payment_method']) || empty($arr['payment_method'])|| !in_array($arr['payment_method'],[1,2,3])){
            return ['code' => 201 , 'msg' => '付款方式为空或格式不对'];
        }
        //判断支付时间
        if(!isset($arr['payment_time'])|| empty($arr['payment_time'])){
            return ['code' => 201 , 'msg' => '支付时间不能为空'];
        }
        //获取后端的操作员id
        $data['admin_id'] = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;  //操作员id
        //根据用户id获得分校id
        $school = Student::select('school_id')->where('id',$arr['student_id'])->first();
        $data['order_number'] = date('YmdHis', time()) . rand(1111, 9999); //订单号  随机生成
        $data['order_type'] = 1;        //1线下支付 2 线上支付
        $data['student_id'] = $arr['student_id'];
        $data['price'] = $arr['student_price'];
        $data['lession_price'] = $arr['lession_price'];
        $data['pay_status'] = $arr['payment_type'];
        $data['pay_type'] = $arr['payment_method'];
        $data['status'] = 1;                  //支付状态
        $data['pay_time'] = $arr['payment_time'];
        $data['oa_status'] = 0;              //OA状态
        $data['class_id'] = $arr['lession_id'];
        $data['school_id'] = $school['school_id'];
        $data['nature'] = $arr['nature'];
        $add = self::insert($data);
        if($add){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $data['admin_id']  ,
                'module_name'    =>  'Order' ,
                'route_url'      =>  'admin/Order/offlineStudentSignup' ,
                'operate_method' =>  'insert' ,
                'content'        =>  '添加订单的内容,'.json_encode($data),
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return true;
        }else{
            return false;
        }
    }
    /*
         * @param  线上支付 生成预订单
         * @param  $student_id  学生id
         * @param  type  1安卓2ios3h5
         * @param  $class_id  课程id
         * @param  author  苏振文
         * @param  ctime   2020/5/6 14:53
         * return  array
         */
    public static function orderPayList($arr){
            DB::beginTransaction();
            if(!$arr || empty($arr)){
                return ['code' => 201 , 'msg' => '参数错误'];
            }
            //判断学生id
            if(!isset($arr['student_id']) || empty($arr['student_id'])){
                return ['code' => 201 , 'msg' => '学生id为空或格式不对'];
            }
            //根据用户id查询信息
            $student = Student::select('school_id','balance')->where('id',$arr['student_id'])->first();
            //判断课程id
            if(!isset($arr['class_id']) || empty($arr['class_id'])){
                return ['code' => 201 , 'msg' => '课程id为空或格式不对'];
            }
            //判断类型
            if(!isset($arr['type']) || empty($arr['type'] || !in_array($arr['type'],[1,2,3]))){
                return ['code' => 201 , 'msg' => '机型不匹配'];
            }
            $course = Coures::select('id','title','cover','pricing','sale_price')->where(['id'=>$arr['class_id'],'is_del'=>0,'status'=>1])->first();


            //判断用户网校，根据网校查询课程信息
//           if($student['school_id'] == 1){
//               //根据课程id 查询价格
//               $lesson = Lesson::select('id','title','cover','price','favorable_price')->where(['id'=>$arr['class_id'],'is_del'=>0,'is_forbid'=>0,'status'=>2,'is_public'=>0])->first();
//           }else{
//                //根据课程id 网校id 查询网校课程详情
//               $lesson = LessonSchool::select('id','title','cover','price','favorable_price')->where(['lesson_id'=>$arr['class_id'],'school_id'=>$student['school_id'],'is_del'=>0,'is_forbid'=>0,'status'=>1,'is_public'=>0])->first();
//           }
            if(!$course){
                return ['code' => 204 , 'msg' => '此课程选择无效'];
            }
            if(empty($course['sale_price']) || empty($course['price'])){
                return ['code' => 204 , 'msg' => '此课程信息有误选择无效'];
            }

            //根据分校查询支付方式
            $payList = PaySet::where(['school_id'=>$student['school_id']])->first();
            if(empty($payList)) {
                $payList = PaySet::where(['school_id' => 1])->first();
            }
            $newpay=[];
            if($payList['wx_pay_state'] == 1){
                array_push($newpay,1);
            }
            if($payList['zfb_pay_state'] == 1){
                array_push($newpay,2);
            }
            if($payList['hj_wx_pay_state'] == 1){
                array_push($newpay,3);
            }
            if($payList['hj_zfb_pay_state'] == 1){
                array_push($newpay,4);
            }
            //查询用户有此类订单没有，有的话直接返回
//            $orderfind = self::where(['student_id'=>$arr['student_id'],'class_id'=>$arr['class_id'],'status'=>0])->first();
//            if($orderfind){
//                $lesson['order_id'] = $orderfind['id'];
//                $lesson['order_number'] = $orderfind['order_number'];
//                $lesson['user_balance'] = $student['balance'];
//                return ['code' => 200 , 'msg' => '生成预订单成功1','data'=>$lesson,'paylist'=>$newpay];
//            }
            //数据入库，生成订单
            $data['order_number'] = date('YmdHis', time()) . rand(1111, 9999);
            $data['admin_id'] = 0;  //操作员id
            $data['order_type'] = 2;        //1线下支付 2 线上支付
            $data['student_id'] = $arr['student_id'];
            $data['price'] = $course['sale_price'];
            $data['lession_price'] = $course['pricing'];
            $data['pay_status'] = 4;
            $data['pay_type'] = 0;
            $data['status'] = 0;
            $data['oa_status'] = 0;              //OA状态
            $data['class_id'] = $arr['class_id'];
            $data['school_id'] = $student['school_id'];
            $add = self::insertGetId($data);
            if($add){
                $lesson['order_id'] = $add;
                $lesson['order_number'] = $data['order_number'];
                $lesson['user_balance'] = $student['balance'];
                DB::commit();
                return ['code' => 200 , 'msg' => '生成预订单成功','data'=>$course,'paylist'=>$newpay];
            }else{
                DB::rollback();
                return ['code' => 203 , 'msg' => '生成订单失败'];
            }
    }
    /*
         * @param  修改审核状态
         * @param  $order_id 订单id
         * @param  $status 1审核通过 status修改成2    2退回审核  status修改成4
         * @param  author  苏振文
         * @param  ctime   2020/5/6 10:56
         * return  array
         */
    public static function exitForIdStatus($data){
        if(!$data || !is_array($data)){
            return ['code' => 201 , 'msg' => '数据不合法'];
        }
        $order = self::where(['id'=>$data['order_id']])->first();
        if(!$order){
            return ['code' => 201 , 'msg' => '数据无效'];
        }
        if($order['status'] != 1){
            return ['code' => 201 , 'msg' => '订单无法审核'];
        }
        //获取后端的操作员id
        $admin_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        if($order['status'] == 1){
            if($data['status'] == 2){
                $update = self::where(['id'=>$data['order_id']])->update(['status'=>2]);
                if($update){
                    //修改学员报名  订单状态 课程有效期
                    $lessons = Coures::where(['id'=>$order['class_id']])->first();
                    //计算用户购买课程到期时间
                    $validity = date('Y-m-d H:i:s',strtotime('+'.$lessons['ttl'].' day'));
                    //修改订单状态 课程有效期 oa状态
                    $update = self::where(['id'=>$order['id']])->update(['status'=>2,'validity_time'=>$validity,'oa_status'=>1,'update_at'=>date('Y-m-d H:i:s')]);
                    //修改用户报名状态
                    Student::where(['id'=>$order['student_id']])->update(['enroll_status'=>1]);
                    //添加日志操作
                    AdminLog::insertAdminLog([
                        'admin_id'       =>   $admin_id  ,
                        'module_name'    =>  'Order' ,
                        'route_url'      =>  'admin/Order/exitForIdStatus' ,
                        'operate_method' =>  'update' ,
                        'content'        =>  '审核成功，修改id为'.$data['order_id'].json_encode($data) ,
                        'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                        'create_at'      =>  date('Y-m-d H:i:s')
                    ]);
                    return ['code' => 200 , 'msg' => '回审通过'];
                }else{
                    return ['code' => 202 , 'msg' => '操作失败'];
                }
           }else if($data['status'] == 4){
                $update = self::where(['id'=>$data['order_id']])->update(['status'=>3]);
                if($update){
                    //添加日志操作
                    AdminLog::insertAdminLog([
                        'admin_id'       =>   $admin_id  ,
                        'module_name'    =>  'Order' ,
                        'route_url'      =>  'admin/Order/exitForIdStatus' ,
                        'operate_method' =>  'update' ,
                        'content'        =>  '退回审核，修改id为'.$data['order_id'].json_encode($data) ,
                        'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                        'create_at'      =>  date('Y-m-d H:i:s')
                    ]);
                    return ['code' => 200 , 'msg' => '回审通过'];
                }else{
                    return ['code' => 202 , 'msg' => '操作失败'];
                }
            }
        }else{
            return ['code' => 203 , 'msg' => '此订单无法进行此操作'];
        }
    }
    /*
         * @param  单条查看详情
         * @param  order_id   订单id
         * @param  author  苏振文
         * @param  ctime   2020/5/6 11:15
         * return  array
         */
    public static function findOrderForId($data){
        if (empty($data['order_id'])) {
            return ['code' => 201, 'msg' => '订单id错误'];
        }
        $order_info = self::select('order_number', 'create_at', 'price', 'order_type', 'status', 'pay_time', 'student_id', 'class_id','nature','lession_price')->where(['id' => $data['order_id']])->first();
        if (!empty($order_info)) {
            if ($order_info['student_id'] != '') {
                $student = Student::select('real_name', 'phone', 'school_id')->where(['id' => $order_info['student_id']])->first();
                $order_info['real_name'] = $student['real_name'];
                $order_info['phone'] = $student['phone'];
                if ($student['school_id'] != '') {
                    $school = School::select('name')->where(['id' => $student['school_id']])->first();
                    $order_info['schoolname'] = $school['name'];
                }
            }
            if ($order_info['class_id'] != '') {
                if($order_info['nature'] == 1){
                    $lesson = CourseSchool::select('course_id as id', 'title','sale_price')->where(['id' => $order_info['class_id']])->first();
                }else{
                    $lesson = Coures::select('id', 'title','sale_price')->where(['id' => $order_info['class_id']])->first();
                }
                if (!empty($lesson)) {
                    $order_info['title'] = $lesson['title'];
                    $teacher = Couresteacher::where(['course_id' => $lesson['id']])->get()->toArray();
                    if (!empty($teacher)) {
                        foreach ($teacher as $k=>$v){
                            $lecturer_educationa = Lecturer::select('real_name')->where(['id' => $v['teacher_id']])->first();
                            $teacherrealname[] = $lecturer_educationa['real_name'];
                        }
                        $teacherrealnames = implode($teacherrealname,',');
                        $order_info['real_names'] = $teacherrealnames;
                    }
                }
            }
            if ($order_info) {
                return ['code' => 200, 'msg' => '查询成功', 'data' => $order_info];
            } else {
                return ['code' => 202, 'msg' => '查询失败'];
            }
        }
    }
    /*
         * @param  订单修改oa状态
         * @param  $order_id
         * @param  $status
         * @param  author  苏振文
         * @param  ctime   2020/5/6 16:33
         * return  array
         */
    public static function orderUpOaForId($data){
        DB::beginTransaction();
        if(!$data || empty($data)){
            return ['code' => 201 , 'msg' => '参数为空或格式错误'];
        }
        if(empty($data['order_number'])){
            return ['code' => 201 , 'msg' => '订单号不能为空'];
        }
        if(!in_array($data['status'],['0','1'])){
            return ['code' => 201 , 'msg' => '状态传输错误'];
        }
        $order = self::where(['order_number'=>$data['order_number']])->first()->toArray();
        if(!$order){
            return ['code' => 201 , 'msg' => '订单号错误'];
        }
        if($data['status'] == 1){
            //修改学员报名  订单状态 课程有效期
            $lessons = Coures::where(['id'=>$order['class_id']])->first();
            //计算用户购买课程到期时间
            $validity = date('Y-m-d H:i:s',strtotime('+'.$lessons['ttl'].' day'));
            //修改订单状态 课程有效期 oa状态
            $update = self::where(['id'=>$order['id']])->update(['status'=>2,'validity_time'=>$validity,'oa_status'=>1,'update_at'=>date('Y-m-d H:i:s')]);
            //修改用户报名状态
            Student::where(['id'=>$order['student_id']])->update(['enroll_status'=>1]);
        }else{
            $update = self::where(['id'=>$order['id']])->update(['status'=>3,'oa_status'=>$data['status'],'update_at'=>date('Y-m-d H:i:s')]);
        }
        if($update){
            DB::commit();
            return ['code' => 200 , 'msg' => '修改成功'];
        }else{
            DB::rollback();
            return ['code' => 202 , 'msg' => '修改失败'];
        }
    }

    //根据用户查询订单
    public static function orderForStudent($data){
        if(!$data || empty($data)){
            return ['code' => 201 , 'msg' => '参数为空或格式错误'];
        }
        if(empty($data['student_id'])){
            return ['code' => 201 , 'msg' => '学员id为空'];
        }
        $order = self::where(['student_id'=>$data['student_id']])->get()->toArray();
        if(!empty($order)){
            foreach ($order as $k=>&$v){
                if($v['nature'] == 1){
                    $course = CourseSchool::where(['id'=>$v['class_id'],'is_del'=>0,'status'=>1])->first();
                }else{
                    $course = Coures::where(['id'=>$v['class_id'],'is_del'=>0,'status'=>1])->first();
                }
                $v['course_cover'] = $course['cover'];
                $v['course_title'] = $course['title'];
                if($v['status'] == 0){
                    $v['learning'] = "未支付";
                    $v['bgcolor'] = '#26A4FD';
                }
                if($v['status'] == 1){
                    $v['learning'] = "待审核";
                    $v['bgcolor'] = '#FDA426';
                }
                if($v['status'] == 2){
                    if($v['pay_status'] < 4){
                        $v['learning'] = "尾款未付清";
                        $v['bgcolor'] = '#FF4545';
                    }else{
                        $v['learning'] = "已开课";
                        $v['bgcolor'] = '#909399';
                    }
                }
                if($v['status'] == 3){
                    $v['learning'] = "审核失败";
                    $v['bgcolor'] = '#67C23A';
                }
                if($v['status'] == 4){
                    $v['learning'] = "已退款";
                    $v['bgcolor'] = '#f2f6fc';
                }
                if($v['status'] == 5){
                    $v['learning'] = "以失效";
                    $v['bgcolor'] = '#FF4545';
                }
            }
        }
        return ['code' => 200 , 'msg' => '完成' , 'data'=>$order];
    }
}

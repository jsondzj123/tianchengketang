<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Enrolment;

class StudentController extends Controller {
    /*
     * @param  description   添加学员的方法
     * @param  参数说明       body包含以下参数[
     *     phone        手机号
     *     real_name    学员姓名
     *     sex          性别(1男,2女)
     *     papers_type  证件类型(1代表身份证,2代表护照,3代表港澳通行证,4代表台胞证,5代表军官证,6代表士官证,7代表其他)
     *     papers_num   证件号码
     *     birthday     出生日期
     *     address_locus户口所在地
     *     age          年龄
     *     educational  学历(1代表小学,2代表初中,3代表高中,4代表大专,5代表大本,6代表研究生,7代表博士生,8代表博士后及以上)
     *     family_phone 家庭电话号
     *     office_phone 办公电话
     *     contact_people  紧急联系人
     *     contact_phone   紧急联系电话
     *     email           邮箱
     *     qq              QQ号码
     *     wechat          微信
     *     address         地址
     *     remark          备注
     * ]
     * @param author    dzj
     * @param ctime     2020-04-28
     * return string
     */
    public function doInsertStudent() {
        //获取提交的参数
        try{
            $data = Student::doInsertStudent(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '添加成功']);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
    /*
     * @param  description   更新学员的方法
     * @param  参数说明       body包含以下参数[
     *     student_id   学员id
     *     phone        手机号
     *     real_name    学员姓名
     *     sex          性别(1男,2女)
     *     papers_type  证件类型(1代表身份证,2代表护照,3代表港澳通行证,4代表台胞证,5代表军官证,6代表士官证,7代表其他)
     *     papers_num   证件号码
     *     birthday     出生日期
     *     address_locus户口所在地
     *     age          年龄
     *     educational  学历(1代表小学,2代表初中,3代表高中,4代表大专,5代表大本,6代表研究生,7代表博士生,8代表博士后及以上)
     *     family_phone 家庭电话号
     *     office_phone 办公电话
     *     contact_people  紧急联系人
     *     contact_phone   紧急联系电话
     *     email           邮箱
     *     qq              QQ号码
     *     wechat          微信
     *     address         地址
     *     remark          备注
     * ]
     * @param author    dzj
     * @param ctime     2020-04-28
     * return string
     */
    public function doUpdateStudent() {
        //获取提交的参数
        try{
            $data = Student::doUpdateStudent(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '更改成功']);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
    /*
     * @param  descriptsion    根据学员id获取详细信息
     * @param  参数说明         body包含以下参数[
     *     student_id   学员id
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-27
     * return  array
     */
    public function getStudentInfoById(){
        //获取提交的参数
        try{
            $data = Student::getStudentInfoById(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取学员信息成功' , 'data' => $data['data']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
    /*
     * @param  descriptsion    账号启用/禁用方法
     * @param  参数说明         body包含以下参数[
     *      student_id   学员id
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-28
     */
    public function doForbidStudent(){
        //获取提交的参数
        try{
            $data = Student::doForbidStudent(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '操作成功']);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
    /*
     * @param  description   学员报名的方法
     * @param  参数说明       body包含以下参数[
     *     student_id     学员id
     *     parent_id      学科分类id
     *     lession_id     课程id
     *     lession_price  课程原价
     *     student_price  学员价格
     *     payment_type   付款类型
     *     payment_method 付款方式
     *     payment_fee    付款金额
     * ]
     * @param author    dzj
     * @param ctime     2020-04-28
     * return string
     */
    public function doStudentEnrolment(){
        //获取提交的参数
        try{
            $data = Enrolment::doStudentEnrolment(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '报名成功']);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
        /*
     * @param  descriptsion    获取学员列表
     * @param  参数说明         body包含以下参数[
     *     student_id   学员id
     *     is_forbid    账号状态
     *     state_status 开课状态
     *     real_name    姓名
     *     paginate     每页显示条数
     * ]
     * @param  author          dzj
     * @param  ctime           2020-04-27
     * return  array
     */
    public function getStudentList(){
        //获取提交的参数
        try{
            //判断token或者body是否为空
            /*if(!empty($request->input('token')) && !empty($request->input('body'))){
                $rsa_data = app('rsa')->servicersadecrypt($request);
            } else {
                $rsa_data = [];
            }*/
            
            //获取全部学员列表
            $data = Student::getStudentList(self::$accept_data);
            if($data['code'] == 200){
                return response()->json(['code' => 200 , 'msg' => '获取学员列表成功' , 'data' => $data['data']]);
            } else {
                return response()->json(['code' => $data['code'] , 'msg' => $data['msg']]);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
    /*
     * @param  description   学员公共参数列表
     * @param  author        dzj
     * @param  ctime         2020-04-30
     */
    public function getStudentCommonList(){
        //证件类型
        $papers_type_array = [[
                'id'  =>  1 ,
                'name'=> '身份证'
            ] ,
            [
                'id'  =>  2 ,
                'name'=> '护照'
            ] ,
            [
                'id'  =>  3 ,
                'name'=> '港澳通行证'
            ],
            [
                'id'  =>  4 ,
                'name'=> '台胞证'
            ],
            [
                'id'  =>  5 ,
                'name'=> '军官证'
            ],
            [
                'id'  =>  6 ,
                'name'=> '士官证'
            ],
            [
                'id'  =>  7 ,
                'name'=> '其他'
            ]
        ];
        
        //学历
        $educational_array = [
            [
                'id'  =>  1 ,
                'name'=> '小学'
            ] ,
            [
                'id'  =>  2 ,
                'name'=> '初中'
            ] ,
            [
                'id'  =>  3 ,
                'name'=> '高中'
            ],
            [
                'id'  =>  4 ,
                'name'=> '大专'
            ],
            [
                'id'  =>  5 ,
                'name'=> '本科'
            ],
            [
                'id'  =>  6 ,
                'name'=> '研究生'
            ],
            [
                'id'  =>  7 ,
                'name'=> '博士生'
            ],
            [
                'id'  =>  8 ,
                'name'=>  '博士后及以上'
            ]
        ];
        
        //付款方式
        $payment_method = [
            [
                'id'  =>  1 ,
                'name'=> '微信'
            ] ,
            [
                'id'  =>  2 ,
                'name'=> '支付宝'
            ] ,
            [
                'id'  =>  3 ,
                'name'=> '银行转账'
            ]
        ];
        
        //付款类型
        $payment_type = [
            [
                'id'  =>  1 ,
                'name'=> '定金'
            ] ,
            [
                'id'  =>  2 ,
                'name'=> '尾款'
            ] ,
            [
                'id'  =>  3 ,
                'name'=> '最后一次尾款'
            ],
            [
                'id'  =>  4 ,
                'name'=> '全款'
            ]
        ];
        return response()->json(['code' => 200 , 'msg' => '返回数据成功' , 'data' => ['papers_type_list' => $papers_type_array , 'educational_list' => $educational_array , 'payment_method' => $payment_method , 'payment_type' => $payment_type]]);
    }
}

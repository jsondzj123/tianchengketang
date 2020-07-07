<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


class UserController extends Controller {
    /*
     * @param  description   用户详情信息
     * @param author    dzj
     * @param ctime     2020-05-23
     * return string
     */
    public function getUserInfoById() {
        //获取提交的参数
        try{
            //根据用户id获取用户详情
            $user_info = Student::select("id as user_id" , "token  as user_token" , "user_type" , "head_icon" , "real_name" , "phone" , "nickname" , "sign" , "papers_type" , "papers_num" , "balance" , "school_id")->find(self::$accept_data['user_info']['user_id']);
            if($user_info && !empty($user_info)){
                //证件名称
                $user_info['papers_name']  = $user_info['papers_type'] > 0 ? parent::getPapersNameByType($user_info['papers_type']) : '';
                //余额
                $user_info['balance']      = floatval($user_info['balance']);
                $user_info['user_token']      = self::$accept_data['user_info']['user_token'];
                return response()->json(['code' => 200 , 'msg' => '获取学员信息成功' , 'data' => ['user_info' => $user_info]]);
            } else {
                return response()->json(['code' => 203 , 'msg' => '获取学员信息失败']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*
     * @param  description   用户更新信息方法
     * @param  参数说明       body包含以下参数[
     *     head_icon             头像(非必传)
     *     real_name             姓名(非必传)
     *     nickname              昵称(非必传)
     *     sign                  签名(非必传)
     *     papers_name           证件名称(非必传)
     *     papers_num            证件号码(非必传)
     * ]
     * @param author    dzj
     * @param ctime     2020-05-25
     * return string
     */
    public function doUserUpdateInfo() {
        //获取提交的参数
        try{
            $body = self::$accept_data;
            //判断传过来的数组数据是否为空
            if(!$body || !is_array($body)){
                return response()->json(['code' => 202 , 'msg' => '传递数据不合法']);
            }

            //获取请求的平台端
            $platform = verifyPlat() ? verifyPlat() : 'pc';

            //hash中的token的key值
            $token_key   = "user:regtoken:".$platform.":".$body['user_token'];

            //空数组赋值
            $where = [];

            //判断头像是否为空
            if(isset($body['head_icon']) && !empty($body['head_icon'])){
                $where['head_icon'] = $body['head_icon'];
                //设置redis的头像值
                Redis::hSet($token_key , 'head_icon' , $body['head_icon']);
            }

            //判断姓名是否为空
            if(isset($body['real_name']) && !empty($body['real_name'])){
                $where['real_name'] = $body['real_name'];
                //设置redis的姓名值
                Redis::hSet($token_key , 'real_name' , $body['real_name']);
            }

            //判断昵称是否为空
            if(isset($body['nickname']) && !empty($body['nickname'])){
                $where['nickname']  = $body['nickname'];
                //设置redis的昵称值
                Redis::hSet($token_key , 'nickname' , $body['nickname']);
            }

            //判断签名是否为空
            if(isset($body['sign']) && !empty($body['sign'])){
                $where['sign']      = $body['sign'];
                //设置redis的签名值
                Redis::hSet($token_key , 'sign' , $body['sign']);
            }

            //判断证件名称是否为空
            if(isset($body['papers_name']) && !empty($body['papers_name'])){
                //根据证件名称获取证件类的id
                $papers_type = array_search($body['papers_name'], [1=>'身份证' , 2=>'护照' , 3=>'港澳通行证' , 4=>'台胞证' , 5=>'军官证' , 6=>'士官证' , 7=>'其他']);
                $where['papers_type'] = $papers_type ? $papers_type : 0;
                //设置redis的证件值
                Redis::hMset($token_key , ['papers_type' => $where['papers_type'] , 'papers_name' => parent::getPapersNameByType($where['papers_type'])]);
            }

            //判断证件号码是否为空
            if(isset($body['papers_num']) && !empty($body['papers_num'])){
                $where['papers_num'] = $body['papers_num'];
                //设置redis的证件号码值
                Redis::hSet($token_key , 'papers_num' , $body['papers_num']);
            }
            $where['update_at']  = date('Y-m-d H:i:s');

            //开启事务
            DB::beginTransaction();

            //更新用户信息
            $rs = Student::where("id" , $body['user_info']['user_id'])->update($where);
            if($rs && !empty($rs)){
                //事务提交
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '更新成功']);
            } else {
                //事务回滚
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '更新失败']);
            }
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }


    /*
     * @param  description   用户退出登录接口
     * @param author    dzj
     * @param ctime     2020-06-01
     * return string
     */
    public function doLoginOut(){
        try {
            //获取用户token
            $token   =   self::$accept_data['user_info']['user_token'];

            //获取请求的平台端
            $platform = verifyPlat() ? verifyPlat() : 'pc';

            //hash中的token的key值
            $token_key   = "user:regtoken:".$platform.":".$token;
            $token_phone = "user:regtoken:".$platform.":".self::$accept_data['user_info']['phone'];

            //删除redis中用户token
            Redis::del($token_key);
            Redis::del($token_phone);
            return response()->json(['code' => 200 , 'msg' => '退出成功']);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
}

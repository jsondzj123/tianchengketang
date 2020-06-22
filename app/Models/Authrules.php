<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Authrules extends Model {
    //指定别的表名   权限表
    public $table = 'ld_auth_rules';
    //时间戳设置
    public $timestamps = false;
        /*
         * @param  descriptsion 权限查询
         * @param  $name    url名
         * @param  author  苏振文
         * @param  ctime   2020/4/25 15:51
         * return  array
         */
    public static function getAuthOne($name){

        $return = self::where(['name'=>$name])->first();
        return $return;
    }
    /*
     * @param  descriptsion 权限查询(全部)
     * @param  $auth_id     权限id组
     * @param  author      lys
     * @param  ctime   2020/4/27 15:00
     * return  array
     */
    public static function getAdminAuthAll($auth_id){
        //判断权限id是否为空
        if(empty($auth_id)){
            return ['code'=>202,'msg'=>'参数类型有误'];
        }
        $auth_id_arr = explode(',',$auth_id);
        if(!$auth_id_arr){
             $auth_id_arr = [$auth_id];
        }
        $authArr = self::whereIn('id',$auth_id_arr)->where(['is_del'=>1,'is_show'=>1,'is_forbid'=>1])->select('id','name','title','parent_id')->get()->toArray();
        $arr = [];
        foreach($authArr as $k=>$v){
            if($v['parent_id'] == 0){
                $arr[] = $v;    
            }else{
                foreach ($arr as $key => $value) {
                    if($v['parent_id'] == $value['id']){
                        unset($v['id']);
                        
                        $arr[$key]['child_array'][] = $v;
                    }
                }
            }
        }
     
        if($arr){
            return ['code'=>200,'msg'=>'获取权限信息成功','data'=>$arr];
        }else{
            return ['code'=>204,'msg'=>'权限信息不存在'];
        }
    }
        /*
         * @param  descriptsion 获取权限列表
         * @param  $where[
                    'id' => 权限id串
                    ...
                ]  查询条件
         * @param  $field  字段
         * @param  author  lys
         * @param  ctime   2020/4/30
         * return  array
         */
    public static function getAuthAlls($where=[],$field=['*']){
        return  self::where(function($query) use ($where){ 
            if(isset($where['id']) && $where['id'] != ''  ){
                $query->whereIn('id',$where['id']);
            }
        })->select($field)->get()->toArray();        
    }

   
}

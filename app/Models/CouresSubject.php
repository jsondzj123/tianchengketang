<?php
namespace App\Models;

use App\Tools\CurrentAdmin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use phpDocumentor\Reflection\Types\Self_;

class CouresSubject extends Model {
    //指定别的表名
    public $table = 'ld_course_subject';
    //时间戳设置
    public $timestamps = false;
    /*
         * @param  列表
         * @param  school_status  1总校其他是分校
         * @param  school_id  分校id
         * @param  author  苏振文
         * @param  ctime   2020/6/24 10:44
         * return  array
         */
    public static function subjectList($id,$school_status = 1,$school_id = 1){
        $where['is_del'] = 0;
        $where['parent_id'] = 0;
       if($school_status != 1){
           $where['school_id'] = $school_id;
       }
       if($id != 0){
           $one =self::select('id','subject_name','description','is_open')
               ->where($where)
               ->get();
           $two = self::select('id','subject_name','description','is_open')
               ->where(['parent_id'=>$id,'is_del'=>0])->get();
           $list['one'] = $one;
           $list['two'] = $two;
           return ['code' => 200 , 'msg' => '获取成功','data'=>$list];
       }else{
           $list =self::select('id','subject_name','description','is_open')
               ->where($where)
               ->get();
           foreach ($list as $k=>&$v){
               $sun = self::select('id','subject_name','is_open')
                   ->where(['parent_id'=>$v['id']])->get();
               $v['subset'] = $sun;
           }
           return ['code' => 200 , 'msg' => '获取成功','data'=>$list];
       }
    }
    //添加
    public static function subjectAdd($user_id,$school_id,$data){
        //判断学科大类的唯一性
        $find = self::where(['admin_id'=>$user_id,'school_id'=>$school_id,'subject_name'=>$data['subject_name'],'is_del'=>0])->first();
        if($find){
            return ['code' => 203 , 'msg' => '此学科大类已存在'];
        }
        $add = self::insert(['admin_id' => $user_id,
                          'parent_id' => $data['parent_id'],
                          'school_id' => $school_id,
                          'subject_name' => $data['subject_name'],
                          'subject_cover' => $data['subject_cover'],
                          'description' => $data['description']
                ]);
        if($add){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'subjectAdd' ,
                'route_url'      =>  'admin/Coursesubject/subjectAdd' ,
                'operate_method' =>  'add' ,
                'content'        =>  '添加操作'.json_encode($data) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return ['code' => 200 , 'msg' => '添加成功'];
        }else{
            return ['code' => 203 , 'msg' => '添加失败'];
        }
    }
    //删除
    public static function subjectDel($user_id,$data){
        $find = self::where(['id'=>$data['id']])->first();
        if($find['is_del'] == 1){
            return ['code' => 200 , 'msg' => '已删除'];
        }
        $del = self::where(['id'=>$data['id']])->update(['is_del'=>1]);
        if($del){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'subjectDel' ,
                'route_url'      =>  'admin/Coursesubject/subjectDel' ,
                'operate_method' =>  'delete' ,
                'content'        =>  '删除操作'.json_encode($data) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return ['code' => 200 , 'msg' => '删除成功'];
        }else{
            return ['code' => 203 , 'msg' => '删除失败'];
        }
    }
    //单条详情
    public static function subjectOnes($data){
        $find = self::where(['id'=>$data['id'],'is_del'=>0])->first();
        if(!$find){
            return ['code' => 202 , 'msg' => '无此信息'];
        }
        return ['code' => 200 , 'msg' => '获取成功', 'data'=>$find];
    }
    //修改
    public static function subjectUpdate($user_id,$data){
        $data['update_at'] = date('Y-m-d H:i:s');
        $update = self::where(['id'=>$data['id']])->update($data);
        if($update){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'subjectUpdate' ,
                'route_url'      =>  'admin/Coursesubject/subjectUpdate' ,
                'operate_method' =>  'Update' ,
                'content'        =>  '修改操作'.json_encode($data) ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return ['code' => 200 , 'msg' => '修改成功'];
        }else{
            return ['code' => 202 , 'msg' => '修改失败'];
        }
    }
    //学科上架下架
    public static function subjectForStatus($user_id,$data){
        $find = self::where(['id'=>$data['id'],'is_del'=>0])->first();
        if(!$find){
            return ['code' => 202 , 'msg' => '无此信息'];
        }
        $status = $find['is_open'] == 1?0:1;
        $up = self::where(['id'=>$data['id']])->update(['is_open'=>$status,'update_at'=>date('Y-m-d H:i:s')]);
        if($up){
            //添加日志操作
            AdminLog::insertAdminLog([
                'admin_id'       =>   $user_id  ,
                'module_name'    =>  'subjectUpdate' ,
                'route_url'      =>  'admin/Coursesubject/subjectUpdate' ,
                'operate_method' =>  'Update' ,
                'content'        =>  '学科上架下架操作'.json_encode($data).'修改状态为'.$status ,
                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
                'create_at'      =>  date('Y-m-d H:i:s')
            ]);
            return ['code' => 200 , 'msg' => '修改成功'];
        }else{
            return ['code' => 202 , 'msg' => '修改失败'];
        }
    }
}

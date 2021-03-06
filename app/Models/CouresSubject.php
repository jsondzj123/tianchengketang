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
    public static function subjectList($school_status = 1,$school_id = 1){
        $where['is_del'] = 0;
        $where['parent_id'] = 0;
       if($school_status != 1){
           $where['school_id'] = $school_id;
       }
       $list =self::select('id','subject_name','description','is_open')
           ->where($where)
           ->get()->toArray();
       foreach ($list as $k=>&$v){
           $sun = self::select('id','subject_name','is_open')
               ->where(['parent_id'=>$v['id'],'is_del'=>0])->get();
           $v['subset'] = $sun;
       }
       return ['code' => 200 , 'msg' => '获取成功','data'=>$list];
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
                          'subject_cover' => isset($data['subject_cover'])?$data['subject_cover']:'',
                          'description' => isset($data['description'])?$data['description']:''
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
        //判断此学科是否有在售课程
        $find = self::where(['id'=>$data['id']])->first();
        if($find['parent_id'] != 0){
            $course1 = Coures::where(['child_id'=>$data['id'],'is_del'=>0,'status'=>1])->count();
            $course2 = CourseSchool::where(['child_id'=>$data['id'],'is_del'=>0,'status'=>1])->count();
            $course = $course1 + $course2;
        }else{
            $course1 = Coures::where(['parent_id'=>$data['id'],'is_del'=>0,'status'=>1])->count();
            $course2 = CourseSchool::where(['parent_id'=>$data['id'],'is_del'=>0,'status'=>1])->count();
            $course = $course1 + $course2;
        }
        if($course != 0){
            return ['code' => 202 , 'msg' => '关联的课程在售无法删除，请确认'];
        }
        $del = self::where(['id'=>$data['id']])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
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
        unset($data['/admin/coursesubject/subjectUpdate']);
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
        if($status == 1){
            //判断此学科是否有在售课程
            if($find['parent_id'] != 0){
                $course1 = Coures::where(['child_id'=>$data['id'],'is_del'=>0,'status'=>1])->count();
                $course2 = CourseSchool::where(['child_id'=>$data['id'],'is_del'=>0,'status'=>1])->count();
                $course = $course1 + $course2;
            }else{
                $course1 = Coures::where(['parent_id'=>$data['id'],'is_del'=>0,'status'=>1])->count();
                $course2 = CourseSchool::where(['child_id'=>$data['id'],'is_del'=>0,'status'=>1])->count();
                $course = $course1 + $course2;
            }
            if($course != 0){
                return ['code' => 202 , 'msg' => '关联的课程在售无法关闭，请确认'];
            }
        }
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
    //课程模块 条件显示
    public static function couresWhere($data){
        //获取用户学校
        $school_id = isset($data['school_id']) && $data['school_id'] != 0?$data['school_id']:AdminLog::getAdminInfo()->admin_user->school_id;
        $one = self::select('id','parent_id','admin_id','school_id','subject_name as name','subject_cover as cover','subject_cover as cover','description','is_open','is_del','create_at')
            ->where(['is_del'=>0,'is_open'=>0,'school_id'=>$school_id])
            ->get()->toArray();
        //根据授权课程 获取分类
        $course = CourseSchool::select('parent_id')->where(['to_school_id'=>$school_id,'is_del'=>0])->groupBy('parent_id')->get()->toArray();
        $two=[];
        if(!empty($course)){
            foreach ($course as $k=>$v){
                $twos = self::select('id','parent_id','admin_id','school_id','subject_name as name','subject_cover as cover','subject_cover as cover','description','is_open','is_del','create_at')->where(['id'=>$v['parent_id'],'is_del'=>0,'is_open'=>0])->first();
                $twsss = self::select('id','parent_id','admin_id','school_id','subject_name as name','subject_cover as cover','subject_cover as cover','description','is_open','is_del','create_at')->where(['parent_id'=>$twos['id'],'is_del'=>0,'is_open'=>0])->get()->toArray();
//                $course = CourseSchool::select('parent_id')->where(['to_school_id'=>$school_id,'is_del'=>0])->groupBy('child_id')->get()->toArray();
//                if(!empty($course)){
//                    foreach ($twsss as $ks=>$vs){
//                        if(!in_array($vs,$course)){
//                            unset($twsss[$ks]);
//                        }
//                    }
                    $twos['childs'] = $twsss;
//                }else{
//                    $twos['childs'] = [];
//                }
                $two[] =$twos;
            }
        }
        $list = self::demo($one,0,0);
        if(!empty($list) && !empty($two)){
            $listss = array_merge($list,$two);
        }else{
            $listss = !empty($list)?$list:$two;
        }
        return ['code' => 200 , 'msg' => '获取成功','data'=>$listss];
    }
    //资源模块 条件显示
    public static function couresWheres(){
        //获取用户学校
        $school_id = AdminLog::getAdminInfo()->admin_user->school_id;
        $one = self::select('id','parent_id','admin_id','school_id','subject_name as name','subject_cover as cover','subject_cover as cover','description','is_open','is_del','create_at')
            ->where(['is_del'=>0,'school_id'=>$school_id])
            ->get()->toArray();
        foreach ($one as $ks=>&$vs){
            $vs['nature'] =0;
            $vs['nature_status'] =false;
        }
        //根据授权课程 获取分类
        $course = CourseSchool::select('parent_id')->where(['to_school_id'=>$school_id,'is_del'=>0])->groupBy('parent_id')->get()->toArray();
        $two=[];
        if(!empty($course)){
            foreach ($course as $k=>$v){
                $twos = self::select('id','parent_id','admin_id','school_id','subject_name as name','subject_cover as cover','subject_cover as cover','description','is_open','is_del','create_at')->where(['id'=>$v['parent_id'],'is_del'=>0])->first();
                $twos['nature'] = 1;
                $twos['nature_status'] = true;
                $twsss = self::select('id','parent_id','admin_id','school_id','subject_name as name','subject_cover as cover','subject_cover as cover','description','is_open','is_del','create_at')->where(['parent_id'=>$v['parent_id'],'is_del'=>0])->get()->toArray();
                $twos['childs'] = $twsss;
                $two[] =$twos;
            }
        }
        $list = self::demo($one,0,0);
        if(!empty($list) && !empty($two)){
            $listss = array_merge($list,$two);
        }else{
            $listss = !empty($list)?$list:$two;
        }
        return ['code' => 200 , 'msg' => '获取成功','data'=>$listss];
    }

    //递归
    public static function demo($arr,$id,$level){
        $list =array();
        foreach ($arr as $k=>$v){
            if ($v['parent_id'] == $id){
                $aa = self::demo($arr,$v['id'],$level+1);
                if(!empty($aa)){
                    $v['level']=$level;
                    $v['childs'] = $aa;
                }
                $list[] = $v;
            }
        }
        return $list;
    }
}

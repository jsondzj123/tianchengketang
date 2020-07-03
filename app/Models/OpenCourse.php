<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;
use App\Models\OpenCourseTeacher;
use App\Models\Teacher;
use App\Models\Admin;
use App\Models\CouresSubject;
use App\Tools\CurrentAdmin;
class OpenCourse extends Model {
    //指定别的表名
    public $table = 'ld_course_open';
    //时间戳设置
    public $timestamps = false;

    //错误信息
    public static function message()
    {
        return [
            'openless_id.required'  => json_encode(['code'=>'201','msg'=>'公开课标识不能为空']),
            'openless_id.integer'   => json_encode(['code'=>'202','msg'=>'公开课标识类型不合法']),
            'page.required'  => json_encode(['code'=>'201','msg'=>'页码不能为空']),
            'page.integer'   => json_encode(['code'=>'202','msg'=>'页码类型不合法']),
            'limit.required' => json_encode(['code'=>'201','msg'=>'显示条数不能为空']),
            'limit.integer'  => json_encode(['code'=>'202','msg'=>'显示条数类型不合法']),
            'subject.required' => json_encode(['code'=>'201','msg'=>'学科标识不能为空']),
            'title.required' => json_encode(['code'=>'201','msg'=>'课程标题不能为空']),
            // 'name.unique' => json_encode(['code'=>'205','msg'=>'学校名称已存在']),
            'keywords.required' => json_encode(['code'=>'201','msg'=>'课程关键字不能为空']),
            'cover.required' => json_encode(['code'=>'201','msg'=>'课程封面不能为空']),
            'time.required' => json_encode(['code'=>'201','msg'=>'开课时间段不能为空']),
            'is_barrage.required' => json_encode(['code'=>'201','msg'=>'弹幕ID不能为空']),
            'is_barrage.integer' => json_encode(['code'=>'202','msg'=>'弹幕ID不合法']),
            'live_type.required' => json_encode(['code'=>'201','msg'=>'直播类型不能为空']),
            'live_type.integer' => json_encode(['code'=>'202','msg'=>'直播类型不合法']),
            'edu_teacher_id.required'  => json_encode(['code'=>'201','msg'=>'教务标识不能为空']),
            'lect_teacher_id.required'  => json_encode(['code'=>'201','msg'=>'讲师标识不能为空']),
            'subject.required'  => json_encode(['code'=>'201','msg'=>'学科不能为空']),
            'introduce.required'  => json_encode(['code'=>'201','msg'=>'课程简介不能为空']),
        ];
    }

    /*
         * @param  descriptsion 获取公开课信息
         * @param  $school_id  公开课id
         * @param  author       lys
         * @param  ctime   2020/4/29 
         * return  array
         */
    public static function getOpenLessById($where,$field = ['*']){
        $openCourseInfo = self::where($where)->select($field)->first();
        if($openCourseInfo){
            return ['code'=>200,'msg'=>'获取课程信息成功','data'=>$openCourseInfo];
        }else{
            return ['code'=>204,'msg'=>'课程信息不存在'];
        }
    }
    /*
         * @param  descriptsion 获取公开课列表
         * @param  author       lys
         * @param  ctime   2020/4/29 
         * return  array
         */
    public static function getList($body){
        $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
        $pagesize = !isset($body['pagesize']) && $body['pagesize'] > 0 ? $body['pagesize'] : 15;
        $page     = !isset($body['page']) && $body['page'] > 0 ? $body['page'] : 1;
        $where['parent_id'] = !isset($body['parent_id'])|| empty($body['parent_id'])  ?'':$body['parent_id'];
        $where['child_id'] =  !isset($body['child_id']) || empty($body['child_id']) ?'':$body['child_id'];
        $where['status'] =  !isset($body['status']) || empty($body['status']) ?'':$body['status'];
        $where['time']  =  !isset($body['time']) || empty($body['time']) ?[]:json_decode($body['time'],1);
        $time = [];
        if(!empty($where['time']) ){
            $where['start_at'] =  $time[0];
            $where['end_at']  = $time[1];
        } 
        $offset   = ($page - 1) * $pagesize;
        $open_less_count = self::where(function($query) use ($where,$school_id){
            if(!empty($where['parent_id']) && $where['parent_id'] != '' && $where['parent_id'] > 0){
                $query->where('parent_id',$where['parent_id']);
            }
            if(!empty($where['child_id']) && $where['child_id'] != '' && $where['child_id'] > 0){
                $query->where('child_id',$where['child_id']);
            }
            if(!empty($where['status']) && $where['status'] != '' ){
                if($where['status'] == 1){
                    $query->where('status',0);
                }
                if($where['status'] == 2){
                    $query->where('status',1);  
                }
                if($where['status'] == 3){
                    $query->where('status',2); 
                }
            }
            if(!empty($where['time']) && $where['time'] != ''){
                $query->where('start_at','<',$where['start_at']);
                $query->where('end_at','>',$where['end_at']);
            }
            $query->where('school_id',$school_id);
            $query->where('is_del',0);
         })->count();
        
        $sum_page = ceil($open_less_count/$pagesize);
        if($open_less_count > 0){
            $open_less_arr = self::where(function($query) use ($where,$school_id){
                if(!empty($where['parent_id']) && $where['parent_id'] != '' && $where['parent_id'] >0){
                    $query->where('parent_id',$where['parent_id']);
                }
                if(!empty($where['child_id']) && $where['child_id'] != '' && $where['child_id'] > 0){
                    $query->where('child_id',$where['child_id']);
                }
                if(!empty($where['status']) && $where['status'] != '' ){
                    switch ($where['status']) {
                        case '1': $query->where('status',0);      break;
                        case '2': $query->where('status',1);      break;
                        case '3': $query->where('status',2);      break;
                    }
                }
                if(!empty($where['time']) && $where['time'] != ''){
                    $query->where('start_at','<',$where['start_at']);
                    $query->where('end_at','>',$where['end_at']);
                }
                $query->where('school_id',$school_id);
                $query->where('is_del',0);
            })->select('id','title','cover','start_at','end_at','is_recommend','status')->offset($offset)->limit($pagesize)->get();
            foreach ($open_less_arr as $k => $v) {
                $v['time'] = [date('Y-m-d H:i:s',$v['start_at']),date('Y-m-d H:i:s',$v['end_at'])];
                $teacherIdArr = OpenCourseTeacher::where('course_id',$v['id'])->where('is_del',0)->get(['teacher_id']);
                $v['teacher_name'] = Teacher::whereIn('id',$teacherIdArr)->where('is_del',0)->where('type',2)->first()['real_name'];
            }
            return ['code'=>200,'msg'=>'Success','data'=>['open_less_list' => $open_less_arr , 'total' => $open_less_count ]];          
        }
        return ['code'=>200,'msg'=>'Success','data'=>['open_less_list' => [] , 'total' => 0 ]];
    }

}



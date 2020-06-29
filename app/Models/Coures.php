<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Coures extends Model {
    //指定别的表名
    public $table = 'ld_course';
    //时间戳设置
    public $timestamps = false;
    //列表
    public static function courseList($data){
        //获取用户网校id
        $data['school_status'] = isset(AdminLog::getAdminInfo()->admin_user->school_status) ? AdminLog::getAdminInfo()->admin_user->school_status : 0;
        $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;
        //每页显示的条数
        $pagesize = (int)isset($data['pageSize']) && $data['pageSize'] > 0 ? $data['pageSize'] : 20;
        $page     = isset($data['page']) && $data['page'] > 0 ? $data['page'] : 1;
        $offset   = ($page - 1) * $pagesize;
        $count = $list = self::where(function($query) use ($data,$school_id) {
            //判断总校 查询所有或一个分校
            if($data['school_status'] == 1){
                if(!empty($data['school_id']) && $data['school_id'] != ''){
                    $query->where('school_id',$data['school_id']);
                }
            }else{
                //分校查询当前学校
                $query->where('school_id',$school_id);
            }
            //学科大类
            if(!empty($data['coursesubjectOne']) && $data['coursesubjectOne'] != ''){
                $query->where('parent_id',$data['coursesubjectOne']);
            }
            //学科小类
            if(!empty($data['coursesubjectTwo']) && $data['coursesubjectTwo'] != ''){
                $query->where('child_id',$data['coursesubjectTwo']);
            }
            //状态
            if(!empty($data['status']) && $data['status'] != ''){
                $query->where('status',$data['status']);
            }
            //属性
            if(!empty($data['nature']) && $data['nature'] != ''){
                $query->where('nature',$data['nature']);
            }
        })->count();
        if($count > 0){
            $list = self::where(function($query) use ($data,$school_id) {
                    //判断总校 查询所有或一个分校
                    if($data['school_status'] == 1){
                        if(!empty($data['school_id']) && $data['school_id'] != ''){
                            $query->where('school_id',$data['school_id']);
                        }
                    }else{
                        //分校查询当前学校
                        $query->where('school_id',$data['school_id']);
                    }
                    //学科大类
                    if(!empty($data['coursesubjectOne']) && $data['coursesubjectOne'] != ''){
                        $query->where('parent_id',$data['school_id']);
                    }
                    //学科小类
                    if(!empty($data['coursesubjectTwo']) && $data['coursesubjectTwo'] != ''){
                        $query->where('child_id',$data['school_id']);
                    }
                    //状态
                    if(!empty($data['status']) && $data['status'] != ''){
                        $query->where('status',$data['status']);
                    }
                    //属性
                    if(!empty($data['nature']) && $data['nature'] != ''){
                        $query->where('nature',$data['nature']);
                    }
                })
                ->orderBy('id','desc')
                ->offset($offset)->limit($pagesize)->get();
            foreach($list  as $k=>&$v){
                $where=[
                    'course_id'=>$v['id'],
                    'is_del'=>0
                ];
                if(!empty($data['method'])) {
                   $where['method_id'] = $data['method'];
                }
                $method = Couresmethod::select('method_id')->where($where)->get()->toArray();
                if(!$method){
                    unset($list[$k]);
                }else{
                    foreach ($method as $key=>&$val){
                        if($val['method_id'] == 1){
                            $val['method_name'] = '直播';
                        }
                        if($val['method_id'] == 2){
                            $val['method_name'] = '录播';
                        }
                        if($val['method_id'] == 3){
                            $val['method_name'] = '其他';
                        }
                    }
                    $v['method'] = $method;
                }
            }
            $page=[
                'pageSize'=>$pagesize,
                'page' =>$page,
                'total'=>$count
            ];
            return ['code' => 200 , 'msg' => '查询成功','data'=>$list,'where'=>$data,'page'=>$page];
        }
    }
    //添加
    public static function courseAdd($data){
        if(empty($data) || !isset($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['parent']) || empty($data['parent'])){
            return ['code' => 201 , 'msg' => '请选择学科'];
        }
        if(!isset($data['title']) || empty($data['title'])){
            return ['code' => 201 , 'msg' => '学科名称不能为空'];
        }
        if(!isset($data['cover']) || empty($data['cover'])){
            return ['code' => 201 , 'msg' => '学科封面不能为空'];
        }
        if(!isset($data['pricing']) || empty($data['pricing'])){
            return ['code' => 201 , 'msg' => '请填写课程原价'];
        }
        if(!isset($data['sale_price']) || empty($data['sale_price'])){
            return ['code' => 201 , 'msg' => '请填写课程优惠价'];
        }
        if(!isset($data['method']) || empty($data['method'])){
            return ['code' => 201 , 'msg' => '请选择授课方式'];
        }
        if(!isset($data['teacher']) || empty($data['teacher'])){
            return ['code' => 201 , 'msg' => '请选择讲师'];
        }
        if(!isset($data['describe']) || empty($data['describe'])){
            return ['code' => 201 , 'msg' => '课程描述不能为空'];
        }
        if(!isset($data['introduce']) || empty($data['introduce'])){
            return ['code' => 201 , 'msg' => '课程简介不能为空'];
        }
        $user_id = AdminLog::getAdminInfo()->admin_user->id;
        $school_id = AdminLog::getAdminInfo()->admin_user->school_id;
        DB::beginTransaction();
        //入课程表  课程授课表 课程讲师表

        $couser = self::insertGetId([
            'admin_id' => $user_id,
            'school_id' => $school_id,
            'parent_id' => $data['parent'][0],
            'child_id' => isset($data['parent'][1])?$data['parent'][1]:0,
            'title' => $data['title'],
            'keywords' => isset($data['keywords'])?$data['keywords']:'',
            'cover' => $data['cover'],
            'pricing' => $data['pricing'],
            'sale_price' => $data['sale_price'],
            'buy_num' => isset($data['buy_num'])?$data['buy_num']:0,
            'expiry' => isset($data['expiry'])?$data['expiry']:24,
            'describe' => $data['describe'],
            'introduce' => $data['introduce'],
        ]);
        if($couser){
            foreach ($data['method'] as $k=>$v){
                 Couresmethod::insert([
                    'course_id' => $couser,
                    'method_id' => $v
                ]);
            }
            foreach ($data['teacher'] as $k=>$v){
                 Couresteacher::insert([
                    'course_id' => $couser,
                    'teacher_id' => $v
                ]);
            }
            DB::commit();
            return ['code' => 200 , 'msg' => '添加成功'];
        }else{
            DB::rollback();
            return ['code' => 202 , 'msg' => '添加失败'];
        }
    }
    //删除
    public static function courseDel($data){
        if(empty($data) || !isset($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '请选择学科大类'];
        }
        $find = self::where(['id'=>$data['id']])->first();
        if($find['nature'] == 1){
            return ['code' => 203 , 'msg' => '授权课程，无法删除'];
        }
        $del = self::where(['id'=>$data['id']])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
        if($del){
            return ['code' => 200 , 'msg' => '删除成功'];
        }else{
            return ['code' => 201 , 'msg' => '删除失败'];
        }
    }
    //单条查询
    public static function courseFirst($data){
        if(empty($data) || !isset($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        if(!isset($data['id']) || empty($data['id'])){
            return ['code' => 201 , 'msg' => '请选择学科大类'];
        }
        $find = self::where(['id'=>$data['id'],'is_del'=>0])->first();
        if(!$find){
            return ['code' => 201 , 'msg' => '此数据不存在'];
        }
        //查询授权课程
        $method = Couresmethod::select('method_id')->where(['course_id'=>$data['id'],'is_del'=>0])->get()->toArray();
        foreach ($method as $key=>&$val){
            if($val['method_id'] == 1){
                $val['method_name'] = '直播';
            }
            if($val['method_id'] == 2){
                $val['method_name'] = '录播';
            }
            if($val['method_id'] == 3){
                $val['method_name'] = '其他';
            }
        }
        $find['method'] = $method;
        //查询讲师
        $teacher = Couresteacher::select('teacher_id')->where(['course_id'=>$data['id'],'is_del'=>0])->get()->toArray();
        if(!empty($teacher)){
            foreach ($teacher as $k=>&$v){
                $name = Lecturer::select('real_name')->where(['id'=>$v['teacher_id'],'is_del'=>0,'type'=>2])->first();
                $v['real_name'] = $name['real_name'];
            }
        }
        $find['teacher'] = $teacher;
        return ['code' => 200 , 'msg' => '查询成功','data'=>$find];
    }
    //修改
    public static function courseUpdate($data){
        if(empty($data) || !isset($data)){
            return ['code' => 201 , 'msg' => '传参数组为空'];
        }
        $find = self::where(['id'=>$data['id']])->first();
        if($find['nature'] == 1){
            return ['code' => 203 , 'msg' => '授权课程，无法修改'];
        }
        DB::beginTransaction();
        try{
        //修改 课程表 课程授课表 课程讲师表
        $cousermethod = isset($data['method'])?$data['method']:'';
        $couserteacher = isset($data['teacher'])?$data['teacher']:'';
        unset($data['/admin/course/courseUpdate']);
        unset($data['method']);
        unset($data['teacher']);
        self::where(['id'=>$data['id']])->update($data);
        if(!empty($cousermethod)){
            Couresmethod::where(['course_id'=>$data['id']])->update(['is_del'=>1]);
            $method = explode(',',$cousermethod);
            foreach ($method as $k=>$v){
                $infor = Couresmethod::where(['course_id'=>$data['id'],'method_id'=>$v])->first();
                if($infor){
                     Couresmethod::where(['id'=>$infor['id']])->update(['is_del'=>0]);
                }else{
                    Couresmethod::insert([
                        'course_id' => $data['id'],
                        'method_id' => $v
                    ]);
                }
            }
        }
        if(!empty($couserteacher)){
            Couresteacher::where(['course_id'=>$data['id']])->update(['is_del'=>1]);
            $teacher = explode(',',$couserteacher);
            foreach ($teacher as $k=>$v){
                $infor = Couresteacher::where(['course_id'=>$data['id'],'teacher_id'=>$v])->first();
                if($infor){
                     Couresteacher::where(['id'=>$infor['id']])->update(['is_del'=>0]);
                }else{
                    Couresteacher::insert([
                        'course_id' => $data['id'],
                        'teacher_id' => $v
                    ]);
                }
            }
        }
        DB::commit();
        return ['code' => 200 , 'msg' => '修改成功'];
        } catch (Exception $ex) {
            DB::rollback();
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }

    }
    //修改推荐状态
    public static function courseComment($data){
        $id = $data['id'];
        $find = self::where(['id'=>$id])->first();
        $recommend = $find['is_recommend'] == 1 ? 0:1;
        $up = self::where(['id'=>$id])->update(['is_recommend'=>$recommend]);
        if($up){
            return ['code' => 200 , 'msg' => '修改成功'];
        }else{
            return ['code' => 201 , 'msg' => '修改失败'];
        }
    }
}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Coureschapters extends Model {
    //指定别的表名
    public $table = 'ld_course_chapters';
    //时间戳设置
    public $timestamps = false;
    /*=============章================*/
    //章节列表
    public static function chapterList($data){
        $lists = self::where(['course_id'=>$data['course_id'],'is_del'=>0,])->get()->toArray();
        $arr = self::demo($lists,0,0);
        return ['code' => 200 , 'msg' => '查询成功','data'=>$arr];
    }
    //添加章  章名 课程id 学校id根据课程id查询
    public static function chapterAdd($data){
        $course = Coures::select('school_id','nature')->where(['id'=>$data['course_id']])->first();
        if($course['nature'] == 1){
            return ['code' => 202 , 'msg' => '授权课程，无法操作'];
        }
        $add = self::insert([
            'admin_id' => isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0,
            'school_id' => $course['school_id'],
            'course_id' => $data['course_id'],
            'name' => $data['name'],
        ]);
        if($add){
            return ['code' => 200 , 'msg' => '添加成功'];
        }else{
            return ['code' => 201 , 'msg' => '添加失败'];
        }
    }
    //删除章/节  课程id 章/节id
    public static function chapterDel($data){
        $course = Coures::select('school_id','nature')->where(['id'=>$data['course_id']])->first();
        if($course['nature'] == 1){
            return ['code' => 201 , 'msg' => '授权课程，无法操作'];
        }
        $del = self::where(['id'=>$data['id']])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
        if($del){
            return ['code' => 200 , 'msg' => '删除成功'];
        }else{
            return ['code' => 202 , 'msg' => '删除失败'];
        }

    }
    //修改章
    public static function chapterUpdate($data){
        if(!isset($data['name']) || empty($data['name'])){
            return ['code' => 201 , 'msg' => '请添加章名'];
        }
        $course = Coures::select('school_id','nature')->where(['id'=>$data['course_id']])->first();
        if($course['nature'] == 1){
            return ['code' => 201 , 'msg' => '授权课程，无法操作'];
        }
        $del = self::where(['id'=>$data['id']])->update(['name'=>$data['name'],'update_at'=>date('Y-m-d H:i:s')]);
        if($del){
            return ['code' => 200 , 'msg' => '修改成功'];
        }else{
            return ['code' => 202 , 'msg' => '修改失败'];
        }
    }

    /*============节==============*/
    //单条详情
    public static function sectionFirst($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '请传参'];
        }
        $list = self::where(['id'=>$data['section_id'],'is_del'=>0])->first();
        //查询录播课程名称
        $section = Couresmaterial::where(['parent_id'=>$data['section_id'],'mold'=>1,'is_del'=>0])->get();
        $list['filearr'] = $section;
        return ['code' => 200 , 'msg' => '查询成功','data'=>$list];
    }
    //添加节
    public static function sectionAdd($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '请传参'];
        }
        if(!isset($data['course_id']) || empty($data['course_id'])){
            return ['code' => 201 , 'msg' => '请选择课程'];
        }

        $course = Coures::select('school_id','nature')->where(['id'=>$data['course_id']])->first();
        if($course['nature'] == 1){
            return ['code' => 202 , 'msg' => '授权课程，无法操作'];
        }

        if(!isset($data['chapter_id']) || empty($data['chapter_id'])){
            return ['code' => 201 , 'msg' => '请选择章'];
        }
        if(!isset($data['type']) || empty($data['type'])){
            return ['code' => 201 , 'msg' => '请选择节类型'];
        }
        if(!isset($data['name']) || empty($data['name'])){
            return ['code' => 201 , 'msg' => '请填写节名称'];
        }
        if(!isset($data['resource_id']) || empty($data['resource_id'])){
            return ['code' => 201 , 'msg' => '请选择资源'];
        }
        try{
            DB::beginTransaction();
            $insert = self::insertGetId([
                'admin_id' => isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0,
                'school_id' => $course['school_id'],
                'parent_id' => $data['chapter_id'],
                'course_id' => $data['course_id'],
                'resource_id' => $data['resource_id'],
                'name' => $data['name'],
                'type' => $data['type'],
                'is_free' => isset($data['is_free'])?$data['is_free']:0
            ]);
            //判断小节资料
            if(!empty($data['filearr'])){
                foreach ($data['filearr'] as $k=>$v){
                    Couresmaterial::insert([
                        'admin_id' => isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0,
                        'school_id' => $course['school_id'],
                        'parent_id' => $insert,
                        'course_id' => $data['course_id'],
                        'type' => $v['type'],
                        'material_name' => $v['name'],
                        'material_size' => $v['size'],
                        'material_url' => $v['url'],
                        'mold' => 1,
                    ]);
                }
            }
            DB::commit();
            return ['code' => 200 , 'msg' => '添加成功'];
        } catch (Exception $ex) {
            DB::rollback();
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    //修改节
    public static function sectionUpdate($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '参数为空'];
        }
        unset($data['/admin/course/sectionUpdate']);
        $filearr = $data['filearr'];
        unset($data['filearr']);
        $up = self::where(['id'=>$data['id']])->update($data);
        if($up){
            if(!empty($filearr)){
                Couresmaterial::where(['parent_id'=>$data['id'],'mold'=>1])->update(['is_del'=>1]);
                foreach ($filearr as $k=>$v){
                    $materialones = Couresmaterial::where(['material_url'=>$v['url'],'mold'=>1])->first();
                    if($materialones){
                        Couresmaterial::where(['id'=>$materialones['id']])->update(['is_del'=>0]);
                    }else{
                        Couresmaterial::insert([
                            'admin_id' => isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0,
                            'school_id' => 0,
                            'parent_id' => $data['id'],
                            'course_id' => 0,
                            'type' => $v['type'],
                            'material_name' => $v['name'],
                            'material_size' => $v['size'],
                            'material_url' => $v['url'],
                            'mold' => 1,
                        ]);
                    }
                }
            }
            return ['code' => 200 , 'msg' => '修改成功'];
        }else{
            return ['code' => 202 , 'msg' => '修改失败'];
        }
    }
    //小节资料删除
    public static function sectionDataDel($data){
        if(!isset($data) || empty($data)){
            return ['code' => 201 , 'msg' => '参数为空'];
        }
        if(!isset($data['course_id']) || empty($data['course_id'])){
            return ['code' => 201 , 'msg' => '请选择课程'];
        }
        $course = Coures::select('nature')->where(['id'=>$data['course_id']])->first();
        if($course['nature'] == 1){
            return ['code' => 202 , 'msg' => '授权课程，无法操作'];
        }
        $del = Couresmaterial::where(['id'=>$data['id']])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
        if($del){
            return ['code' => 200 , 'msg' => '删除成功'];
        }else{
            return ['code' => 202 , 'msg' => '删除失败'];
        }
    }






    //递归
    public static function demo($arr,$id,$level){
        $list =array();
        foreach ($arr as $k=>$v){
            if ($v['parent_id'] == $id){
                $v['level']=$level;
                $v['son'] = self::demo($arr,$v['id'],$level+1);
                $list[] = $v;
            }
        }
        return $list;
    }
}
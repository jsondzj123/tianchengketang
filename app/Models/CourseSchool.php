<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class CourseSchool extends Model {
    //指定别的表名
    public $table = 'ld_course_school';
    //时间戳设置
    public $timestamps = false;


    //错误信息
    public static function message(){
        return [
            'page.required'  => json_encode(['code'=>'201','msg'=>'页码不能为空']),
            'page.integer'   => json_encode(['code'=>'202','msg'=>'页码类型不合法']),
            'limit.required' => json_encode(['code'=>'201','msg'=>'显示条数不能为空']),
            'limit.integer'  => json_encode(['code'=>'202','msg'=>'显示条数类型不合法']),
            'type.required' => json_encode(['code'=>'201','msg'=>'分类类型不能为空']),
            'type.integer'  => json_encode(['code'=>'202','msg'=>'分类类型不合法']),
            'school_id.required' => json_encode(['code'=>'201','msg'=>'学校标识不能为空']),
            'school_id.integer'  => json_encode(['code'=>'202','msg'=>'学校标识类型不合法']),
            'course_id.required' => json_encode(['code'=>'201','msg'=>'课程标识不能为空']),
            'course_id.integer'  => json_encode(['code'=>'202','msg'=>'课程标识类型不合法']),
            'is_public.required' => json_encode(['code'=>'201','msg'=>'课程类型标识不能为空']),
            'course_id.integer'  => json_encode(['code'=>'202','msg'=>'课程标识类型不合法']),
            'subjectOne.required' => json_encode(['code'=>'201','msg'=>'学科大类标识不能为空']),
        ];
    }
    /**
     * @param  授权课程IDs
     * @param  school_id
     * @param  author  李银生
     * @param  ctime   2020/6/30
     * @return  array 7.4 调整
     */
    public static function courseIds($body){
        $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前登陆学校id
        if($body['is_public'] == 1){ //公开课
            $openCourseIds['ids'] = CourseRefOpen::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->pluck('course_id');
            $openCourseIds['is_public'] = 1;
            return ['code'=>200,'msg'=>'Success','data'=>$openCourseIds];
        }   
        if($body['is_public'] == 0){ //课程
            $courseIds['ids'] = self::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->pluck('course_id');
            $courseIds['is_public'] = 0;
            return ['code'=>200,'msg'=>'Success','data'=>$courseIds];
        }
    }

   /**
     * @param  授权课程列表
     * @param  school_id
     * @param  author  李银生
     * @param  ctime   2020/6/30
     * @return  array 7.4 调整
     */
    public static function courseList($body){
    	$school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前登陆学校id
        $OpenCourseArr =   $natureOpenCourse = $courseArr = $natureCourseArr = [];
        $zizengSubjectArr = CouresSubject::where('school_id',$school_id)->where(['is_open'=>0,'is_del'=>0])->select('id','subject_name')->get()->toArray();//自增大类小类（总校）
        $subjectArr = array_column($zizengSubjectArr,'subject_name','id');
        if($body['is_public'] == 1){//公开课

            $zizengOpenCourse = OpenCourse::where(function($query) use ($body,$school_id) {
                            if(!empty($body['subjectOne']) && $body['subjectOne'] != ''){
                                $query->where('parent_id',$body['subjectOne']);
                            }
                            if(!empty($body['subjectTwo']) && $body['subjectTwo'] != ''){
                                $query->where('child_id',$body['subjectTwo']);
                            }
                            if(!empty($body['search']) && $body['search'] != ''){
                                $query->where('title','like',"%".$body['search']."%");
                            }
                            $query->where('is_del',0);
                            $query->where('school_id',$school_id);
                        })->select('id','parent_id','child_id','title')->get()->toArray();//自增公开课信息（总校）
            $natureOpenCourse = CourseRefOpen::leftJoin('ld_course_open','ld_course_open.id','=','ld_course_ref_open.course_id')
                            ->where(function($query) use ($body,$school_id) {
                                if(!empty($body['subjectOne']) && $body['subjectOne'] != ''){
                                    $query->where('ld_course_open.parent_id',$body['subjectOne']);
                                }
                                if(!empty($body['subjectTwo']) && $body['subjectTwo'] != ''){
                                    $query->where('ld_course_open.child_id',$body['subjectTwo']);
                                }
                                if(!empty($body['search']) && $body['search'] != ''){
                                    $query->where('ld_course_open.title','like',"%".$body['search']."%");
                                }
                                $query->where('ld_course_ref_open.to_school_id',$body['school_id']);
                                $query->where('ld_course_ref_open.from_school_id',$school_id);
                                $query->where('ld_course_ref_open.is_del',0);
                        })->select('ld_course_ref_open.course_id as id','ld_course_open.parent_id','ld_course_open.child_id','ld_course_open.title')->get()->toArray(); //授权公开课信息（分校）

                if(!empty($zizengOpenCourse)){
                    if(!empty($natureOpenCourse)){
                        $natureOpenCourseIds = array_column($natureOpenCourse,'id');
                        foreach($zizengOpenCourse as $key=>&$v){
                            if(in_array($v['id'], $natureOpenCourseIds)){
                                unset($zizengOpenCourse[$key]);
                            }
                        }
                    }
                    $OpenCourseArr = array_merge($zizengOpenCourse,$natureOpenCourse);
                    foreach ($OpenCourseArr as $key => $v) {
                        $OpenCourseArr[$key]['subjectNameOne'] = !isset($subjectArr[$v['parent_id']])?'':$subjectArr[$v['parent_id']];
                        $OpenCourseArr[$key]['subjectNameTwo'] = !isset($subjectArr[$v['child_id']])?'':$subjectArr[$v['child_id']];
                        $OpenCourseArr[$key]['method'] = ['直播'];
                    }
                }
                return ['code'=>200,'msg'=>'message','data'=>$OpenCourseArr];
            }
            
        if($body['is_public'] == 0){//课程
            $CourseArr = [];
            $zizengCourse = Coures::where(['school_id'=>$school_id,'nature'=>0])  //自增课程(总校)
                ->where(function($query) use ($body) {
                    if(!empty($body['subjectOne']) && $body['subjectOne'] != ''){
                        $query->where('parent_id',$body['subjectOne']);
                    }
                    if(!empty($body['subjectTwo']) && $body['subjectTwo'] != ''){
                        $query->where('child_id',$body['subjectTwo']);
                    }
                    if(!empty($body['search']) && $body['search'] != ''){
                        $query->where('title','like',"%".$body['search']."%");
                    }
                    $query->where('is_del',0);
                    $query->where('status',1);
                })->select('id','parent_id','child_id','title')->get()->toArray(); 
            $natureCourse = self::leftJoin('ld_course','ld_course.id','=','ld_course_school.course_id')
                ->where(function($query) use ($body,$school_id) {
                    if(!empty($body['subjectOne']) && $body['subjectOne'] != ''){
                        $query->where('ld_course.parent_id',$body['subjectOne']);
                    }
                    if(!empty($body['subjectTwo']) && $body['subjectTwo'] != ''){
                        $query->where('ld_course.child_id',$body['subjectTwo']);
                    }
                    if(!empty($body['search']) && $body['search'] != ''){
                        $query->where('ld_course.title','like',"%".$body['search']."%");
                    }
                    $query->where('ld_course_school.to_school_id',$body['school_id']); //被授权学校
                    $query->where('ld_course_school.from_school_id',$school_id); //授权学校
                    $query->where('ld_course_school.is_del',0);
            })->select('ld_course_school.course_id as id','ld_course.parent_id','ld_course.child_id','ld_course.title')->get()->toArray(); //授权课程
            if(!empty($zizengCourse)){
                if(!empty($natureCourse)){
                    $natureCourseIds = array_column($natureCourse,'id');
                    foreach($zizengCourse as $key=>&$v){
                        if(in_array($v['id'], $natureCourseIds)){
                            unset($zizengCourse[$key]);
                        }
                    }
                } 
                $CourseArr = array_merge($zizengCourse,$natureCourse);
                foreach ($CourseArr as $key => $v) {
                    $CourseArr[$key]['subjectNameOne'] = !isset($subjectArr[$v['parent_id']])?'':$subjectArr[$v['parent_id']];
                    $CourseArr[$key]['subjectNameTwo'] = !isset($subjectArr[$v['child_id']])?'':$subjectArr[$v['child_id']];
                    $method = Couresmethod::select('method_id')->where(['course_id'=>$v['id'],'is_del'=>0])->get()->toArray();
                    if(!$method){
                        unset($CourseArr[$key]);
                    }else{
                        $methodArr = [];
                        foreach ($method as $k=>&$val){
                            if($val['method_id'] == 1){
                                $val['method_name'] = '直播';
                            }
                            if($val['method_id'] == 2){
                                $val['method_name'] = '录播';
                            }
                            if($val['method_id'] == 3){
                                $val['method_name'] = '其他';
                            }
                            array_push($methodArr,$val['method_name']);
                        }
                      $CourseArr[$key]['method'] = $methodArr;
                    }
                }
            }  
            return ['code'=>200,'msg'=>'message','data'=>$CourseArr];   
        }
    }
     /**
     * @param  批量授权
     * @param  school_id
     * @param  author  李银生
     * @param  ctime   2020/6/30
     * @return  array
     */
    public static function store($body){

        $arr = $subjectArr = $bankids = $questionIds = $InsertTeacherRef = $InsertSubjectRef = $InsertRecordVideoArr = $InsertZhiboVideoArr = $InsertQuestionArr =  [];
    	// $courseIds = explode(',',$body['course_id']);
        $courseIds = json_decode($body['course_id'],1);
    	$school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前学校id
    	$user_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0; //当前登录的用户id
        if($body['is_public'] == 1){ //公开课
            $nature = CourseRefOpen::whereIn('course_id',$courseIds)->where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->first()->toArray();
            if(!empty($nature)){
                return ['code'=>207,'msg'=>'已经授权'];
            }
            $ids = OpenCourseTeacher::whereIn('course_id',$courseIds)->where('is_del',0)->select('teacher_id')->get(); //要授权的教师信息
            $ids = array_unique($ids);
            $teacherIds = CourseRefTeacher::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>1])->pluck('teacher_id')->toArray();//已经授权过的讲师信息
            $teacherIdArr = array_diff($ids,$teacherIds);//不在授权讲师表里的数据   
            foreach($teacherIds as $key => $id){
                $InsertTeacherRef[$key]['from_school_id'] =$school_id;
                $InsertTeacherRef[$key]['to_school_id'] =$body['school_id'];
                $InsertTeacherRef[$key]['teacher_id'] =$body['id'];
                $InsertTeacherRef[$key]['is_public'] =1;
                $InsertTeacherRef[$key]['admin_id'] =$user_id;
            }
            $natureSubject = OpenCourse::where(function($query) use ($school_id) {
                                $query->where('status',1);
                                $query->where('school_id',$school_id);
                                $query->where('is_del',0);
                        })->select('parent_id','child_id')->get()->toArray(); //要授权的学科信息
            $arr =array_unique($natureSubject, SORT_REGULAR);
            $subjectArr = CourseRefSubject::where(['to_school_id'=>$data['school_id'],'from_school_id'=>$school_id,'is_del'=>0])->select('parent_id','child_id')->first()->toArray(); //已经授权的学科信息
            foreach($arr as $k=>$v) if(in_array($v, $subjectArr)) unset($arr[$k]);
            foreach($arr as $key=>&$v){
                $v['from_school_id'] =$school_id;
                $v['to_school_id'] =$body['school_id'];
                $v['admin_id'] =$user_id;
            }
             DB::beginTransaction();
            try{
                $teacherRes = CourseRefTeacher::insert($InsertTeacherRef);
                if(!$teacherRes){
                    DB::rollback();
                    return ['code'=>203,'msg'=>'公开课授权未成功'];
                }
                $subjectRes = CourseRefSubject::insert($arr);
                if(!$subjectRes){
                    DB::rollback();
                    return ['code'=>203,'msg'=>'公开课授权未成功！'];
                }
                DB::commit();
                return ['code'=>200,'msg'=>'公开课授权成功！'];
            } catch (Exception $e) {
                return ['code' => 500 , 'msg' => $ex->getMessage()];
            }
        }
        if($body['is_public'] == 0){  //课程
            $nature = self::whereIn('course_id',$courseIds)->where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->first();
            if(!empty($nature)){
                return ['code'=>207,'msg'=>'已经授权'];
            }
            $ids = Couresteacher::whereIn('course_id',$courseIds)->where('is_del',0)->select('teacher_id')->get()->toArray(); //要授权的教师信息
            $ids = array_unique($ids);
            $teacherIds = CourseRefTeacher::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>1])->pluck('teacher_id')->toArray();//已经授权过的讲师信息
            $teacherIdArr = array_diff($ids,$teacherIds);//不在授权讲师表里的数据   
            if(!empty($teacherIdArr)){
                foreach($teacherIds as $key => $id){
                    $InsertTeacherRef[$key]['from_school_id'] =$school_id;
                    $InsertTeacherRef[$key]['to_school_id'] =$body['school_id'];
                    $InsertTeacherRef[$key]['teacher_id'] =$body['id'];
                    $InsertTeacherRef[$key]['is_public'] =1;
                    $InsertTeacherRef[$key]['admin_id'] = $user_id;
                    $InsertTeacherRef[$key]['create_at'] = date('Y-m-d H:i:s');
                }   
            }
            
            $natureSubject = Coures::where(function($query) use ($school_id) {
                                $query->where('status',1);
                                $query->where('school_id',$school_id);
                                $query->where('is_del',0);
                        })->select('parent_id','child_id')->get()->toArray(); //学科
            $arr =array_unique($natureSubject, SORT_REGULAR);
            $subjectArr = CourseRefSubject::where(['to_school_id'=>$body['school_id'],'from_school_id'=>$school_id,'is_del'=>0])->select('parent_id','child_id')->first(); 
            if(!empty($subjectArr)){
                foreach($arr as $k=>$v) if(in_array($v, $subjectArr)) unset($arr[$k]);       
            }
         
            foreach($arr as $key=>$v){
                $InsertSubjectRef[$key]['parent_id'] = $v['parent_id'];
                $InsertSubjectRef[$key]['child_id'] = $v['child_id'];
                $InsertSubjectRef[$key]['from_school_id'] = $school_id;
                $InsertSubjectRef[$key]['to_school_id'] = $body['school_id'];
                $InsertSubjectRef[$key]['admin_id'] = $user_id;
                $InsertSubjectRef[$key]['create_at'] = date('Y-m-d H:i:s');    
            }
            //录播资源
            $recordVideoIds = CourseLivesResource::whereIn('course_id',$courseIds)->where(['is_del'=>0])->pluck('id')->toArray();   
            $narturecordVideoIds = CourseRefResource::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'type'=>0,'is_del'=>0])->pluck('resource_id as id ')->toArray();
            $recordVideoIds = array_diff($recordVideoIds,$narturecordVideoIds);
            foreach ($recordVideoIds as $key => $v) {
                $InsertRecordVideoArr[$key]['resource_id']=$v;
                $InsertRecordVideoArr[$key]['from_school_id'] = $school_id;
                $InsertRecordVideoArr[$key]['to_school_id'] = $body['school_id'];
                $InsertRecordVideoArr[$key]['admin_id'] = $user_id;
                $InsertRecordVideoArr[$key]['type'] = 0;
                $InsertRecordVideoArr[$key]['create_at'] = date('Y-m-d H:i:s');    

            }
            //直播资源
            $zhiboVideoIds = CourseLivesResource::whereIn('course_id',$courseIds)->where(['is_del'=>1])->pluck('id')->toArray();   
            $narturezhiboVideoIds = CourseRefResource::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'type'=>1,'is_del'=>0])->pluck('resource_id as id ')->toArray();
            $zhiboVideoIds = array_diff($zhiboVideoIds,$narturezhiboVideoIds);

            foreach ($zhiboVideoIds as $key => $v) {
                $InsertZhiboVideoArr[$key]['resource_id']=$v;
                $InsertZhiboVideoArr[$key]['from_school_id'] = $school_id;
                $InsertZhiboVideoArr[$key]['to_school_id'] = $body['school_id'];
                $InsertZhiboVideoArr[$key]['admin_id'] = $user_id;
                $InsertZhiboVideoArr[$key]['type'] = 1;
                $InsertZhiboVideoArr[$key]['create_at'] = date('Y-m-d H:i:s');    
            }//题库
            foreach($arr as $key=>&$v){
                $bankIdArr = QuestionBank::where(['parent_id'=>$v['parent_id'],'child_id'=>$v['child_id'],'is_del'=>0])->pluck('id')->toArray();
                array_push($bankIdArr,$bankids);
            }
            foreach($bankids as $key=>$v){
                $questionIds=array_merge($v);
            }
            $natureQuestionBank = CourseRefBank::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->pluck('bank_id')->toArray();
            $questionIds = array_diff($questionIds,$natureQuestionBank);
            foreach($questionIds as $key=>$v){
                $InsertQuestionArr[$key]['bank_id'] =$v;
                $InsertQuestionArr[$key]['from_school_id'] = $school_id;
                $InsertQuestionArr[$key]['to_school_id'] = $body['school_id'];
                $InsertQuestionArr[$key]['admin_id'] = $user_id;
                $InsertQuestionArr[$key]['create_at'] = date('Y-m-d H:i:s');    
            }
             DB::beginTransaction();
             try{
                $teacherRes = CourseRefTeacher::insert($InsertTeacherRef);//教师
                if(!$teacherRes){
                    DB::rollback();
                    return ['code'=>203,'msg'=>'教师授权未成功'];
                }
                $subjectRes = CourseRefSubject::insert($InsertSubjectRef);//学科
                if(!$subjectRes){
                    DB::rollback();
                    return ['code'=>203,'msg'=>'学科授权未成功！'];
                }
                $recordRes = CourseRefResource::insert($InsertRecordVideoArr); //录播
                if(!$recordRes){
                    DB::rollback();
                    return ['code'=>203,'msg'=>'录播资源授权未成功！'];
                }
                $zhiboRes = CourseRefResource::insert($InsertZhiboVideoArr); //直播
                if(!$zhiboRes){
                    DB::rollback();
                    return ['code'=>203,'msg'=>'直播资源授权未成功！'];
                }
                $bankRes = CourseRefBank::insert($InsertQuestionArr); //题库
                if(!$bankRes){
                    DB::rollback();
                    return ['code'=>203,'msg'=>'题库授权未成功！'];
                }
              
                DB::commit();
                return ['code'=>200,'msg'=>'公开课授权成功！'];
            } catch (Exception $e) {
                return ['code' => 500 , 'msg' => $ex->getMessage()];
            }
        }
    }
    //授权课程列表学科大类
    public static function getNatureSubjectOneByid($data){

        $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前登陆学校id
        $ids= [];
        $ids = $zongSubjectIds = CouresSubject::where(['parent_id'=>0,'school_id'=>$school_id,'is_open'=>0,'is_del'=>0])->pluck('id')->toArray();//总校自增学科大类
        if($data['is_public'] == 1){//公开课
            $natureSujectIds = CourseRefOpen::leftJoin('ld_course_open','ld_course_open.id','=','ld_course_ref_open.course_id')
                            ->where(function($query) use ($data,$school_id) {
                                $query->where('ld_course_ref_open.to_school_id',$data['school_id']);
                                $query->where('ld_course_ref_open.from_school_id',$school_id);
                                $query->where('ld_course_ref_open.is_del',0);
                            })->pluck('ld_course_open.parent_id')->toArray();
        }
        if($data['is_public'] == 0 ){//课程
            $natureSujectIds = CourseSchool::leftJoin('ld_course','ld_course.id','=','ld_course_school.course_id')
                            ->where(function($query) use ($data,$school_id) {
                                $query->where('ld_course_school.to_school_id',$data['school_id']);
                                $query->where('ld_course_school.from_school_id',$school_id);
                                $query->where('ld_course_school.is_del',0);
                             })->pluck('ld_course.parent_id')->toArray(); 
        } 
     
        if(!empty($natureSujectIds)){
           $natureSujectIds = array_unique($natureSujectIds);
           $ids = array_merge($zongSubjectIds,$natureSujectIds);
        }   
        $subjectOneArr = CouresSubject::whereIn('id',$ids)->where(['is_open'=>0,'is_del'=>0])->select('id','subject_name')->get();
        return ['code'=>200,'msg'=>'Success','data'=>$subjectOneArr];  
    }
    //授权课程列表小类
    public static function getNatureSubjectTwoByid($data){
        $subjectTwoArr = CouresSubject::where(['parent_id'=>$data['subjectOne'],'is_del'=>0,'is_open'=>0])->select('id','subject_name')->get();
        return ['code'=>200,'msg'=>'Success','data'=>$subjectTwoArr];
    }
}
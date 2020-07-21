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
        $school_status = isset(AdminLog::getAdminInfo()->admin_user->school_status) ? AdminLog::getAdminInfo()->admin_user->school_status : 0; //当前登陆学校id
        $OpenCourseArr =   $natureOpenCourse = $courseArr = $natureCourseArr = [];
        $zizengSubjectArr = CouresSubject::where('school_id',$school_id)->where(['is_open'=>0,'is_del'=>0])->select('id','subject_name')->get()->toArray();//自增大类小类（总校）
        $subjectArr = array_column($zizengSubjectArr,'subject_name','id');
        $schoolArr =Admin::where(['school_id'=>$body['school_id'],'is_del'=>1])->first();
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
            $OpenCourseArr = array_merge($zizengOpenCourse,$natureOpenCourse);
            if(!empty($OpenCourseArr)){
                $OpenCourseArr = array_unique($OpenCourseArr, SORT_REGULAR);
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
            })->select('ld_course_school.course_id as id','ld_course.parent_id','ld_course.child_id','ld_course.title')->get()->toArray(); 
            //授权课程
            $CourseArr = array_merge($zizengCourse,$natureCourse);

            if(!empty($CourseArr)){
                $CourseArr = array_unique($CourseArr, SORT_REGULAR);
                
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

        $arr = $subjectArr = $bankids = $questionIds = $InsertTeacherRef = $InsertSubjectRef = $InsertRecordVideoArr = $InsertZhiboVideoArr = $InsertQuestionArr = $teacherIdArr = [];
     //    $courseIds=$body['course_id'];
    	// $courseIds = explode(',',$body['course_id']);
        $courseIds = json_decode($body['course_id'],1); //前端传值
        if(empty($courseIds)){
            return ['code'=>205,'msg'=>'请选择授权课程'];
        }
    	$school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前学校id
        $school_status = isset(AdminLog::getAdminInfo()->admin_user->school_status) ? AdminLog::getAdminInfo()->admin_user->school_status : 0; //当前登陆学校id
    	$user_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0; //当前登录的用户id
        $schoolArr =Admin::where(['school_id'=>$body['school_id'],'is_del'=>1])->first();

        if($body['school_id'] == $school_id){
            return ['code'=>205,'msg'=>'自己不能给自己授权'];
        }
        if($schoolArr['school_status'] > $school_status){
            return ['code'=>205,'msg'=>'分校不能给总校授权'];
        }


        if($body['is_public'] == 1){ //公开课
            $nature = CourseRefOpen::whereIn('course_id',$courseIds)->where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->first();

            if(!empty($nature)){
                return ['code'=>207,'msg'=>'公开课已经授权'];
            }
            $ids = OpenCourseTeacher::whereIn('course_id',$courseIds)->where('is_del',0)->pluck('teacher_id')->toArray(); //要授权的教师信息
            if(!empty($ids)){
                $ids = array_unique($ids);
                $teacherIds = CourseRefTeacher::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>1])->pluck('teacher_id')->toArray();//已经授权过的讲师信息
                if(!empty($teacherIds)){
                    $teacherIdArr = array_diff($ids,$teacherIds);//不在授权讲师表里的数据   
                }else{
                    $teacherIdArr = $ids;
                }
                if(!empty($teacherIdArr)){
                    foreach($teacherIdArr as $key => $id){
                        $InsertTeacherRef[$key]['from_school_id'] =$school_id;
                        $InsertTeacherRef[$key]['to_school_id'] =$body['school_id'];
                        $InsertTeacherRef[$key]['teacher_id'] =$id;
                        $InsertTeacherRef[$key]['is_public'] =0;
                        $InsertTeacherRef[$key]['admin_id'] = $user_id;
                        $InsertTeacherRef[$key]['create_at'] = date('Y-m-d H:i:s');
                    }   
                }
                $natureSubject = OpenCourse::where(function($query) use ($school_id) {
                                              $query->where('status',1);
                                    $query->where('school_id',$school_id);
                                    $query->where('is_del',0);
                            })->select('parent_id','child_id')->get()->toArray(); //要授权的学科信息
              
                $subjectArr = CourseRefSubject::where(['to_school_id'=>$body['school_id'],'from_school_id'=>$school_id,'is_del'=>0])->select('parent_id','child_id')->get();//已经授权的学科信息
                if(!empty($subjectArr)){
                     foreach($natureSubject as $k=>$v){
                        foreach($subjectArr as $kk=>$bv){
                            if($v == $bv){
                                unset($natureSubject[$k]);
                            }
                        }
                    }     
                }
                foreach($natureSubject as $key=>&$vs){
                    $vs['from_school_id'] =$school_id;
                    $vs['to_school_id'] =$body['school_id'];
                    $vs['admin_id'] =$user_id;
                    $vs['create_at'] =date('Y-m-d H:i:s');
                }  
                foreach($courseIds as $k=>$vv){
                    $refOpenInsert[$k]['admin_id'] =$user_id;
                    $refOpenInsert[$k]['from_school_id'] =$school_id;
                    $refOpenInsert[$k]['to_school_id'] =$body['school_id'];
                    $refOpenInsert[$k]['course_id'] = $vv;
                    $refOpenInsert[$k]['create_at'] =date('Y-m-d H:i:s');
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
                    $refOpenRes = CourseRefOpen::insert($refOpenInsert);
                
                    if(!$refOpenRes){
                        DB::rollback();
                        return ['code'=>203,'msg'=>'公开课授权未成功！！'];
                    }

                     DB::commit();
                    return ['code'=>200,'msg'=>'公开课授权成功！'];
                    
                   
                } catch (Exception $e) {
                    return ['code' => 500 , 'msg' => $ex->getMessage()];
                }       
                    
            } 
        }  
        if($body['is_public'] == 0){  //课程
            $nature = self::whereIn('course_id',$courseIds)->where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->limit(1)->get()->toArray();
        
            if(!empty($nature)){
                return ['code'=>207,'msg'=>'课程已经授权'];
            }
            $course = Coures::whereIn('id',$courseIds)->where(['is_del'=>0])->select('parent_id','child_id','title','keywords','cover','pricing','sale_price','buy_num','expiry','describe','introduce','status','watch_num','is_recommend','id as course_id','school_id as from_school_id')->get()->toArray();//要授权课程 所有信息
            
            if(!empty($course)){
                foreach($course as $key=>&$vv){
                    $vv['from_school_id'] = $school_id;
                    $vv['to_school_id'] = $body['school_id'];
                    $vv['admin_id'] = $user_id;
                    $vv['create_at'] = date('Y-m-d H:i:s');
                    $courseSubjectArr[$key]['parent_id'] = $vv['parent_id'];
                    $courseSubjectArr[$key]['child_id'] = $vv['child_id'];
                }//授权课程信息
                $ids = Couresteacher::whereIn('course_id',$courseIds)->where('is_del',0)->pluck('teacher_id')->toArray(); //要授权的教师信息
                if(!empty($ids)){
                    $ids = array_unique($ids);
                    $teacherIds = CourseRefTeacher::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->pluck('teacher_id')->toArray();//已经授权过的讲师信息
                    if(!empty($teacherIds)){
                        $teacherIdArr = array_diff($ids,$teacherIds);//不在授权讲师表里的数据   
                    }else{
                        $teacherIdArr = $ids;
                    }
                    if(!empty($teacherIdArr)){
                        foreach($teacherIdArr as $key => $id){
                            $InsertTeacherRef[$key]['from_school_id'] =$school_id;
                            $InsertTeacherRef[$key]['to_school_id'] =$body['school_id'];
                            $InsertTeacherRef[$key]['teacher_id'] =$id;
                            $InsertTeacherRef[$key]['is_public'] =0;
                            $InsertTeacherRef[$key]['admin_id'] = $user_id;
                            $InsertTeacherRef[$key]['create_at'] = date('Y-m-d H:i:s');
                        }   
                    }
                }

                //学科
                $subjectArr = CourseRefSubject::where(['to_school_id'=>$body['school_id'],'from_school_id'=>$school_id,'is_del'=>0])->select('parent_id','child_id')->get()->toArray();  //已经授权过的学科
                if(!empty($subjectArr)){
                    foreach($courseSubjectArr as $k=>$v){
                        foreach($subjectArr as $kk=>$bv){
                            if($v == $bv){
                                unset($courseSubjectArr[$k]);
                            }
                        }
                    }         
                }
                
                foreach($courseSubjectArr as $key=>$v){
                        $InsertSubjectRef[$key]['parent_id'] = $v['parent_id'];
                        $InsertSubjectRef[$key]['child_id'] = $v['child_id'];
                        $InsertSubjectRef[$key]['from_school_id'] = $school_id;
                        $InsertSubjectRef[$key]['to_school_id'] = $body['school_id'];
                        $InsertSubjectRef[$key]['admin_id'] = $user_id;
                        $InsertSubjectRef[$key]['create_at'] = date('Y-m-d H:i:s');    
                }
                
                //录播资源
                $recordVideoIds = Coureschapters::whereIn('course_id',$courseIds)->where(['is_del'=>0])->pluck('resource_id as id')->toArray(); //要授权的录播资源 
                if(!empty($recordVideoIds)){
                    $narturecordVideoIds = CourseRefResource::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'type'=>0,'is_del'=>0])->pluck('resource_id as id ')->toArray(); //已经授权过的录播资源   
                    $recordVideoIds = array_diff($recordVideoIds,$narturecordVideoIds);
                    foreach ($recordVideoIds as $key => $v) {
                        $InsertRecordVideoArr[$key]['resource_id']=$v;
                        $InsertRecordVideoArr[$key]['from_school_id'] = $school_id;
                        $InsertRecordVideoArr[$key]['to_school_id'] = $body['school_id'];
                        $InsertRecordVideoArr[$key]['admin_id'] = $user_id;
                        $InsertRecordVideoArr[$key]['type'] = 0;
                        $InsertRecordVideoArr[$key]['create_at'] = date('Y-m-d H:i:s');    
                    }
                } 
                
                //直播资源
                $zhiboVideoIds = CourseLivesResource::whereIn('course_id',$courseIds)->where(['is_del'=>0])->pluck('id')->toArray();//要授权的直播资源
                if(!empty($zhiboVideoIds)){
                    $narturezhiboVideoIds = CourseRefResource::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'type'=>1,'is_del'=>0])->pluck('resource_id as id ')->toArray();
                    $zhiboVideoIds = array_diff($zhiboVideoIds,$narturezhiboVideoIds);
                    foreach ($zhiboVideoIds as $key => $v) {
                        $InsertZhiboVideoArr[$key]['resource_id']=$v;
                        $InsertZhiboVideoArr[$key]['from_school_id'] = $school_id;
                        $InsertZhiboVideoArr[$key]['to_school_id'] = $body['school_id'];
                        $InsertZhiboVideoArr[$key]['admin_id'] = $user_id;
                        $InsertZhiboVideoArr[$key]['type'] = 1;
                        $InsertZhiboVideoArr[$key]['create_at'] = date('Y-m-d H:i:s');    
                    }
                }   
                // print_r($InsertZhiboVideoArr);die;
                
                //题库
                foreach($courseSubjectArr as $key=>&$vs){
                    $bankIdArr = QuestionBank::where(['parent_id'=>$vs['parent_id'],'child_id'=>$vs['child_id'],'is_del'=>0])->pluck('id')->toArray();
                    if(!empty($bankIdArr)){
                        foreach($bankIdArr as $k=>$vb){
                            array_push($bankids,$vb);        
                        }
                    }
                }
            
                if(!empty($bankids)){
                    $bankids=array_unique($bankids);
                    $natureQuestionBank = CourseRefBank::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->pluck('bank_id')->toArray();
                    $bankids = array_diff($bankids,$natureQuestionBank);
                    foreach($bankids as $key=>$bankid){
                        $InsertQuestionArr[$key]['bank_id'] =$bankid;
                        $InsertQuestionArr[$key]['from_school_id'] = $school_id;
                        $InsertQuestionArr[$key]['to_school_id'] = $body['school_id'];
                        $InsertQuestionArr[$key]['admin_id'] = $user_id;
                        $InsertQuestionArr[$key]['create_at'] = date('Y-m-d H:i:s');    
                    }
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

                    if(!empty($InsertRecordVideoArr)){
                        $InsertRecordVideoArr = array_chunk($InsertRecordVideoArr,500);
                        foreach($InsertRecordVideoArr as $key=>$lvbo){
                            $recordRes = CourseRefResource::insert($lvbo); //录播
                            if(!$recordRes){
                                DB::rollback();
                                return ['code'=>203,'msg'=>'录播资源授权未成功！'];
                            }
                        }
                    }

                    if(!empty($InsertZhiboVideoArr)){
                        $InsertZhiboVideoArr = array_chunk($InsertZhiboVideoArr,500);

                        foreach($InsertZhiboVideoArr as $key=>$zhibo){
                            $zhiboRes = CourseRefResource::insert($zhibo); //直播
                            if(!$zhiboRes){
                                DB::rollback();
                                return ['code'=>203,'msg'=>'直播资源授权未成功！'];
                            }
                        } 
                    }
                    
                    $bankRes = CourseRefBank::insert($InsertQuestionArr); //题库
                    if(!$bankRes){
                        DB::rollback();
                        return ['code'=>203,'msg'=>'题库授权未成功！'];
                    } 
                   
                    $courseRes = self::insert($course); //
                    if(!$courseRes){
                        DB::rollback();
                        return ['code'=>203,'msg'=>'课程资源授权未成功！'];
                    }else{
                         DB::commit();
                        return ['code'=>200,'msg'=>'课程授权成功'];
                    }
                  
                } catch (Exception $e) {
                    return ['code' => 500 , 'msg' => $ex->getMessage()];
                }
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
    // /**
    //  * @param  批量取消授权
    //  * @param  school_id
    //  * @param  author  李银生
    //  * @param  ctime   2020/6/30
    //  * @return  array
    // */   
    //  public static function courseCancel(){
    //     $arr = $subjectArr = $bankids = $questionIds = $InsertTeacherRef = $InsertSubjectRef = $InsertRecordVideoArr = $InsertZhiboVideoArr = $InsertQuestionArr = $teacherIdArr =$nonatureCourseId =  [];
    //      //    $courseIds=$body['course_id'];
    //     // $courseIds = explode(',',$body['course_id']);
    //     $courseIds = json_decode($body['course_id'],1); //前端传值 
    //     if(empty($courseIds)){
    //         return ['code'=>205,'msg'=>'请选择取消授权课程'];
    //     }
    //     $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前学校id
    //     $school_status = isset(AdminLog::getAdminInfo()->admin_user->school_status) ? AdminLog::getAdminInfo()->admin_user->school_status : 0; //当前登录学校的状态
    //     $user_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0; //当前登录的用户id
    //     $schoolArr =Admin::where(['school_id'=>$body['school_id'],'is_del'=>1])->first(); //前端传学校的id
    //     if($body['is_public'] == 1){
    //             //公开课
    //     }
    //     if($body['is_public'] == 0){
    //         //课程
    //         $nature = self::whereIn('course_id',$courseIds)->where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->get()->toArray(); //要取消的授权的课程
    //         if(empty($nature)){
    //             return return ['code'=>207,'msg'=>'课程已经取消授权'];
    //         }
    //         foreach ($nature  as $kk => $vv) {
    //             $natureCourseArr[$kk]['parent_id'] = $vv['parent_id'];
    //             $natureCourseArr[$kk]['child_id'] = $vv['child_id'];
               
    //         }
    //         $noNatureCourse = self::whereNotIn('course_id',$courseIds)->where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->get()->toArray();//除取消授权课程的信息

    //         foreach($noNatureCourse as $k=>$v){
    //               $noNaturecourseSubjectArr[$k]['parent_id'] = $v['parent_id'];
    //               $noNaturecourseSubjectArr[$k]['child_id'] = $v['child_id'];
    //               array_push($nonatureCourseId,$vv['course_id']);
    //         }
    //         //要取消的教师信息
    //         $teachers_ids = Couresteacher::whereIn('course_id',$courseIds)->where(['is_del'=>0,'is_public'=>0])->pluck('teacher_id')->toArray(); //要取消授权的教师信息
    //         $noNatuerTeacher_ids  =  Couresteacher::whereNotIn('course_id',$nonatureCourseId)->where(['is_del'=>0,'is_public'=>0])->pluck('teacher_id')->toArray(); //除取消授权的教师信息
    //         $refTeacherArr  = CourseRefTeacher::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0,'is_public'=>0])->pluck('teacher_id')->toArray(); //现已经授权过的讲师
    //         if(!empty($refTeacherArr)){
    //             $teachers_ids = array_unique($teachers_ids);
    //             if(!empty($noNatuerTeacher_ids)){
    //                 $noNatuerTeacher_ids = array_unique($noNatuerTeacher_ids);
    //                 $arr = array_diff($teachers_ids,$noNatuerTeacher_ids);
    //                 if(!empty($arr)){
    //                     $updateTeacherArr = array_diff($arr,$refTeacherArr);
    //                 }
    //             }else{
    //                 $updateTeacherArr = array_diff($teachers_ids,$refTeacherArr); //$updateTecherArr 要取消授权的讲师信息
    //             }
    //         }
    //         //要取消的直播资源
    //         $zhibo_resourse_ids = CourseLivesResource::whereIn('course_id',$courseIds)->where('is_del',0)->pluck('id')->toArray(); //要取消授权的直播资源
    //         $no_natuer_zhibo_resourse_ids  =  CourseLivesResource::whereNotIn('course_id',$nonatureCourseId)->where('is_del',0)->pluck('id')->toArray(); //除取消授权的直播资源
    //         $refzhiboRescourse = CourseRefResource::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0,'type'=>1])->pluck('resource_id')->toArray(); //现在已经授权过的直播资源
    //         if(!empty($refzhiboRescourse)){
    //             $zhibo_resourse_ids = array_unique($zhibo_resourse_ids);
    //             if(!empty($no_natuer_zhibo_resourse_ids)){
    //                 $no_natuer_zhibo_resourse_ids = array_unique($no_natuer_zhibo_resourse_ids);
    //                 $arr = array_diff($zhibo_resourse_ids,$no_natuer_zhibo_resourse_ids);
    //                 if(!empty($arr)){
    //                     $updatezhiboArr = array_diff($arr,$refzhiboRescourse);
    //                 }
    //             }else{
    //                 $updatezhiboArr = array_diff($zhibo_resourse_ids,$refzhiboRescourse); //$updatezhiboArr 要取消授权的讲师信息
    //             }
    //         }
    //         //要取消的录播资源
    //         $lvbo_resourse_ids = Coureschapters::whereIn('course_id',$courseIds)->where('is_del',0)->pluck('resource_id')->toArray(); //要取消授权的录播资源
    //         $no_natuer_lvbo_resourse_ids  =  Coureschapters::whereNotIn('course_id',$nonatureCourseId)->where('is_del',0)->pluck('resource_id')->toArray(); //除取消授权的录播资源
    //         $reflvboRescourse = CourseRefResource::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0,'type'=>0])->pluck('resource_id')->toArray(); //现在已经授权过的录播资源
    //         if(!empty($reflvboRescourse)){
    //             $lvbo_resourse_ids = array_unique($lvbo_resourse_ids);
    //             if(!empty($no_natuer_lvbo_resourse_ids)){
    //                 $no_natuer_lvbo_resourse_ids = array_unique($no_natuer_lvbo_resourse_ids);
    //                 $arr = array_diff($lvbo_resourse_ids,$no_natuer_lvbo_resourse_ids);
    //                 if(!empty($arr)){
    //                     $updatelvboArr = array_diff($arr,$reflvboRescourse);
    //                 }
    //             }else{
    //                 $updatelvboArr = array_diff($lvbo_resourse_ids,$reflvboRescourse); //$updatezhiboArr 要取消授权的讲师信息
    //             }
    //         }
    //         //学科
    //         $natureCourseArr = array_unique($natureCourseArr,SORT_REGULAR);//要取消授权的学科信息
    //         $noNaturecourseSubjectArr = array_unique($noNaturecourseSubjectArr,SORT_REGULAR);//除取消授权的学科信息
    //         $natureSubjectIds = CourseRefSubject::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->select('parent_id','child_id')->get()->toArray();//已经授权过的学科信息
    //         if(!empty($natureSubjectIds)){
    //             $natureSubjectIds = array_unique($natureSubjectIds,SORT_REGULAR);
    //             if(!empty($noNatuerTeacher_ids)){
    //                 foreach ($natureCourseArr as $ka => $va) {
    //                     foreach($noNaturecourseSubjectArr as $kb =>$vb){
    //                         if($va != $vb){
    //                             array_push($subjectNatureArr,$va); //要取消的学科信息
    //                         }
    //                     }
    //                 }
    //                 foreach ($subjectNatureArr as $ks => $vs) {
    //                     foreach($natureSubjectIds as$kn=>$vn ){
    //                          if($vs != $vn){
    //                             array_push($updateSubjectArr,$vs);
    //                          }
    //                     }
    //                 }
    //             }else{
    //                 foreach ($natureCourseArr as $ks => $vs) {
    //                     foreach($natureSubjectIds as$kn=>$vn ){
    //                          if($vs != $vn){
    //                             array_push($updateSubjectArr,$vs);
    //                          }
    //                     }
    //                 }
    //             }
    //         }
    //         //题库
    //         // CourseRefBank::where(['from_school_id'=>$school_id,'to_school_id'=>$body['school_id'],'is_del'=>0])->pluck('bank_id')->toArray();
                 
    //     }  

    // }


}
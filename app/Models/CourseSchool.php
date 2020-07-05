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
                        $query->where('ld_course_open.parent_id',$body['subjectOne']);
                    }
                    if(!empty($body['subjectTwo']) && $body['subjectTwo'] != ''){
                        $query->where('ld_course_open.child_id',$body['subjectTwo']);
                    }
                    if(!empty($body['search']) && $body['search'] != ''){
                        $query->where('ld_course_open.title','like',"%".$body['search']."%");
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

    	$courseIds = explode(',',$body['course_id']);
    	
    	$school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前学校id
    	$user_id = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0; //当前登录的用户id
   		$courseSchoolIds=self::where(['school_id'=>$body['school_id'],'is_del'=>0])->get();
   		foreach($courseSchoolIds as $k=>$v){
   			if(in_array($v['course_id'],$courseIds)){
   				return ['code'=>207,'msg'=>'已经授权'];
   			}
   		}
   		$courseInsertData = Coures::whereIn('id',$courseIds)
   								->select('id','parent_id','child_id','title','keywords','cover','pricing','sale_price','buy_num','expiry','describe','introduce','status','is_recommend')
   								->get();
   		foreach($courseInsertData as $k=>$v){
   		//直播资源也未添加
            // $v['zhibo_resource'] = CourseLivesResource::where(['course_id',$v['id'],'is_del'=>0])->pluck('resource_id');
            // if(!empty($v['zhibo_resource'])){
            //     $v['zhibo_resource']['resource'] = Live::whereIn('id',$v['zhibo_resource'])->get()->toArray();
            //     if(!empty($v['zhibo_resource']['resource'])){
            //         foreach($v['zhibo_resource']['resource'] as $kk =>$vv){
            //             $v['zhibo_resource']['resource'][$kk]['class_no'] =  CourseShiftNo::
            //         }
            //     }
            // }
            $v['record']  = Video::where(['lesson_id',$v['id'],'is_del'=>0,'status'=>0])->first();//录播资源是单选，表关系是一对一的
   			$v['method_id'] = Couresmethod::where('course_id',$v['id'])->pluck('method_id');
   		}   
   		try{
	   		foreach($courseInsertData as $key=>$data){
	   				$courseInsertArr = [
	   					'parent_id'=>$data['parent_id'],
	   					'child_id'=>$data['child_id'],
	   					'title'=>$data['title'],
	   					'keywords'=>$data['keywords'],
	   					'cover'=>$data['cover'],
	   					'pricing'=>$data['pricing'],
	   					'sale_price'=>$data['sale_price'],
	   					'buy_num'=>$data['buy_num'],
	   					'expiry'=>$data['expiry'],
	   					'describe'=>$data['describe'],
	   					'introduce'=>$data['introduce'],
	   					'status'=>$data['status'],
	   					'admin_id' => $user_id,
	   					'nature' => 1,
	   					'school_id'=>$body['school_id'],
	   					'create_at' => date('Y-m-d H:i:s'),//资源未添加
	   				];
					$course_id = Coures::insertGetId($courseInsertArr);
					if($course_id<=0){
						DB::rollback();
						return ['code'=>203,'msg'=>'课程授权未成功'];
					}
					$methodInsertArr = [];
					foreach($data['method_id'] as $k=>$v){
						$methodInsertArr[$k]['course_id'] = $course_id;
						$methodInsertArr[$k]['method_id'] = $v;
						$methodInsertArr[$k]['create_at'] = date('Y-m-d H:i:s');
					}
					$res = Couresmethod::insert($methodInsertArr);
					if(!$res){
						DB::rollback();
						return ['code'=>203,'msg'=>'课程授权未成功！'];
					}
                    // //直播资源 
                    // if(!empty($data['zhibo_resource'])){
                    //     $zhiboResourseInsertArr = [];
                    //     foreach($data['zhibo_resource'] as $k=>$v){
                    //         $zhiboResourseInsertArr[$k]['resource_id'] = $v;
                    //         $zhiboResourseInsertArr[$k]['course_id'] = $course_id;
                    //         $zhiboResourseInsertArr[$k]['create_at'] = date('Y-m-d H:i:s');
                    //     }
                    //     $res = CourseLivesResource::insert($zhiboResourseInsertArr);
                    //     if(!$res){
                    //         DB::rollback();
                    //         return ['code'=>203,'msg'=>'课程授权未成功！'];
                    //     }    
                    // }
                    if(!empty($data['record'])){  //录播资源
                        $recordResouInsertArr = [
                            'lesson_id'=>$data['record']['lesson_id'],
                            'admin_id'=>$user_id,
                            'school_id'=>$body['school_id'],
                            'parent_id'=>$data['record']['parent_id'],
                            'child_id'=>$data['record']['child_id'],
                            'course_id'=>$data['record']['course_id'],
                            'mt_video_id'=>$data['record']['mt_video_id'],
                            'mt_video_name'=>$data['record']['mt_video_name'],
                            'mt_url'=>$data['record']['mt_url'],
                            'mt_duration'=>$data['record']['mt_duration'],
                            'start_time'=>$data['record']['start_time'],
                            'end_time'=>$data['record']['end_time'],
                            'resource_type'=>$data['record']['resource_type'],
                            'resource_name'=>$data['record']['resource_name'],
                            'resource_url'=>$data['record']['resource_url'],
                            'resource_size'=>$data['record']['resource_size'],
                            'nature'=>1,
                            'create_at'=>date('Y-m-d H:i:s'),
                        ];
                        $res = Video::insert($recordResouInsertArr);    
                        if(!$res){
                            DB::rollback();
                            return ['code'=>203,'msg'=>'课程授权未成功！！'];
                        }    
                    }
				}
				DB::commit();
				return ['code'=>200,'msg'=>'课程授权成功！'];
			} catch (Exception $e) {
				return ['code' => 500 , 'msg' => $ex->getMessage()];
			}
    }

}
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\AdminLog;
use App\Models\CouresSubject;
use App\Models\OpenCourse;
use App\Models\OpenCourseTeacher;
use Illuminate\Support\Facades\DB;
use App\Models\Teacher;

class OpenCourseController extends Controller {
    /*
    * @param  公开课列表
    * @param  author  lys
    * @param  ctime   2020/6/28 9:30
    * return  array
    */
	public function getList(){
		$data = OpenCourse::getList(self::$accept_data);
		return response()->json($data);
	}
	  /*
    * @param  直播类型
    * @param  author  lys
    * @param  ctime   2020/6/28 9:30
    * return  array
    */
	public function zhiboMethod(){
		$arr = [
			['id'=>1,'name'=>'语音云'],
			['id'=>3,'name'=>'大班'],
			['id'=>5,'name'=>'小班'],
			['id'=>6,'name'=>'大班互动'],
		];
		return response()->json(['code'=>200,'msg'=>'Success','data'=>$arr]); 
	}
   /*
    * @param  添加公开课
    * @param  author  lys
    * @param  ctime   2020/6/25 17:25
    * return  array
    */
   //部分未完成（创建直播间）
    public function doInsertOpenCourse(){
        $openCourseArr = self::$accept_data;
        $validator = Validator::make($openCourseArr, 
                [
                	'subject' => 'required',
                	'title' => 'required',
                	'keywords' => 'required',
                	'cover' => 'required',
                	'time' => 'required',
                	'is_barrage' => 'required',
                	'live_type' => 'required',
                	'introduce'=>'required',
                	'edu_teacher_id' => 'required',
                	'lect_teacher_id'=>'required',
               	],
                OpenCourse::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        try{
	        DB::beginTransaction();
	        $openCourseArr['parent_id'] = $openCourseArr['subject'][0]<0 ? 0: $openCourseArr['subject'][0];
	        $openCourseArr['child_id'] = !isset($openCourseArr['subject'][1]) && $openCourseArr['subject'][1] ? 0 : $openCourseArr['subject'][1];
	     	$eduTeacherArr = explode(',',$openCourseArr['edu_teacher_id']);
	        $lectTeacherId = $openCourseArr['lect_teacher_id'];
	        $time = explode(',', $openCourseArr['time']);
	        $openCourseArr['start_at']  = $time[0];
	        $openCourseArr['end_at']  = $time[1];
	        unset($openCourseArr['edu_teacher_id']);
	        unset($openCourseArr['lect_teacher_id']);
	        unset($openCourseArr['subject']);
	        unset($openCourseArr['time']);
	        if($openCourseArr['start_at'] >  $openCourseArr['end_at'] ){
	        	return response()->json(['code'=>207,'msg'=>'开始时间不能大于结束时间']); 
	        }
	        $openCourseArr['school_id']  = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0 ;
	        $openCourseArr['admin_id']  = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0 ;
	        $openCourseArr['describe']  = isset($openCourseArr['describe']) ?$openCourseArr['describe']:'';
	   		$openCourseArr['create_at'] = date('Y-m-d H:i:s');
			$openCourseId = OpenCourse::insertGetId($openCourseArr);
	        if($openCourseId <0){
	        	DB::rollBack();
	            return response()->json(['code'=>203,'msg'=>'公开课创建未成功']);  
	        }
	        $teacherArr = array_push($eduTeacherArr,$lectTeacherId);

	        foreach ($teacherArr as $key => $val) {
	        	$addTeacherArr[$key]['course_id'] = (int)$openCourseId;
	        	$addTeacherArr[$key]['teacher_id'] = $val;
	        	$addTeacherArr[$key]['create_at'] = date('Y-m-d H:i:s');
	        } 

	       	$openCourseTeacher = new OpenCourseTeacher();

            $res = $openCourseTeacher->insert($addTeacherArr);
     		
            if(!$res){
            	DB::rollBack();
	            return response()->json(['code'=>203,'msg'=>'教师创建未成功']);  
            }else{
            	DB::commit();
            	return response()->json(['code'=>200,'msg'=>'公开课创建成功']);  
            }
            
	    } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }        
    }
    /*
    * @param  是否推荐
    * @param  author  lys
    * @param  ctime   2020/6/28 9:30
    * return  array
    */
   	public function doUpdateRecomend(){
	    $openCourseArr = self::$accept_data;
	    $validator = Validator::make($openCourseArr, 
	        [
	        	'openless_id' => 'required|integer',
	       	],
	    OpenCourse::message());
	    $data = OpenCourse::getOpenLessById(['id'=>$openCourseArr['openless_id'],'is_del'=>0],['id','is_recommend']);
	    if($data['code']!= 200 ){
	    	 return response()->json($data);
	    }
	    try {
		    $update['is_recommend'] = $data['data']['is_recommend'] >0 ? 0:1;
		    $update['update_at'] = date('Y-m-d H:i:s');
		    $update['id'] =  $data['data']['id'];
		    $update['admin_id'] =  isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0 ;
		    $res = openCourse::where('id',$data['data']['id'])->update($update);
	        if($res){
	        	AdminLog::insertAdminLog([
	                'admin_id'       =>   isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0  ,
	                'module_name'    =>  'OpenCourse' ,
	                'route_url'      =>  'admin/OpenCourse/doUpdateRecomend' , 
	                'operate_method' =>  'update',
	                'content'        =>  json_encode($update) ,
	                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
	                'create_at'      =>  date('Y-m-d H:i:s')
            	]);
	    		return response()->json(['code'=>200,'msg'=>'更改成功']);
		    }else{
		    	return response()->json(['code'=>203,'msg'=>'更改成功']);
		    }
       	} catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
	  
    }
    /*
    * @param  修改课程状态
    * @param  author  lys
    * @param  ctime   2020/6/28 10:00
    * return  array
    */
   	public function doUpdateStatus(){
	    $openCourseArr = self::$accept_data;
	    $validator = Validator::make($openCourseArr, 
	        [
	        	'openless_id' => 'required|integer',
	       	],
	    OpenCourse::message());
	    $data = OpenCourse::getOpenLessById(['id'=>$openCourseArr['openless_id'],'is_del'=>0],['id','status','start_at','end_at']);
	    if($data['code']!= 200 ){
	    	 return response()->json($data);
	    }
	    if($data['data']['status'] <1){
	    	$update['status'] = 1;
	    }else if($data['data']['status'] == 1){
	    	if($data['data']['start_at'] <time() && $data['data']['end_at'] >time()){
	    		return response()->json(['code'=>207,'msg'=>'直播中，无法停售!']);
	    	}
	    	$update['status'] = 2;
	    }else if($data['data']['status'] == 2){
	    	$update['status'] = 1;
	    }
	    try { 
		    $update['admin_id'] =  isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0 ;
		    $update['update_at'] = date('Y-m-d H:i:s');
		    $update['id'] =  $data['data']['id'];
		    $res = OpenCourse::where('id',$data['data']['id'])->update($update);
		    if($res){
		    	AdminLog::insertAdminLog([
	                'admin_id'       =>   isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0  ,
	                'module_name'    =>  'OpenCourse' ,
	                'route_url'      =>  'admin/OpenCourse/doUpdateStatus' , 
	                'operate_method' =>  'update',
	                'content'        =>  json_encode($update) ,
	                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
	                'create_at'      =>  date('Y-m-d H:i:s')
            	]);
		    	return response()->json(['code'=>200,'msg'=>'更改成功']);
		    }else{
		    	return response()->json(['code'=>203,'msg'=>'更改成功']);
		    }
		} catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
    * @param  是否删除
    * @param  author  lys
    * @param  ctime   2020/6/28 9:30
    * return  array
    */
   	public function doUpdateDel(){
	    $openCourseArr = self::$accept_data;
	    $validator = Validator::make($openCourseArr, 
	        [
	        	'openless_id' => 'required|integer',
	       	],
	    OpenCourse::message());
	    $data = OpenCourse::getOpenLessById(['id'=>$openCourseArr['openless_id'],'is_del'=>0],['id','is_del']);
	    if($data['code']!= 200 ){
	    	 return response()->json($data);
	    }
	    try { 
	        DB::beginTransaction();
		    $update['is_del'] = 1;
		    $update['admin_id'] =  isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0 ;
		    $update['update_at'] = date('Y-m-d H:i:s');
		    $update['id'] =  $data['data']['id'];
		    $res = OpenCourse::where('id',$data['data']['id'])->update($update);
		    if(!$res){
		    	DB::rollBack();
		    	return response()->json(['code'=>203,'msg'=>'删除成功']);
		   	}
		   	$res = OpenCourseTeacher::where('course_id',$update['id'])->update(['is_del'=>1,'update_at'=>date('Y-m-d H:i:s')]);
		    if($res){
		    	AdminLog::insertAdminLog([
		                'admin_id'       =>   isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0  ,
		                'module_name'    =>  'OpenCourse' ,
		                'route_url'      =>  'admin/OpenCourse/doUpdateDel' , 
		                'operate_method' =>  'update',
		                'content'        =>  json_encode($update) ,
		                'ip'             =>  $_SERVER["REMOTE_ADDR"] ,
		                'create_at'      =>  date('Y-m-d H:i:s')
	            	]);
		    	DB::commit();
		    	return response()->json(['code'=>200,'msg'=>'删除成功']);
		    }else{
		    	 DB::rollBack();
		    	return response()->json(['code'=>203,'msg'=>'删除成功']);
		    }  
	    } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
    * @param  公开课修改(获取)
    * @param  author  lys
    * @param  ctime   2020/6/28 9:30
    * return  array
    */
   	public function getOpenLessById(){
	    $openCourseArr = self::$accept_data;
	    $validator = Validator::make($openCourseArr, 
	        [
	        	'openless_id' => 'required|integer',
	       	],
	    OpenCourse::message());
	    $data = OpenCourse::getOpenLessById(['id'=>$openCourseArr['openless_id'],'is_del'=>0],['id','parent_id','child_id','title','keywords','cover','start_at','end_at','describe','introduce','is_barrage','live_type']);
	    if($data['code']!= 200 ){
	    	return response()->json($data);
	    }
	    if($data['data']['start_at'] <time() && $data['data']['end_at'] >time()){
	    	return response()->json(['code'=>207,'msg'=>'直播中，无法修改']);
	    }
	    $data['data']['subject'] = [];
	   	if($data['data']['parent_id']>0){
	   		array_push($data['data']['subject'], $data['data']['parent_id']);
	   	} 
	   	if($data['data']['child_id']>0){
	   		array_push($data['data']['subject'], $data['data']['child_id']);
	   	}                
	    $data['data']['start_at'] = date('Y-m-d H:i:s',$data['data']['start_at']);
	    $data['data']['end_at'] = date('Y-m-d H:i:s',$data['data']['end_at']);
	    $teacher_id = OpenCourseTeacher::where(['course_id'=>$data['data']['id'],'is_del'=>0])->get(['teacher_id'])->toArray();   
	    $teacherArr = array_column($teacher_id,'teacher_id');	
    	$teacherData = Teacher::whereIn('id',$teacherArr)->where('is_del',0)->select('id','type')->get()->toArray();
    	$lectTeacherArr = $eduTeacherArr = [];
    	if(!empty($teacherData)){
    		foreach($teacherData as $key =>$v){
    			if($v['type'] == 1){
    				array_push($eduTeacherArr,$v['id']);
    			}else if($v['type'] == 2){
    				$data['data']['lect_teacher_id'] = $v['id'];
    			}
    		}
    	}
    	$arr = ['openless'=>$data['data'],'eduteacher'=>$eduTeacherArr];
    	return response()->json(['code'=>200,'msg'=>'获取成功','data'=>$arr]);
    }
    /*
    * @param  公开课修改(获取)
    * @param  author  lys
    * @param  ctime   2020/6/28 9:30
    * return  array
    */
   	public function doOpenLessById(){
	    $openCourseArr = self::$accept_data;
	    $validator = Validator::make($openCourseArr, 
	        [
	        	'openless_id' => 'required|integer',
	       		'subject' =>'required',
            	'title' => 'required',
            	'keywords' => 'required',
            	'cover' => 'required',
            	'time' => 'required',
            	'is_barrage' => 'required',
            	'live_type' => 'required',
            	'introduce' => 'required',
            	'edu_teacher_id' => 'required',
            	'lect_teacher_id'=>'required',
	       	],
	    OpenCourse::message());
	    $data = OpenCourse::getOpenLessById(['id'=>$openCourseArr['openless_id'],'is_del'=>0],['id','start_at','end_at']);
	    if($data['code']!= 200 ){
	    	return response()->json($data);
	    }
	    if($data['data']['start_at'] <time() && $data['data']['end_at'] >time()){
	    	return response()->json(['code'=>207,'msg'=>'直播中，无法修改']);
	    }
	     try{
	        DB::beginTransaction();
	     
	     	$time = explode(',', $openCourseArr['time']);
	        $openCourseArr['start_at']  = $time[0];
	        $openCourseArr['end_at']  = $time[1];
	        if($openCourseArr['start_at'] >  $openCourseArr['end_at'] ){
	        	return response()->json(['code'=>207,'msg'=>'开始时间不能大于结束时间']); 
	        }
	        $eduTeacherArr = explode(',',$openCourseArr['edu_teacher_id']);
	        $lectTeacherId = $openCourseArr['lect_teacher_id'];
	        unset($openCourseArr['edu_teacher_id']);
	        unset($openCourseArr['lect_teacher_id']);
	        unset($openCourseArr['time']);
	        $openCourseArr['admin_id']  = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0 ;
	        $openCourseArr['describe']  = !isset($openCourseArr['describe']) ?'':$openCourseArr['describe'];
	   		$openCourseArr['update_at'] = date('Y-m-d H:i:s');
			$res = OpenCourse::where('id',$data['data']['id'])->update($openCourseArr);
	        if(!$res){
	        	DB::rollBack();
	            return response()->json(['code'=>203,'msg'=>'公开课更改未成功']);  
	        }
	        $openCourseTeacher = new OpenCourseTeacher();
	        $res = $openCourseTeacher->where('course_id',$data['data']['id'])->delete();
	        if(!$res){
            	DB::rollBack();
	            return response()->json(['code'=>203,'msg'=>'教师更改未成功']);  
	        }
	        $teacherArr = array_push($eduTeacherArr,$lectTeacherId);
	        foreach ($teacherArr as $key => $val) {
	        	$addTeacherArr[$key]['course_id'] = (int)$openCourseId;
	        	$addTeacherArr[$key]['teacher_id'] = $val;
	        	// $addTeacherArr[$key]['create_at'] = date('Y-m-d H:i:s');
	        	$addTeacherArr[$key]['update_at'] = date('Y-m-d H:i:s');
	        } 
            $res = $openCourseTeacher->insert($addTeacherArr);
            if(!$res){
            	DB::rollBack();
	            return response()->json(['code'=>203,'msg'=>'教师更改未成功']);  
            }else{
            	DB::commit();
            	return response()->json(['code'=>200,'msg'=>'公开课更改成功']);  
            }
	    } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }        
    }



    //公开课创建直播
    public function addLive($data, $lesson_id)
    {
        $user = CurrentAdmin::user();
        try {
            $MTCloud = new MTCloud();
            $res = $MTCloud->courseAdd(
                $data['title'],
                $data['teacher_id'],
                $data['start_at'],
                $data['end_at'],
                $data['nickname'],
                '',         
                [   
                    'barrage' => $data['barrage'], 
                    'modetype' => $data['modetype'],
                ]
            );
            if(!array_key_exists('code', $res) && !$res["code"] == 0){
                Log::error('欢拓创建失败:'.json_encode($res));
                return false;
            }
            $live = Live::create([
                    'admin_id' => intval($user->id),
                    'subject_id' => $data['subject_id'],
                    'name' => $data['title'],
                    'description' => $data['description'],
                ]);
            
            $live->lessons()->attach([$lesson_id]);
            $livechild =  LiveChild::create([
                            'admin_id'   => $user->id,
                            'live_id'    => $live->id,
                            'course_name' => $data['title'],
                            'account'     => $data['teacher_id'],
                            'start_time'  => $data['start_at'],
                            'end_time'    => $data['end_at'],
                            'nickname'    => $data['nickname'],
                            'partner_id'  => $res['data']['partner_id'],
                            'bid'         => $res['data']['bid'],
                            'course_id'   => $res['data']['course_id'],
                            'zhubo_key'   => $res['data']['zhubo_key'],
                            'admin_key'   => $res['data']['admin_key'],
                            'user_key'    => $res['data']['user_key'],
                            'add_time'    => $res['data']['add_time'],
                        ]);
            LiveTeacher::create([
                'admin_id' => $user->id,
                'live_id' => $live->id,
                'live_child_id' => $livechild->id,
                'teacher_id' => $data['teacher_id'],
            ]);
        } catch (Exception $e) {
            Log::error('创建失败:'.$e->getMessage());
            return false;
        }
        return true;
    }

}

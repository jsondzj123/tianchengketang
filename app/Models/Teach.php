<?php
namespace App\Models;

use App\Tools\CurrentAdmin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use App\Models\AdminLog;
use App\Tools\MTCloud;
//教学模块Model
class Teach extends Model {

	//返回错误信息
	public static function message(){
		 return [
            'class_id.required'  => json_encode(['code'=>'201','msg'=>'课次id不能为空']),
            'class_id.integer'   => json_encode(['code'=>'202','msg'=>'课次id类型不合法']),
            'classno_id.required' => json_encode(['code'=>'201','msg'=>'班号id不能为空']),
            'classno_id.integer'  => json_encode(['code'=>'202','msg'=>'班号id类型不合法']),
            'is_public.required' => json_encode(['code'=>'201','msg'=>'是否公开课标识不能为空']),
            'id.required' =>        json_encode(['code'=>'201','msg'=>'id不能为空']),
            'file.required' =>       json_encode(['code'=>'201','msg'=>'课件不能为空']),
        ];
	}
	//教学列表
	public static function getList($body){
		$school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前学校id
		//公开课数据
		$openCourseArr = OpenCourse::rightJoin('ld_course_open_live_childs','ld_course_open_live_childs.lesson_id','=','ld_course_open.id') 
						->rightJoin('ld_course_open_teacher','ld_course_open_teacher.course_id','=','ld_course_open.id')
						->rightJoin('ld_lecturer_educationa','ld_lecturer_educationa.id','=','ld_course_open_teacher.teacher_id')
						->where(function($query) use ($body,$school_id) {
						if(isset($body['time']) && !empty($body['time'])){
							switch ($body['time']) {
								case '1': //今天
									$query->where('ld_course_open.start_at','<',strtotime(date('Y-m-d')));
									$query->where('ld_course_open.end_at','>',strtotime(date('Y-m-d 23:59:59')));
									break;
								case '2': //明天
									$query->where('ld_course_open.start_at','<',strtotime(date("Y-m-d",strtotime("+1 day"))));
									$query->where('ld_course_open.end','>',strtotime(date("Y-m-d 23:59:59",strtotime("+1 day"))));
									break;
								case '3': //昨天
									$query->where('ld_course_open.start_at','<',strtotime(date("Y-m-d",strtotime("-1 day"))));
									$query->where('ld_course_open.end','>',strtotime(date("Y-m-d 23:59:59",strtotime("-1 day"))));
									break;
							}
							if(is_array($body['time'])){
								$time = json_decode($body['time'],1);
								$query->where('ld_course_open.start_at','<',$time[0]);
								$query->where('ld_course_open.end','>',$time[1]);
							}
						}
						if(isset($body['status']) && !empty($body['status'])){
							switch ($body['status']) {
								case '2':
									$query->where('ld_course_open.start_at','>',time());
									break;
								case '1':
									$query->where('ld_course_open.start_at','<',time());
									$query->where('ld_course_open.end_at','>',time());
									break;
								case '3':
									$query->where('ld_course_open.end_at','<',time());
									break;	
							}
						}
						if(isset($body['teacherSearch']) && !empty($body['teacherSearch'])){
							$query->where('ld_lecturer_educationa.real_name','like',"%".$body['teacherSearch']."%");
						}
						if(isset($body['classSearch']) && !empty($body['classSearch'])){
							$query->where('ld_course_open.title',$body['classSearch']);
						}
						$query->where('ld_course_open.nature',0);
						$query->where('ld_course_open.is_del',0);
						$query->where('ld_course_open.school_id',$school_id);
						$query->where('ld_lecturer_educationa.type',2);
					})->select('ld_course_open.title as class_name','ld_lecturer_educationa.real_name as teacher_name','ld_course_open.start_at','ld_course_open.end_at','ld_course_open_live_childs.watch_num')
					->get()->toArray();

			//课程
		$courseArr = CourseShiftNo::rightJoin('ld_course_class_number','ld_course_class_number.id','=','ld_course_shift_no.id')
					->rightJoin('ld_course_class_teacher','ld_course_class_number.id','=','ld_course_class_teacher.class_id')
					->rightJoin('ld_lecturer_educationa','ld_lecturer_educationa.id','=','ld_course_class_teacher.teacher_id')
					->rightJoin('ld_course_live_childs','ld_course_live_childs.class_id','=','ld_course_shift_no.id')
					->where(function($query) use ($body,$school_id) {
						if(isset($body['time']) && !empty($body['time'])){
							switch ($body['time']) {
								case '1': //今天
									$query->where('ld_course_class_number.start_at','<',strtotime(date('Y-m-d')));
									$query->where('ld_course_class_number.end_at','>',strtotime(date('Y-m-d 23:59:59')));
									break;
								case '2': //明天
									$query->where('ld_course_class_number.start_at','<',strtotime(date("Y-m-d",strtotime("+1 day"))));
									$query->where('ld_course_class_number.end','>',strtotime(date("Y-m-d 23:59:59",strtotime("+1 day"))));
									break;
								case '3': //昨天
									$query->where('ld_course_class_number.start_at','<',strtotime(date("Y-m-d",strtotime("-1 day"))));
									$query->where('ld_course_class_number.end','>',strtotime(date("Y-m-d 23:59:59",strtotime("-1 day"))));
									break;
							}
							if(is_array($body['time'])){
								$time = json_decode($body['time'],1);
								$query->where('ld_course_class_number.start_at','<',$time[0]);
								$query->where('ld_course_class_number.end','>',$time[1]);
							}
						}
						if(isset($body['status']) && !empty($body['status'])){
							switch ($body['status']) {
								case '2':
									$query->where('ld_course_class_number.start_at','>',time());
									break;
								case '1':
									$query->where('ld_course_class_number.start_at','<',time());
									$query->where('ld_course_class_number.end_at','>',time());
									break;
								case '3':
									$query->where('ld_course_class_number.end_at','<',time());
									break;	
							}
						}
						if(isset($body['teacherSearch']) && !empty($body['teacherSearch'])){
							$query->where('ld_lecturer_educationa.real_name','like',"%".$body['teacherSearch']."%");
						}
						if(isset($body['classSearch']) && !empty($body['classSearch'])){
							$query->where('ld_course_class_number.name',$body['classSearch']);
						}
						if(isset($body['classNoSearch']) && !empty($body['classNoSearch'])){ //
							$query->where('ld_course_shift_no.name',$body['classNoSearch']);
						}
						$query->where('ld_course_shift_no.is_del',0);
						$query->where('ld_course_class_number.is_del',0);
						$query->where('ld_course_class_number.status',1);
						$query->where('ld_course_shift_no.school_id',$school_id);
						$query->where('ld_lecturer_educationa.type',2);
				})->select('ld_course_shift_no.name as classno_name','ld_lecturer_educationa.real_name as class_name','ld_course_class_number.start_at','ld_course_class_number.end_at','ld_lecturer_educationa.real_name as teacher_name','ld_course_live_childs.watch_num')	
				->get()->toArray();
				$newcourseArr = [];
				if(!empty($openCourseArr) && !empty($courseArr) ){
					foreach($openCourseArr as $k=>$v){
						$openCourseArr[$k]['is_public'] = 1;
					}
					foreach($courseArr as $k=>$v){
						$courseArr[$k]['is_public'] = 0;
					}
					$newcourseArr = array_merge($openCourseArr,$courseArr);
					foreach($newcourseArr as $k=>$v){
						$time = (int)$v['end_at']-(int)$v['start_at'];
						$newcourseArr[$k]['time'] = timetodate($time);
						$newcourseArr[$k]['start_time'] = date('Y-m-d H:i:s',$v['start_at']);
						if(time()<$v['start_at']){
							$newcourseArr[$k]['zhibostatus'] = '预开始';
						}
						if(time()>$v['end_at']){
							$newcourseArr[$k]['zhibostatus'] = '直播已结束';
						}
						if(time()>$v['start_at'] && time()<$v['end_at']){
							$newcourseArr[$k]['zhibostatus'] = '直播中';
						}
					}
				}
			return ['code'=>200,'msg'=>'Success','data'=>$newcourseArr,'where'=>$body];
	}
	//教学详情
	//缺少公开课课程列表及课程详情   7.2
	public static function details($body){

		if($body['is_public'] == 1){  //公开课
			$openCourseArr = OpenCourse::where('id',$body['class_id'])->select('id','title','start_at','end_at')->first();//公开课名称
			$openChildsArr = OpenLivesChilds::where('lesson_id',$openCourseArr['id'])->select('watch_num','course_id')->first(); 
			$openCourseArr['watch_num'] = $openChildsArr['watch_num']; //观看人数（学员人数）
			$teacherIds = OpenCourseTeacher::where('course_id',$openCourseArr['id'])->pluck('teacher_id'); //讲师id组
			$openCourseArr['lect_teacher_name'] = Teacher::whereIn(['id'=>$teacherIds,'type'=>'1'])->select('real_name')->first()['real_name'];//讲师
			$eduTeacherName = Teacher::whereIn(['id'=>$teacherIds,'type'=>'2'])->pluck('real_name'); //教务
			$openCourseArr['edu_teacher_name'] = '';
			if(!empty($eduTeacherName)){
				$openCourseArr['edu_teacher_name'] = implode(',', $eduTeacherName);
			}
			$openCourseArr['time'] = timetodate((int)$openCourseArr['end_at']-(int)$openCourseArr['start_at']);//时长
			if($openCourseArr['start_at']>time()){
				$openCourseArr['zhibostatus'] = '待直播';
			}
			if($openCourseArr['end_at']<time()){
				$openCourseArr['zhibostatus'] = '直播已结束';
			}
			if($openCourseArr['start_at']<time() && $openCourseArr['end_at']>time()){
				$openCourseArr['zhibostatus'] = '直播中';
			} 
			$openCourseArr['start_at'] = date('Y-m-d H:i:s',$openCourseArr['start_at']);
			$openCourseArr['end_at'] = date('Y-m-d H:i:s',$openCourseArr['end_at']);
			$MTCloud = new MTCloud();
			$res =$MTCloud->courseDocumentList($body['course_id'],1);
			$openCourseArr['courseware'] = $newArr = [];
			if(!empty($res['data'])){
				foreach($res['data'] as $key =>$v){
					$newArr[] = $MTCloud->documentGet($v['id']))['data'];
				}
				$openCourseArr['courseware'] = $newArr;  //欢拓课件信息
			}
			return ['code'=>200,'msg'=>'Success','data'=>$openCourseArr];
		}
		if($body['is_public'] == 0){  //课程

		}
	}
}
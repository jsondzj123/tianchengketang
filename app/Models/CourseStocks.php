<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class CourseStocks extends Model {
    //指定别的表名
    public $table = 'ld_course_stocks';
    //时间戳设置
    public $timestamps = false;
    //错误信息
    public static function message(){
        return [
            'school_id.required' => json_encode(['code'=>'201','msg'=>'学校标识不能为空']),
            'school_id.integer'  => json_encode(['code'=>'202','msg'=>'学校标识类型不合法']),
            'course_id.required' => json_encode(['code'=>'201','msg'=>'课程标识不能为空']),
            'course_id.integer'  => json_encode(['code'=>'202','msg'=>'课程标识类型不合法']),
        ];
    }
	/*
     * @param  descriptsion 库存列表
     * @param  $school_id   学校id
     * @param  $course_id   课程id
     * @param  author       lys
     * @param  ctime   2020/6/29 
     * return  array
     */
    public static function getCourseStocksList($data){
    	$school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;//当前登录学校id
    	$info = self::where($data)->where(['school_pid'=>$school_id,'is_del'=>0])->orderBy('id','desc')->select('id','create_at','current_number','add_number')->get();
    	$sum_current_number = 0;
    	$residue_number = Order::whereIn('pay_status',[3,4])->where(['class_id'=>$data['course_id'],'school_id'=>$data['school_id'],'oa_status'=>1])->count();
    	foreach($info as $k=>$v){
    		$sum_current_number += $v['add_number'];
    	}
    	$residue_number = $residue_number<=0 ?$sum_current_number:(int)$sum_current_number-(int)$residue_number;
    	return ['code'=>200,'msg'=>'success','data'=>$info,'sum_current_number'=>$sum_current_number,'residue_number'=>$residue_number];
    }
    /*
     * @param  descriptsion 添加库存
     * @param  $school_id   学校id
     * @param  $course_id   课程id
     * @param  $add_number   添加库存数
     * @param  author       lys
     * @param  ctime   2020/6/29 
     * return  array
     */
   	public static function doInsertStocks($data){
   		$data['admin_id'] = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;//当前登录账号id
   		$data['school_pid'] = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0;//当前登录学校id

   		$sum_current_number = self::where($data)->where(['school_pid'=>$data['school_pid'],'is_del'=>0])->orderBy('id','desc')->sum('add_number');//当前已经添加总库存
   		$residue_number = Order::whereIn('pay_status',[3,4])->where(['class_id'=>$data['course_id'],'school_id'=>$data['school_id'],'oa_status'=>1])->count();
		$data['current_number'] = $residue_number<=0 ?$sum_current_number:(int)$sum_current_number-(int)$residue_number;
   		$data['create_at'] = date('Y-m-d H:i:s');
		$result = self::insert($data);
		if($result){
			return ['code'=>200,'msg'=>'添加成功'];
		}else{
			return ['code'=>203,'msg'=>'网络错误,请重试！'];	
		}	
   	}

}
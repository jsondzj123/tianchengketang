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
    	$info = CourseStocks::where($data)->where(['school_pid'=>$school_id,'is_del'=>0])->orderBy('id','desc')->select('id','create_at','current_number','add_number')->get();
    	$sum_current_number = 0;
    	$residue_number = Order::whereIn('pay_status',[3,4])->where(['class_id'=>$data['course_id'],'school_id'=>$data['school_id'],'oa_status'=>1])->count();
    	
    	foreach($info as $k=>$v){
    		$sum_current_number += $v['add_number'];
    	}
    	$residue_number = $residue_number<=0 ?0:(int)$sum_current_number-(int)$residue_number;
    	return ['code'=>200,'msg'=>'success','data'=>$info,'sum_current_number'=>$sum_current_number,'residue_number'=>$residue_number];
    }
    /*
     * @param  descriptsion 库存列表
     * @param  $school_id   学校id
     * @param  $course_id   课程id
     * @param  author       lys
     * @param  ctime   2020/6/29 
     * return  array
     */
   	public static function doInsertCourseStocks($data){

   	}

}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AdminLog;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Models\QuestionSubject as Subject;
use Illuminate\Support\Facades\Redis;

class FootConfig extends Model {
    //指定别的表名
    public $table      = 'ld_footer_config';
    //时间戳设置
    public $timestamps = false;

      //错误信息
    public static function message(){
        return [
            'id.required'  => json_encode(['code'=>'201','msg'=>'id不能为空']),
            'id.integer'   => json_encode(['code'=>'202','msg'=>'id类型不合法']),
            'type.required' => json_encode(['code'=>'201','msg'=>'类型不能为空']),
            'type.integer'  => json_encode(['code'=>'202','msg'=>'类型类型不合法']),
        ];
    }
    public static function getList($body){
    	$schoolid = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前登录的id
    	$school_id = isset($body['school_id']) && $body['school_id'] > 0 ? $body['school_id'] : $school_id; //搜索条件
    	$pageSet = self::where(['is_opne'=>0,'is_del'=>0])
    		->where(function($query) use ($body,$school_id){
    			if($body['school_id'] == '' ){
    				$query->where('school_id',$school_id);
    			}
    	})->get()->toArray(); //
    	$headerArr = $footer = $icp=[];
    	if(!empty($pageSet)){
    		foreach($pageSet  as $key=>$v){
    			if($type== 1){
    				array_push($v,$headerArr);
    			}
    			if($type== 2){
    				array_push($v,$footer);
    			}
    			if($type== 3){
    				array_push($v,$icp);
    			}
    		}
    	}
    	return ['code'=>200,'msg'=>'Success','data'=>['header'=>$headerArr,'footer'=>$footer,'icp'=>$icp]];
    }

    public static function details(){
    	if($type == 1){ //头部
    		if(!isset($body['header_name']) || empty($body['header_name']){
    			return ['code'=>201,'msg'=>'header_name为空'];
    		}	
    		if(!isset($body['header_url']) || empty($body['header_url']){
    			return ['code'=>201,'msg'=>'header_url为空'];
    		}
    		$res = self::where(['id'=>$body['id'],'type'=>$body['type']])->update(['name'=>$body['header_name'],'url'=>$body['header_url'],'update'=>date('Y-m-d H:i:s')]);
    	}
    	if($type == 2){ //尾部 
    		if(!isset($body['foot_name']) || empty($body['foot_name']){
    			return ['code'=>201,'msg'=>'foot_name为空'];
    		}	
    		if(!isset($body['foot_url']) || empty($body['foot_url']){
    			return ['code'=>201,'msg'=>'foot_url为空'];
    		}
    		if(isset($body['text'])){
    			$update['text'] = $body['text'];
    		}
    		$update['name'] = $body['foot_name'];
    		$update['url'] = $body['header_url'];
    		$update['update_at'] = date('Y-m-d H:i:s');
    		$res = self::where(['id'=>$body['id'],'type'=>$body['type']])->update($update);
    	}
    	if($type == 3){ //icp
    		if(!isset($body['icp_name']) || empty($body['icp_name']){
    			return ['code'=>201,'msg'=>'icp为空'];
    		}	
    		$res = self::where(['id'=>$body['id'],'type'=>$body['type']])->update(['name'=>$body['icp_name'],'update'=>date('Y-m-d H:i:s')]);
    	}
    	if($res){
    		return ['code'=>200,'msg'=>'Success'];
    	}else{
    		return ['code'=>203,'msg'=>'网络错误，请重试'];
    	}
    }


}
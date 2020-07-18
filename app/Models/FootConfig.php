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
            'school_id.required' => json_encode(['code'=>'201','msg'=>'学校标识不能为空']),
            'logo.required'  => json_encode(['code'=>'201','msg'=>'logo标识不合法']),
        ];
    }
    public static function getList($body){
    	$schoolid = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前登录的id
	    $school_name='';
    	$school_id = isset($body['school_id']) && $body['school_id'] > 0 ? $body['school_id'] : $schoolid; //搜索条件
    	if($school_id>0){
    		$school=School::where(['id'=>$school_id,'is_del'=>1])->select('name')->first();
    		$school_name = $school['name'];
    	}
    	$pageSet = self::where(['is_del'=>0])
    		->where(function($query) use ($body,$school_id){
    			if(isset($body['school_id']) && $body['school_id'] != '' ){
    				$query->where('school_id',$school_id);
    			}
    	})->get()->toArray(); //
    		
    	$headerArr = $footer = $icp= $logo =[];
    	if(!empty($pageSet)){
    		foreach($pageSet  as $key=>$v){
    			if($v['type']== 1){
    				array_push($headerArr,$v);
    			}
    			if($v['type']== 2){
    				array_push($footer,$v);
    			}
    			if($v['type']== 3){
    				array_push($icp,$v);
    			}
                if($v['type']== 4){
                    array_push($logo,$v);
                }
    		}
    		if(!empty($footer)){
    			$footer =getParentsList($footer);
    		}
    	}
    	
    	return ['code'=>200,'msg'=>'Success','data'=>['header'=>$headerArr,'footer'=>$footer,'icp'=>$icp,'school_name'=>$school_name,'logo'=>$logo]];
    }

    public static function details($body){
    	$body['open'] = isset($body['open']) && $body['open'] > 0 ?1:0;
    	if($body['type'] == 1){ //头部
    		if(!isset($body['name']) || empty($body['name'])){
    			return ['code'=>201,'msg'=>'header_name为空'];
    		}	
    		if(!isset($body['url']) || empty($body['url'])){
    			return ['code'=>201,'msg'=>'header_url为空'];
    		}
    		$res = self::where(['id'=>$body['id'],'type'=>$body['type']])->update(['name'=>$body['name'],'url'=>$body['url'],'is_open'=>$body['open'],'update_at'=>date('Y-m-d H:i:s')]);
    	}
    	if($body['type'] == 2){ //尾部 
    		if(!isset($body['name']) || empty($body['name'])){
    			return ['code'=>201,'msg'=>'foot_name为空'];
    		}	
    		if(!isset($body['url']) || empty($body['url'])){
    			return ['code'=>201,'msg'=>'foot_url为空'];
    		}
    		if(isset($body['text'])){
    			$update['text'] = $body['text'];
    		}
    		$update['name'] = $body['name'];
    		$update['url'] = $body['url'];
    		$update['is_open'] = $body['open'];
    		$update['update_at'] = date('Y-m-d H:i:s');
    		$res = self::where(['id'=>$body['id'],'type'=>$body['type']])->update($update);
    	}
    	if($body['type'] == 3){ //icp
    		if(!isset($body['name']) || empty($body['name'])){
    			return ['code'=>201,'msg'=>'icp为空'];
    		}	
    		$res = self::where(['id'=>$body['id'],'type'=>$body['type']])->update(['name'=>$body['name'],'is_open'=>$body['open'],'update_at'=>date('Y-m-d H:i:s')]);
    	}
    	if($res){
    		return ['code'=>200,'msg'=>'Success'];
    	}else{
    		return ['code'=>203,'msg'=>'网络错误，请重试'];
    	}
    }


    public static function doLogoUpdate($body){
        $school_id = isset(AdminLog::getAdminInfo()->admin_user->school_id) ? AdminLog::getAdminInfo()->admin_user->school_id : 0; //当前登录的id
        $body['school_id'] = isset($body['school_id']) && $body['school_id'] > 0 ?$body['school_id']:$school_id;
        $Logo = self::where(['school_id'=>$body['school_id'],'type'=>4,'is_del'=>0])->first();
        if(empty($Logo)){
            return ['code'=>203,'msg'=>'数据不存在！'];
        }
        $update['update_at'] = date('Y-m-d H:i:s');
        $update['admin_id'] = isset(AdminLog::getAdminInfo()->admin_user->id) ? AdminLog::getAdminInfo()->admin_user->id : 0;
        $update['logo']  = $body['logo'];
        $res = self::where(['school_id'=>$body['school_id'],'type'=>4,'is_del'=>0])->update($update);  
        if($res){
            return ['code'=>200,'msg'=>'更改成功'];
        }else{
            return ['code'=>203,'msg'=>'网络错误，请重试'];
        }  
    }


}
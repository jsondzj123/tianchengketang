<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Live;
use App\Models\Subject;
use App\Models\Teach;
use Illuminate\Http\Request;
use App\Tools\CurrentAdmin;
use Validator;
use App\Tools\MTCloud;
use App\Models\LiveChild;
use App\Models\OpenLivesChilds;
use Log;
use App\Listeners\LiveListener;
//教学
class TeachController extends Controller {

	//教学列表
	public function getList(){
		$result = Teach::getList(self::$accept_data);
        if($result['code'] == 200){
            return response()->json($result);
        }else{
            return response()->json($result);
        }
	}
	//教学详情
	public function details(){
		$validator = Validator::make(self::$accept_data, 
                [
                	'class_id' => 'required',//课次 （公开课的话时课程id）
                	// 'classno_id' => 'required',//班号
                	'is_public' => 'required',//是否未公开课   (1 公开课  0课程)    	
               	],
                Teach::message());
        if($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
		$result = Teach::details(self::$accept_data);
		if($result['code'] == 200){
            return response()->json($result);
        }else{
            return response()->json($result);
        }
	}
	 /**
     * 启动直播
     * @param   auther  lys  2020.7.2
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function startLive()
    {   $data = self::$accept_data;
        $validator = Validator::make($data, [
        	'is_public'=>'required',
            'id' => 'required', 
        ],Teach::message());
        if ($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        if($data['is_public'] == 1){ //公开课
        	$live = OpenLivesChilds::where('lesson_id',$data['id'])->select('course_id')->first();
        }
       	if($data['is_public']== 0){  //课程
 			$live = LiveChild::where('class_id',$data['id'])->select('course_id')->first();
       	}
        $MTCloud = new MTCloud();
        $res = $MTCloud->courseLaunch($live['course_id']);
        Log::error('直播器启动:'.json_encode($res));
        if(!array_key_exists('code', $res) && !$res["code"] == 0){
            return $this->response('直播器启动失败', 500);
        }
        return $this->response($res['data']);
    }
   	/**
     * 查看回放
     * @param   auther  lys  2020.7.2
     * @param  int  $id 
     * @return \Illuminate\Http\Response
     */
   	public function livePlayback(){
   		$data = self::$accept_data;
   		$validator = Validator::make($data, [
   			'is_public'=>'required',
            'id' => 'required', 
        ],Teach::message());
        if($data['is_public'] == 1){ //公开课
        	$live = OpenLivesChilds::where('lesson_id',$data['id'])->select('playbackUrl')->first();
        }
       	if($data['is_public']== 0){  //课程
 			$live = LiveChild::where('class_id',$data['id'])->select('playbackUrl')->first();
       	}
       	return ['code'=>200,'msg'=>'Success','data'=>$live];
   	}

 	/**
     * 课件上传
     * @param   auther  lys  2020.7.2
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */  //欢拓 上传课件有问题
    public function courseUpload(){
  		$data = self::$accept_data;
      $validator = Validator::make($data, [
      	'is_public'=>'required',
        'id' => 'required', //课次id 
      ],Teach::message());
      if ($validator->fails()) {
          return response()->json(json_decode($validator->errors()->first(),1));
      }
      $file = isset($_FILES['file']) && !empty($_FILES['file']) ? $_FILES['file'] : '';
      //判断是否有文件上传
      if(!isset($_FILES['file']) || empty($_FILES['file']['tmp_name'])){
          return response()->json(['code' => 201 , 'msg' => '请上传课件文件']);
      }
      //存放文件路径
      $file_path= app()->basePath() . "/public/upload/editor/" . date('Y-m-d') . '/';
      //判断上传的文件夹是否建立
      if(!file_exists($file_path)){
          mkdir($file_path , 0777 , true);
      }
      //重置文件名
      $filename = time() . rand(1,10000) . uniqid() . substr($file['name'], stripos($file['name'], '.'));
      $path     = $file_path.$filename;
      //判断文件是否是通过 HTTP POST 上传的
      if(is_uploaded_file($_FILES['file']['tmp_name'])){
          //上传文件方法
          $rs =  move_uploaded_file($_FILES['file']['tmp_name'], $path);
          if($rs && !empty($rs)){
              $file=array(
                'name'=>$_FILES['file']['name'],
          			'file'=>$_SERVER['DOCUMENT_ROOT']."/upload/editor/" . date('Y-m-d') . '/'.$filename,
          		);
          	if($data['is_public'] == 1){ //公开课
			        $live = OpenLivesChilds::where('lesson_id',$data['id'])->select('course_id')->first();
		        }
		       	if($data['is_public']== 0){  //课程
		 			    $live = LiveChild::where('class_id',$data['id'])->select('course_id')->first();
		       	}
		       	$MTCloud = new MTCloud();	
		       	$res = $MTCloud->courseDocumentUpload($live['course_id'],$file);
		       	  if(!array_key_exists('code', $res) && !$res["code"] == 0){
                 return $this->response('课件上传失败', 500);
              }
            return  response()->json(['code'=>200,'msg'=>'上传课件成功']);
          } else {
              return response()->json(['code' => 203 , 'msg' => '上传课件失败']);
          } 
      } else {
          return response()->json(['code' => 202 , 'msg' => '上传方式非法']);
      }
    }
    /**
     * 课件删除
     * @param   auther  lys  2020.7.2
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function coursewareDel(){
    	$validator = Validator::make(self::$accept_data, [
        
            'id' => 'required', //课件id
        ],Teach::message());
        if ($validator->fails()) {
            return response()->json(json_decode($validator->errors()->first(),1));
        }
        $MTCloud = new MTCloud();
        $res = $MTCloud->documentDelete(self::$accept_data['id']);
        if(!array_key_exists('code', $res) && !$res["code"] == 0){
            return $this->response('课件删除失败', 500);
        }
        return ['code'=>200,'msg'=>'课件删除成功'];
    }






}
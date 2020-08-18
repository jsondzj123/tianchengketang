<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Coures;
use App\Models\Couresmethod;
use App\Models\CourseRefResource;
use App\Models\CourseSchool;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Articletype;
use App\Models\FootConfig;
use App\Models\Admin;
use App\Models\CouresSubject;
use App\Models\Article;
use App\Models\Order;
use App\Models\CourseRefTeacher;
class MyController extends Controller {
	protected $school;
    protected $data;
    public function __construct(){
        $this->data = $_REQUEST;
        // $this->school = School::where(['dns'=>$this->data['dns']])->first();
    }
    //关于我们
    public function getAbout(){
        print_r($this->data);die;
        
    	$aboutArr = FootConfig::where(['school_id'=>$this->school['id'],'is_del'=>0,'is_open'=>0,'type'=>5])->select('text')->first();
    	$about = isset($aboutArr['text']) ?$aboutArr['text'] :'';
    	return response()->json(['code'=>200,'msg'=>'success','about'=>$about]);
    }
}
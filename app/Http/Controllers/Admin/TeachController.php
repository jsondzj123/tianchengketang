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
use Log;
use App\Listeners\LiveListener;
//教学
class TeachController extends Controller {


	public function getList(){
		 $result = Teach::getList(self::$accept_data);
        if($result['code'] == 200){
            return response()->json($result);
        }else{
            return response()->json($result);
        }
	}

}
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\FootConfig;
use Illuminate\Http\Request;
use App\Tools\CurrentAdmin;
use DB;
use Validator;

class PageSetController extends Controller {
	public function getList(){
	    $res = FootConfig::getList(self::$accept_data);
		return response()->json($res);
	}
	public function details(){
	    $validator = Validator::make(self::$accept_data, 
            [
            	'id' => 'required|integer',
            	'type' => 'required|integer',
           	],
            FootConfig::message());
	    $res = FootConfig::details(self::$accept_data);
		return response()->json($res);
	}
}
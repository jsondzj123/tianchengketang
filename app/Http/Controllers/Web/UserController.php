<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\School;

class UserController extends Controller {
    protected $school;
    protected $data;
    protected $userid;
    public function __construct(){
        $this->data = $_REQUEST;
        $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
        $this->userid = isset(AdminLog::getAdminInfo()->admin_user->id)?AdminLog::getAdminInfo()->admin_user->id:0;
    }
    /*
         * @param  个人信息
         * @param  author  苏振文
         * @param  ctime   2020/7/8 16:32
         * return  array
         */
    public function userDetail(){
        
    }
}


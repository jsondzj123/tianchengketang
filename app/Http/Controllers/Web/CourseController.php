<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class CourseController extends Controller {
    public $school;
    public function __construct(){
    }
    /*
         * @param  课程列表
         * @param  author  苏振文
         * @param  ctime   2020/7/4 17:09
         * return  array
     */
    public function courseList(){
        echo $this->school;
        print_r(self::$accept_data);
    }
}

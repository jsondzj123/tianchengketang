<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\QuestionSubject;
use App\Models\Chapters;
use App\Models\Exam;
use App\Models\ExamOption;
use App\Models\Papers;
use App\Models\PapersExam;
use App\Models\StudentDoTitle;
use App\Models\StudentCollectQuestion;
use App\Models\StudentPapers;
use App\Models\Coures;
use App\Models\Order;
use App\Models\School;
use App\Models\Admin;
use App\Models\CourseRefBank;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


class BankController extends Controller {
    /*
     * @param  description   全部题库接口
     * @param author    dzj
     * @param ctime     2020-07-07
     * return string
     */
    public function getBankList() {
        //获取提交的参数
        try{
            //获取请求的平台端
            $platform = verifyPlat() ? verifyPlat() : 'pc';
            if($platform == 'pc'){
                //分校域名
                $school_dns        = isset(self::$accept_data['school_dns']) && !empty(self::$accept_data['school_dns']) ? self::$accept_data['school_dns'] : '';           //获取学校域名

                //判断学校域名是否传递
                if(!$school_dns || empty($school_dns)){
                    return response()->json(['code' => 201 , 'msg' => '分校域名为空']);
                }

                //根据学校域名获取学校的id
                $school_info = School::where('dns' , $school_dns)->where('is_del' , 1)->where('is_forbid' , 1)->first();
                if(!$school_info || empty($school_info)){
                    return response()->json(['code' => 203 , 'msg' => '此域名不合法']);
                }
                
                //学校id赋值
                $school_id = $school_info['id'];
            } else {
                //判断用户token是否为空
                if(isset(self::$accept_data['user_token']) && !empty(self::$accept_data['user_token'])){
                    //hash中token赋值
                    $token_key   = "user:regtoken:".$platform.":".self::$accept_data['user_token'];

                    //判断token值是否合法
                    $redis_token = Redis::hLen($token_key);
                    if($redis_token <= 0){
                        return response()->json(['code' => 201 , 'msg' => '用户token为空']);
                    }

                    //解析json获取用户详情信息
                    $json_info = Redis::hGetAll($token_key);

                    //学校id赋值
                    $school_id = $json_info['school_id'];
                } else {
                    $school_id = 1;
                }
            }
            
            
            //判断此学校是否是总校
            $school_count = Admin::where('school_id' , $school_id)->where('school_status' , 1)->where('is_forbid' , 1)->where('is_del' , 1)->count();
            
            //判断是否是总校
            if($school_count && $school_count > 0){
                //题库数组赋值
                $bank_array = [];
            
                //获取全部题库的列表
                $bank_list = Bank::select('id' , 'subject_id' , 'topic_name')->where('school_id' , $school_id)->where('is_del' , 0)->where('is_open' , 0)->orderByDesc('id')->get();
                if($bank_list && !empty($bank_list)){
                    foreach($bank_list as $k=>$v){
                        //根据科目的id获取列表数据
                        $subject_list = QuestionSubject::select('id as subject_id' , 'subject_name')->where('bank_id' , $v->id)->where('is_del' , 0)->get();

                        //新数组赋值
                        $bank_array[] = [
                            'bank_id'     =>   $v->id ,
                            'bank_name'   =>   $v->topic_name ,
                            'subject_list'=>   $subject_list
                        ];
                    }
                }
            } else { //分校
                //题库数组赋值
                $bank_array1 = [];
                $bank_array2 = [];
                
                //获取全部题库的列表
                $bank_list = Bank::select('id' , 'subject_id' , 'topic_name')->where('school_id' , $school_id)->where('is_del' , 0)->where('is_open' , 0)->orderByDesc('id')->get();
                if($bank_list && !empty($bank_list)){
                    foreach($bank_list as $k=>$v){
                        //根据科目的id获取列表数据
                        $subject_list = QuestionSubject::select('id as subject_id' , 'subject_name')->where('bank_id' , $v->id)->where('is_del' , 0)->get();

                        //新数组赋值
                        $bank_array1[] = [
                            'bank_id'     =>   $v->id ,
                            'bank_name'   =>   $v->topic_name ,
                            'subject_list'=>   $subject_list
                        ];
                    }
                }
                
                //授权的题库列表
                $bank_list2 = DB::table('ld_course_ref_bank')->select('ld_course_ref_bank.bank_id')->join("ld_question_bank" , function($join){
                    $join->on('ld_course_ref_bank.bank_id', '=', 'ld_question_bank.id');
                })->where('ld_course_ref_bank.to_school_id' , $school_id)->where('ld_course_ref_bank.is_del' , 0)->where('ld_question_bank.is_open' , 0)->where('ld_question_bank.is_del' , 0)->orderByDesc('ld_course_ref_bank.create_at')->get();
                if($bank_list2 && !empty($bank_list2)){
                    foreach($bank_list2 as $k=>$v){
                        //根据题库的id获取题库信息
                        $bank_info = Bank::where('id' , $v->bank_id)->first();

                        //根据科目的id获取列表数据
                        $subject_list2 = QuestionSubject::select('id as subject_id' , 'subject_name')->where('bank_id' , $v->bank_id)->where('is_del' , 0)->get();

                        //新数组赋值
                        $bank_array2[] = [
                            'bank_id'     =>   $v->bank_id ,
                            'bank_name'   =>   $bank_info['topic_name'] ,
                            'subject_list'=>   $subject_list2
                        ];
                    }
                }
                
                //获取总条数
                $bank_array = array_merge((array)$bank_array1 , (array)$bank_array2);
            }
            return response()->json(['code' => 200 , 'msg' => '获取全部题库列表成功' , 'data' => $bank_array]);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    
    /*
     * @param description   判断用户是否有做题的权限
     * @param $bank_id      题库id
     * @param author    dzj
     * @param ctime     2020-07-08
     * return string
     */
    public static function verifyUserExamJurisdiction($bank_id){
        //判断用户是否有做题的权限
        /*$bank_info = Bank::where('id' , $bank_id)->where('is_del' , 0)->where('is_open' , 0)->first();
        
        //判断题库是否存在
        if(!$bank_info || empty($bank_info)){
            return ['code' => 209 , 'msg' => '您没有做题权限'];
        }
        
        //通过学科大小类找到对应的课程
        $course_list = Coures::where('parent_id' , $bank_info['parent_id'])->where('is_del' , 0)->get()->toArray();
        if(count($course_list) <= 0){
            return ['code' => 209 , 'msg' => '您没有做题权限'];
        }
        
        //获取课程的id
        $course_id_list = array_column($course_list , 'id');
        
        //通过订单表查询是否购买过
        $order_count = Order::where('student_id' , self::$accept_data['user_info']['user_id'])->whereIn('class_id' , $course_id_list)->where('status' , 2)->count();
        if($order_count <= 0){
            return ['code' => 209 , 'msg' => '您没有做题权限'];
        }
        return ['code' => 200 , 'msg' => '可以做题啦'];*/
        
        //可做题库数量
        $bank_list11 = DB::table('ld_question_bank')->selectRaw("any_value(ld_question_bank.id) as bank_id")->join("ld_course" , function($join){
            $join->on('ld_course.parent_id', '=', 'ld_question_bank.parent_id');
        })->join("ld_order" , function($join){
            $join->on('ld_course.id', '=', 'ld_order.class_id');
        })->where('ld_order.student_id' , self::$accept_data['user_info']['user_id'])->where('ld_question_bank.id' , $bank_id)->where('ld_question_bank.is_del' , 0)->where('ld_question_bank.is_open' , 0)->where('ld_course.is_del' , 0)->where('ld_order.status' , 2)->where('ld_order.nature' , 0)->groupBy('ld_question_bank.id')->get()->count();

        //授权题库
        $bank_list12 = DB::table('ld_question_bank')->selectRaw("any_value(ld_question_bank.id) as bank_id")->join("ld_course_ref_bank" , function($join){
            $join->on('ld_course_ref_bank.bank_id', '=', 'ld_question_bank.id');
        })->join("ld_course_school" , function($join){
            $join->on('ld_course_school.parent_id', '=', 'ld_question_bank.parent_id');
        })->join("ld_order" , function($join){
            $join->on('ld_course_school.id', '=', 'ld_order.class_id');
        })->where('ld_order.student_id' , self::$accept_data['user_info']['user_id'])->where('ld_question_bank.id' , $bank_id)->where('ld_question_bank.is_del' , 0)->where('ld_question_bank.is_open' , 0)->where('ld_course_school.is_del' , 0)->where('ld_order.status' , 2)->where('ld_order.nature' , 1)->groupBy('ld_question_bank.id')->get()->count();
        
        $count = $bank_list11 + $bank_list12;
        if($count <= 0){
            return ['code' => 209 , 'msg' => '您没有做题权限'];
        }
        return ['code' => 200 , 'msg' => '可以做题啦'];
    }
    
    /*
     * @param  description   题库章节列表接口
     * @param author    dzj
     * @param ctime     2020-07-07
     * return string
     */
    public function getBankChaptersList(){
        $bank_id        = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;           //获取题库的id
        $subject_id     = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;  //获取题库科目的id
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //章节新数组
        $chapters_array = [];
        
        //获取章列表
        $chapters_list = Chapters::where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("type" , 0)->where("is_del" , 0)->orderByDesc('id')->get();
        if($chapters_list && !empty($chapters_list)){
            $chapters_list = $chapters_list->toArray();
            foreach($chapters_list as $k=>$v){
                //根据章id获取节列表
                $joint_list = Chapters::select('id as joint_id' , 'name as joint_name')->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('parent_id' , $v['id'])->where("type" , 1)->where("is_del" , 0)->get();
                if($joint_list && !empty($joint_list)){
                    $joint_list = $joint_list->toArray();
                    foreach($joint_list as $k1=>$v1){
                        //根据节id获取试题的数量
                        $exam_count = Exam::where('bank_id' , $bank_id)->where('subject_id' , $subject_id)->where('chapter_id' , $v['id'])->where('joint_id' , $v1['joint_id'])->where('is_publish' , 1)->where('is_del' , 0)->count();
                        $joint_list[$k1]['exam_count'] = $exam_count;
                    }
                }
                
                //根据章的id获取试题的总数
                $exam_sum_count = Exam::where('bank_id' , $bank_id)->where('subject_id' , $subject_id)->where('chapter_id' , $v['id'])->where('is_publish' , 1)->where('is_del' , 0)->count();
                
                //新数组赋值
                $chapters_array[] = [
                    'chapters_id'     =>   $v['id'] ,
                    'chapters_name'   =>   $v['name']  ,
                    'exam_sum_count'  =>   $exam_sum_count > 0 ? $exam_sum_count : 0 ,
                    'joint_list'      =>   $joint_list
                ];
            }
        } 
        return response()->json(['code' => 200 , 'msg' => '获取题库章节列表成功' , 'data' => $chapters_array]);
    }
    
    
    /*
     * @param  description   做题设置接口
     * @param author    dzj
     * @param ctime     2020-07-07
     * return string
     */
    public function getExamSet(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库的id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取题库科目的id
        $chapter_id   = isset(self::$accept_data['chapter_id']) && self::$accept_data['chapter_id'] > 0 ? self::$accept_data['chapter_id'] : 0;           //获取章的id
        $joint_id     = isset(self::$accept_data['joint_id']) && self::$accept_data['joint_id'] > 0 ? self::$accept_data['joint_id'] : 0;                 //获取节的id
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //判断章的id是否传递合法
        if(!$chapter_id || $chapter_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '章id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //设置题型数组
        $exam_type_array = [];
        //分类数组
        $type_array      = [];
        
        $array = [1=>'单选题',2=>'多选题',3=>'判断题',4=>'不定项',5=>'填空题',6=>'简答题'];
        
        
        //题型数据
        $exam_type_list   = Exam::selectRaw("type , count('type') as t_count")->where('chapter_id' , $chapter_id)->where('joint_id' , $joint_id)->where('is_del' , 0)->where('is_publish' , 1)->groupBy('type')->get()->toArray();
        if($exam_type_list && !empty($exam_type_list)){
            for($i=0;$i<6;$i++){
                if(isset($exam_type_list[$i]['type']) && !empty($exam_type_list[$i]['type']) && isset($array[$exam_type_list[$i]['type']])) {
                    $exam_type_array[] = ['type' => $exam_type_list[$i]['type'] , 'name'   =>  $array[$exam_type_list[$i]['type']] , 'count'  =>  $exam_type_list[$i]['t_count']];
                } else {
                    $exam_type_array[] = ['type' => $i+1 , 'name'   =>  $array[$i+1] , 'count'  =>  0];
                }
            }
        } else {
            $exam_type_array  = [
                [
                    'type'   =>  1 ,
                    'name'   =>  '单选题' ,
                    'count'  =>  0
                ] ,
                [
                    'type'   =>  2 ,
                    'name'   =>  '多选题' ,
                    'count'  =>  0
                ] ,
                [
                    'type'   =>  3 ,
                    'name'   =>  '判断题' ,
                    'count'  =>  0
                ] ,
                [
                    'type'   =>  4 ,
                    'name'   =>  '不定项' ,
                    'count'  =>  0
                ] ,
                [
                    'type'   =>  5 ,
                    'name'   =>  '填空题' ,
                    'count'  =>  0
                ] ,
                [
                    'type'   =>  6 ,
                    'name'   =>  '简答题' ,
                    'count'  =>  0
                ]
            ];
        }
        
        //根据章id和节id获取数量
        $exam_count = Exam::where('chapter_id' , $chapter_id)->where('joint_id' , $joint_id)->where('is_del' , 0)->where('is_publish' , 1)->count();
        
        //判断显示最大试题数量
        //$exam_count = $exam_count > 100 ? 100 : $exam_count;
        
        //未做试题数量
        $no_exam_count = StudentDoTitle::select(DB::raw("any_value(exam_id) as exam_id"))->where("student_id" , self::$accept_data['user_info']['user_id'])->where('bank_id' , $bank_id)->where('subject_id' , $subject_id)->where('chapter_id' , $chapter_id)->where('joint_id' , $joint_id)->where('type' , 1)->where('is_right' , 2)->where('answer' , '=' , '')->groupBy('exam_id')->get()->count();
        $no_exam_count = $no_exam_count > 0 ? $no_exam_count : $exam_count;
        
        //错题数量
        $error_exam_count = StudentDoTitle::select(DB::raw("any_value(exam_id) as exam_id"))->where("student_id" , self::$accept_data['user_info']['user_id'])->where('bank_id' , $bank_id)->where('subject_id' , $subject_id)->where('chapter_id' , $chapter_id)->where('joint_id' , $joint_id)->where('type' , 1)->where('is_right' , 2)->where('answer' , '!=' , '')->groupBy('exam_id')->get()->count();

        //分类
        $type_array = [
            ['type' => 1 , 'name' => "全部题(".$exam_count.")"] ,
            ['type' => 2 , 'name' => "未做题(".$no_exam_count.")"] ,
            ['type' => 3 , 'name' => "错题(".$error_exam_count.")"] ,
        ];
        
        //题量
        $count_array = [['type' => 1 , 'name' => "30道题"] , ['type' => 2 , 'name' => "60道题"] , ['type' => 3, 'name' => "100道题"]];
        return response()->json(['code' => 200 , 'msg' => '获取设置列表成功' , 'data' => ['exam_type_array' => $exam_type_array , 'type_array' => $type_array , 'count_array' => $count_array]]);
    }
    
    /*
     * @param  description   章节练习/快速做题/模拟真题随机生成试题接口
     * @param author    dzj
     * @param ctime     2020-07-07
     * return string
     */
    public function doRandExamList(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        $chapter_id   = isset(self::$accept_data['chapter_id']) && self::$accept_data['chapter_id'] > 0 ? self::$accept_data['chapter_id'] : 0;           //获取章的id
        $joint_id     = isset(self::$accept_data['joint_id']) && self::$accept_data['joint_id'] > 0 ? self::$accept_data['joint_id'] : 0;                 //获取节的id
        $papers_id    = isset(self::$accept_data['papers_id']) && self::$accept_data['papers_id'] > 0 ? self::$accept_data['papers_id'] : 0;              //获取试卷的id
        $type         = isset(self::$accept_data['type']) && self::$accept_data['type'] > 0 ? self::$accept_data['type'] : 0;                             //获取类型(1代表章节练习2代表快速做题3代表模拟真题)
        $model        = isset(self::$accept_data['model']) && self::$accept_data['model'] > 0 ? self::$accept_data['model'] : 0;                          //获取模式

        //判断类型是否传递
        if($type <= 0 || !in_array($type , [1,2,3])){
            return response()->json(['code' => 202 , 'msg' => '类型不合法']);
        }

        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //题型数组
        $exam_type_arr = [1=>'单选题',2=>'多选题',3=>'判断题',4=>'不定项',5=>'填空题',6=>'简答题'];
        
        //判断是否为章节练习
        if($type == 1){
            //判断章的id是否传递合法
            if(!$chapter_id || $chapter_id <= 0){
                return response()->json(['code' => 202 , 'msg' => '章id不合法']);
            }
            
            //新数组赋值
            $exam_array = [];            
            
            //判断是否做完了随机生成的快速做题数量
            $rand_exam_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('chapter_id' , $chapter_id)->where('joint_id' , $joint_id)->where('is_right' , 0)->where('type' , 1)->count();
            if($rand_exam_count <= 0){
                //获取题型[1,2]
                $question_type = isset(self::$accept_data['question_type']) && !empty(self::$accept_data['question_type']) ? self::$accept_data['question_type'] : '';
                if(!$question_type || empty($question_type)){
                    return response()->json(['code' => 201 , 'msg' => '请选择题型']);
                }
                $question_type = json_decode($question_type , true);
                foreach ($question_type as $key=>$value){
                    if ($value === 5 || $value === 6)
                      unset($question_type[$key]);
                }

                //获取分类
                $exam_type = isset(self::$accept_data['exam_type']) && !empty(self::$accept_data['exam_type']) ? self::$accept_data['exam_type'] : '';
                if(!$exam_type || empty($exam_type)){
                    return response()->json(['code' => 201 , 'msg' => '请选择分类']);
                } 

                //判断题型是否合法
                if(!in_array($exam_type , [1,2,3])){
                    return response()->json(['code' => 202 , 'msg' => '分类不合法']);
                }

                //获取题量
                $exam_count = isset(self::$accept_data['exam_count']) && !empty(self::$accept_data['exam_count']) ? self::$accept_data['exam_count'] : '';
                if(!$exam_count || empty($exam_count)){
                    return response()->json(['code' => 201 , 'msg' => '请选择题量']);
                } 

                $exam_count_array = [1=>30,2=>60,3=>100];
                //判断题量是否合法
                if(!in_array($exam_count , [1,2,3])){
                    return response()->json(['code' => 202 , 'msg' => '题量不合法']);
                }

                //判断选择模式是否合法
                if(!in_array($model , [1,2])){
                    return response()->json(['code' => 202 , 'msg' => '模式不合法']);
                }

                //根据设置的条件筛选试题
                /*$exam_list = Exam::select("id")->where([['bank_id' , '=' , $bank_id] , ['subject_id' , '=' , $subject_id] , ['chapter_id' , '=' , $chapter_id] , ['joint_id' , '=' , $joint_id] , ['is_del' , '=' , 0] , ['is_publish' , '=' , 1]])->whereIn('type' , $question_type)->orderByRaw("RAND()")->limit($exam_count_array[$exam_count])->get();
                if(!$exam_list || empty($exam_list) || count($exam_list) <= 0){
                    return response()->json(['code' => 203 , 'msg' => '暂无随机生成的试题']);
                }*/
                
                //判断是全部题,未做题,错题
                if($exam_type == 1){
                    //根据设置的条件筛选试题
                    $exam_list = Exam::select("id")->where([['bank_id' , '=' , $bank_id] , ['subject_id' , '=' , $subject_id] , ['chapter_id' , '=' , $chapter_id] , ['joint_id' , '=' , $joint_id] , ['is_del' , '=' , 0] , ['is_publish' , '=' , 1]])->whereIn('type' , $question_type)->orderByRaw("RAND()")->limit($exam_count_array[$exam_count])->get();
                    if(!$exam_list || empty($exam_list) || count($exam_list) <= 0){
                        return response()->json(['code' => 203 , 'msg' => '暂无随机生成的试题']);
                    }
                } else if($exam_type == 2){//未做题
                    $no_exam_count = StudentDoTitle::select(DB::raw("any_value(exam_id) as id"))->where("student_id" , self::$accept_data['user_info']['user_id'])->where('bank_id' , $bank_id)->where('subject_id' , $subject_id)->where('chapter_id' , $chapter_id)->where('joint_id' , $joint_id)->where('type' , 1)->where('is_right' , 2)->where('answer' , '=' , '')->groupBy('exam_id')->get()->count();
                    if($no_exam_count <= 0){
                        //根据设置的条件筛选试题
                        $exam_list = Exam::select("id")->where([['bank_id' , '=' , $bank_id] , ['subject_id' , '=' , $subject_id] , ['chapter_id' , '=' , $chapter_id] , ['joint_id' , '=' , $joint_id] , ['is_del' , '=' , 0] , ['is_publish' , '=' , 1]])->whereIn('type' , $question_type)->orderByRaw("RAND()")->limit($exam_count_array[$exam_count])->get();
                        if(!$exam_list || empty($exam_list) || count($exam_list) <= 0){
                            return response()->json(['code' => 203 , 'msg' => '暂无随机生成的试题']);
                        }
                    } else {
                        $exam_list = StudentDoTitle::join("ld_question_exam","ld_student_do_title.exam_id","=","ld_question_exam.id")->select(DB::raw("any_value(ld_student_do_title.exam_id) as id"))->where("ld_student_do_title.student_id" , self::$accept_data['user_info']['user_id'])->where('ld_student_do_title.bank_id' , $bank_id)->where('ld_student_do_title.subject_id' , $subject_id)->where('ld_student_do_title.chapter_id' , $chapter_id)->where('ld_student_do_title.joint_id' , $joint_id)->where('ld_student_do_title.type' , 1)->where('ld_student_do_title.is_right' , 2)->where('ld_student_do_title.answer' , '=' , '')->whereIn('ld_question_exam.type' , $question_type)->groupBy('ld_student_do_title.exam_id')->orderByRaw("RAND()")->limit($exam_count_array[$exam_count])->get()->toArray();
                        if(!$exam_list || empty($exam_list) || count($exam_list) <= 0){
                            return response()->json(['code' => 203 , 'msg' => '暂无随机生成的试题']);
                        }
                    }
                } else if($exam_type == 3){//错题
                    $error_exam_count = StudentDoTitle::join("ld_question_exam","ld_student_do_title.exam_id","=","ld_question_exam.id")->select(DB::raw("any_value(ld_student_do_title.exam_id) as id"))->where("ld_student_do_title.student_id" , self::$accept_data['user_info']['user_id'])->where('ld_student_do_title.bank_id' , $bank_id)->where('ld_student_do_title.subject_id' , $subject_id)->where('ld_student_do_title.chapter_id' , $chapter_id)->where('ld_student_do_title.joint_id' , $joint_id)->where('ld_student_do_title.type' , 1)->where('ld_student_do_title.is_right' , 2)->where('ld_student_do_title.answer' , '!=' , '')->whereIn('ld_question_exam.type' , $question_type)->groupBy('ld_student_do_title.exam_id')->get()->count();
                    if($error_exam_count <= 0){
                        return response()->json(['code' => 203 , 'msg' => '暂无随机生成的试题']);
                    } else {
                        $exam_list = StudentDoTitle::join("ld_question_exam","ld_student_do_title.exam_id","=","ld_question_exam.id")->select(DB::raw("any_value(ld_student_do_title.exam_id) as id"))->where("ld_student_do_title.student_id" , self::$accept_data['user_info']['user_id'])->where('ld_student_do_title.bank_id' , $bank_id)->where('ld_student_do_title.subject_id' , $subject_id)->where('ld_student_do_title.chapter_id' , $chapter_id)->where('ld_student_do_title.joint_id' , $joint_id)->where('ld_student_do_title.type' , 1)->where('ld_student_do_title.is_right' , 2)->where('ld_student_do_title.answer' , '!=' , '')->whereIn('ld_question_exam.type' , $question_type)->groupBy('ld_student_do_title.exam_id')->orderByRaw("RAND()")->limit($exam_count_array[$exam_count])->get()->toArray();
                        if(!$exam_list || empty($exam_list) || count($exam_list) <= 0){
                            return response()->json(['code' => 203 , 'msg' => '暂无随机生成的试题']);
                        }
                    }
                }
                
                //保存章节试卷得信息
                $papers_id = StudentPapers::insertGetId([
                    'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                    'bank_id'      =>   $bank_id ,
                    'subject_id'   =>   $subject_id ,
                    'chapter_id'   =>   $chapter_id ,
                    'joint_id'     =>   $joint_id ,
                    'model'        =>   $model ,
                    'type'         =>   1 ,
                    'create_at'    =>   date('Y-m-d H:i:s') ,
                    'update_at'    =>   date('Y-m-d H:i:s')
                ]);
                
                //保存随机生成的试题
                foreach($exam_list as $k=>$v){
                    //循环插入试题
                    $rand_exam_id = StudentDoTitle::insertGetId([
                        'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                        'bank_id'      =>   $bank_id ,
                        'subject_id'   =>   $subject_id ,
                        'chapter_id'   =>   $chapter_id ,
                        'papers_id'    =>   $papers_id ,
                        'joint_id'     =>   $joint_id ,
                        'exam_id'      =>   $v['id'] ,
                        'type'         =>   1 ,
                        'create_at'    =>   date('Y-m-d H:i:s')
                    ]);
                    
                    //根据试题的id获取试题详情
                    $exam_info = Exam::where('id' , $v['id'])->first();
                    
                    //单选题,多选题,不定项
                    if(in_array($exam_info['type'] , [1,2,4])){
                        //根据试题的id获取选项
                        $option_info = ExamOption::where("exam_id" , $v['id'])->first();
                        
                        //选项转化
                        $option_content = json_decode($option_info['option_content'] , true);
                        
                        //获取试题类型
                        $exam_type_name = $exam_type_arr[$exam_info['type']];
                    } else {
                        $option_content = [];
                        $exam_type_name = $exam_info['type'] == 3 ? $exam_type_arr[$exam_info['type']] : "";
                    }
                    
                    //试题随机展示
                    $exam_array[$exam_info['type']][] = [
                        'papers_id'           =>  $papers_id ,
                        'exam_id'             =>  $v['id'] ,
                        'exam_name'           =>  $exam_info['exam_content'] ,
                        'exam_type_name'      =>  $exam_type_name ,
                        'text_analysis'       =>  $exam_info['text_analysis'] ,
                        'correct_answer'      =>  trim($exam_info['answer']) ,
                        'option_list'         =>  $option_content ,
                        'my_answer'           =>  '' ,
                        'is_right'            =>  0 ,
                        'is_collect'          =>  0 ,
                        'type'                =>  1
                    ];
                }
            } else {
                //查询还未做完的试卷
                $student_papers_info = StudentPapers::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('chapter_id' , $chapter_id)->where('joint_id' , $joint_id)->where('type' , 1)->where('is_over' , 0)->first();
                //试卷id
                $papers_id = $student_papers_info['id'];
                
                //查询还未做完的题列表
                $exam_list = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("papers_id" , $papers_id)->where('type' , 1)->get();
                foreach($exam_list as $k=>$v){
                    //根据试题的id获取试题详情
                    $exam_info = Exam::where('id' , $v['exam_id'])->first();
                    
                    //单选题,多选题,不定项
                    if(in_array($exam_info['type'] , [1,2,4])){
                        //根据试题的id获取选项
                        $option_info = ExamOption::where("exam_id" , $v['exam_id'])->first();
                        
                        //选项转化
                        $option_content = json_decode($option_info['option_content'] , true);
                        
                        //获取试题类型
                        $exam_type_name = $exam_type_arr[$exam_info['type']];
                    } else {
                        $option_content = [];
                        $exam_type_name = $exam_info['type'] == 3 ? $exam_type_arr[$exam_info['type']] : "";
                    }
                    
                    //判断学员是否收藏此题
                    //$is_collect =  StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("papers_id" , $v['papers_id'])->where('exam_id' , $v['exam_id'])->where('type' , 1)->where('status' , 1)->count();
                    $is_collect =  StudentCollectQuestion::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('exam_id' , $v['exam_id'])->where('status' , 1)->count();
                    
                    //试题随机展示
                    $exam_array[$exam_info['type']][] = [
                        'papers_id'           =>  $v['papers_id'] ,
                        'exam_id'             =>  $v['exam_id'] ,
                        'exam_name'           =>  $exam_info['exam_content'] ,
                        'exam_type_name'      =>  $exam_type_name ,
                        'text_analysis'       =>  $exam_info['text_analysis'] ,
                        'correct_answer'      =>  trim($exam_info['answer']) ,
                        'option_list'         =>  $option_content ,
                        'my_answer'           =>  !empty($v['answer']) ? $v['answer'] : '' ,
                        'is_right'            =>  $v['is_right'] ,
                        'is_collect'          =>  $is_collect ? 1 : 0 ,
                        'type'                =>  1
                    ];
                }
                //模式返回
                $model = $student_papers_info['model'];
            }
        } else if($type == 2){  //快速做题
            //新数组赋值
            $exam_array = [];            
            
            //判断是否做完了随机生成的快速做题数量
            $rand_exam_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('is_right' , 0)->where('type' , 2)->count();
            if($rand_exam_count <= 0){
                //快速做题随机生成20条数据
                $exam_list = Exam::select("id","exam_content","answer")->where([['bank_id' , '=' , $bank_id] , ['subject_id' , '=' , $subject_id] , ['is_del' , '=' , 0] , ['is_publish' , '=' , 1]])->whereIn('type' , [1,2,3,4])->orderByRaw("RAND()")->limit(20)->get();
                if(!$exam_list || empty($exam_list) || count($exam_list) <= 0){
                    return response()->json(['code' => 203 , 'msg' => '暂无随机生成的试题']);
                }
                
                //保存章节试卷得信息
                $papers_id = StudentPapers::insertGetId([
                    'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                    'bank_id'      =>   $bank_id ,
                    'subject_id'   =>   $subject_id ,
                    'type'         =>   2 ,
                    'create_at'    =>   date('Y-m-d H:i:s') ,
                    'update_at'    =>   date('Y-m-d H:i:s')
                ]);
                
                //保存随机生成的试题
                foreach($exam_list as $k=>$v){
                    //循环插入试题
                    $rand_exam_id = StudentDoTitle::insertGetId([
                        'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                        'bank_id'      =>   $bank_id ,
                        'subject_id'   =>   $subject_id ,
                        'papers_id'    =>   $papers_id ,
                        'exam_id'      =>   $v['id'] ,
                        'type'         =>   2 ,
                        'create_at'    =>   date('Y-m-d H:i:s')
                    ]);
                    
                    //根据试题的id获取试题详情
                    $exam_info = Exam::where('id' , $v['id'])->first();
                    
                    //单选题,多选题,不定项
                    if(in_array($exam_info['type'] , [1,2,4])){
                        //根据试题的id获取选项
                        $option_info = ExamOption::where("exam_id" , $v['id'])->first();
                        
                        //选项转化
                        $option_content = json_decode($option_info['option_content'] , true);
                        
                        //获取试题类型
                        $exam_type_name = $exam_type_arr[$exam_info['type']];
                    } else {
                        $option_content = [];
                        $exam_type_name = $exam_info['type'] == 3 ? $exam_type_arr[$exam_info['type']] : "";
                    }
                    
                    //试题随机展示
                    $exam_array[$exam_info['type']][] = [
                        'papers_id'           =>  $papers_id ,
                        'exam_id'             =>  $v['id'] ,
                        'exam_name'           =>  $exam_info['exam_content'] ,
                        'exam_type_name'      =>  $exam_type_name ,
                        'text_analysis'       =>  $exam_info['text_analysis'] ,
                        'correct_answer'      =>  trim($exam_info['answer']) ,
                        'option_list'         =>  $option_content ,
                        'my_answer'           =>  '' ,
                        'is_right'            =>  0  ,
                        'is_collect'          =>  0  ,
                        'type'                =>  2
                    ];
                }
            } else {
                //查询还未做完的试卷
                $student_papers_info = StudentPapers::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('type' , 2)->where('is_over' , 0)->first();
                //试卷id
                $papers_id = $student_papers_info['id'];
                
                //查询还未做完的题列表
                $exam_list = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("papers_id" , $papers_id)->where('type' , 2)->get();
                foreach($exam_list as $k=>$v){
                    //根据试题的id获取试题详情
                    $exam_info = Exam::where('id' , $v['exam_id'])->first();
                    
                    //单选题,多选题,不定项
                    if(in_array($exam_info['type'] , [1,2,4])){
                        //根据试题的id获取选项
                        $option_info = ExamOption::where("exam_id" , $v['exam_id'])->first();
                        
                        //选项转化
                        $option_content = json_decode($option_info['option_content'] , true);
                        
                        //获取试题类型
                        $exam_type_name = $exam_type_arr[$exam_info['type']];
                    } else {
                        $option_content = [];
                        $exam_type_name = $exam_info['type'] == 3 ? $exam_type_arr[$exam_info['type']] : "";
                    }
                    
                    //判断学员是否收藏此题
                    //$is_collect =  StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("papers_id" , $v['papers_id'])->where('exam_id' , $v['exam_id'])->where('type' , 2)->where('status' , 1)->count();
                    $is_collect =  StudentCollectQuestion::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('exam_id' , $v['exam_id'])->where('status' , 1)->count();
                    
                    //试题随机展示
                    $exam_array[$exam_info['type']][] = [
                        'papers_id'           =>  $v['papers_id'] ,
                        'exam_id'             =>  $v['exam_id'] ,
                        'exam_name'           =>  $exam_info['exam_content'] ,
                        'exam_type_name'      =>  $exam_type_name ,
                        'text_analysis'       =>  $exam_info['text_analysis'] ,
                        'correct_answer'      =>  trim($exam_info['answer']) ,
                        'option_list'         =>  $option_content ,
                        'my_answer'           =>  !empty($v['answer']) ? $v['answer'] : '' ,
                        'is_right'            =>  $v['is_right'] ,
                        'is_collect'          =>  $is_collect ? 1 : 0 ,
                        'type'                =>  2
                    ];
                }
            }
        } else if($type == 3){  //模拟真题
            //新数组赋值
            $exam_array = [];
            
            //判断试卷的id是否合法
            if(!$papers_id || $papers_id <= 0){
                return response()->json(['code' => 202 , 'msg' => '试卷id不合法']);
            }
            
            //判断此试卷是否被做完了
            $is_over = StudentPapers::where('student_id' , self::$accept_data['user_info']['user_id'])->where('bank_id' , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $papers_id)->where('type' , 3)->where('is_over' , 1)->count();
            if($is_over && $is_over > 0){
                return response()->json(['code' => 209 , 'msg' => '此试卷你已经做过了']);
            }
            
            //通过试卷的id获取下面的试题列表
            $papers_exam = PapersExam::where("papers_id" , $papers_id)->where("subject_id" , $subject_id)->where("is_del" , 0)->whereIn("type" ,[1,2,3,4])->get();
            if(!$papers_exam || empty($papers_exam) || count($papers_exam) <= 0){
                return response()->json(['code' => 209 , 'msg' => '此试卷下暂无试题']);
            }
            
            foreach($papers_exam as $k=>$v){
                //根据试题的id获取试题详情
                $exam_info = Exam::where('id' , $v['exam_id'])->first();

                //单选题,多选题,不定项
                if(in_array($exam_info['type'] , [1,2,4])){
                    //根据试题的id获取选项
                    $option_info = ExamOption::where("exam_id" , $v['exam_id'])->first();

                    //选项转化
                    $option_content = json_decode($option_info['option_content'] , true);
                    
                    //获取试题类型
                    $exam_type_name = $exam_type_arr[$exam_info['type']];
                } else {
                    $option_content = [];
                    $exam_type_name = $exam_info['type'] == 3 ? $exam_type_arr[$exam_info['type']] : "";
                }
                
                //判断学员是否收藏此题
                //$is_collect =  StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("papers_id" , $papers_id)->where('exam_id' , $v['exam_id'])->where('type' , 3)->where('status' , 1)->count();
                $is_collect =  StudentCollectQuestion::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('exam_id' , $v['exam_id'])->where('status' , 1)->count();
                
                //根据条件获取此学生此题是否答了
                $info = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("papers_id" , $papers_id)->where("subject_id" , $subject_id)->where('exam_id' , $v['exam_id'])->where('type' , 3)->first();

                //试题随机展示
                $exam_array[$exam_info['type']][] = [
                    'papers_id'           =>  $papers_id ,
                    'exam_id'             =>  $v['exam_id'] ,
                    'exam_name'           =>  $exam_info['exam_content'] ,
                    'exam_type_name'      =>  $exam_type_name ,
                    'text_analysis'       =>  $exam_info['text_analysis'] ,
                    'correct_answer'      =>  trim($exam_info['answer']) ,
                    'option_list'         =>  $option_content ,
                    'my_answer'           =>  $info && !empty($info) && !empty($info['answer']) ? $info['answer'] : '' ,
                    'is_right'            =>  $info && !empty($info) ? $info['is_right'] : 0 ,
                    'is_collect'          =>  $is_collect ? 1 : 0 ,
                    'type'                =>  3
                ];
            }
        }
        
        //判断是否为章节
        if($type == 1){
            //返回数据信息
            return response()->json(['code' => 200 , 'msg' => '操作成功' , 'data' => $exam_array , 'model' => $model]);
        } else {
            //返回数据信息
            return response()->json(['code' => 200 , 'msg' => '操作成功' , 'data' => $exam_array]);
        }
    }
    
    /*
     * @param  description   模拟真题试卷列表接口
     * @param author    dzj
     * @param ctime     2020-07-07
     * return string
     */
    public function getExamPapersList(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //通过题库和科目id查询试卷的列表
        $exam_array = Papers::where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("is_del" , 0)->where("is_publish" , 1)->get()->toArray();
        if(!$exam_array || empty($exam_array)){
            return response()->json(['code' => 203 , 'msg' => '暂无对应的试卷']);
        }
        
        //数组赋值
        $papers_score_score = [];

        //循环数据
        foreach($exam_array as $k=>$v){
            //判断学员是否提交了试卷
            $info = StudentPapers::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $v['id'])->where('type' , 3)->first();
            if($info && !empty($info)){
                $sum_score    =  !empty($info['answer_score']) ? $info['answer_score'] : 0;
                $answer_time  =  !empty($info['answer_time']) ? $info['answer_time'] : '';
                $is_over      =  $info['is_over'];
            } else {
                $sum_score    =  0;
                $answer_time  =  '';
                $is_over      =  0;
            }
            
            
            
            //算出试卷的总得分
            $info = PapersExam::where("subject_id" , $subject_id)->where("papers_id" , $v['id'])->where('is_del' , 0)->get()->toArray();
            if($info && !empty($info)){
                foreach($info as $k1=>$v1){
                    //获取试题的详细信息
                    $exam_info = Exam::where('id' , $v1['exam_id'])->first();

                    //判断题型
                    if($exam_info['type'] == 1){
                        $score = $v['signle_score'];
                    } elseif($exam_info['type'] == 2){
                        $score = $v['more_score'];
                    } elseif($exam_info['type'] == 3){
                        $score = $v['judge_score'];
                    } elseif($exam_info['type'] == 4){
                        $score = $v['options_score'];
                    } else {
                        $score = 0;
                    }
                    $papers_score_score[] = $score;
                }
            }
            

            $array[] = [
                'papers_id'    =>  $v['id'] ,
                'papers_name'  =>  $v['papers_name'] ,
                'papers_time'  =>  $v['papers_time'] ,
                'answer_time'  =>  $answer_time ,
                'papers_sum_score' =>  count($papers_score_score) > 0 ?array_sum($papers_score_score) : 0 ,
                'sum_score'    =>  (float)$sum_score ,
                'is_over'      =>  $is_over
            ];
        }
        return response()->json(['code' => 200 , 'msg' => '操作成功' , 'data' => $array]);
    }
    
    /*
     * @param  description   收藏/取消收藏试题接口
     * @param author    dzj
     * @param ctime     2020-07-08
     * return string
     */
    public function doCollectQuestion(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        $papers_id    = isset(self::$accept_data['papers_id']) && self::$accept_data['papers_id'] > 0 ? self::$accept_data['papers_id'] : 0;              //获取试卷id
        $exam_id      = isset(self::$accept_data['exam_id']) && self::$accept_data['exam_id'] > 0 ? self::$accept_data['exam_id'] : 0;                    //获取试题id
        $type         = isset(self::$accept_data['type']) && self::$accept_data['type'] > 0 ? self::$accept_data['type'] : 0;                             //获取类型
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //判断试卷的id是否传递合法
        if(!$papers_id || $papers_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '试卷id不合法']);
        }
        
        //判断试题的id是否传递合法
        if(!$exam_id || $exam_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '试题id不合法']);
        }
        
        //判断类型是否传递
        if($type <= 0 || !in_array($type , [1,2,3])){
            return response()->json(['code' => 202 , 'msg' => '类型不合法']);
        }
        
        //开启事务
        DB::beginTransaction();
        
        //收藏试题操作
        //$is_collect =  StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('exam_id' , $exam_id)->where('type' , $type)->first();
        $is_collect =  StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('exam_id' , $exam_id)->first();
        if($is_collect && !empty($is_collect)){
            if($is_collect['status'] == 1){
                //$res = StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('exam_id' , $exam_id)->where('type' , $type)->update(['status' => 2 , 'update_at' => date('Y-m-d H:i:s')]);
                $res = StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('exam_id' , $exam_id)->update(['status' => 2 , 'update_at' => date('Y-m-d H:i:s')]);
                if($res && !empty($res)){
                    //事务提交
                    DB::commit();
                    return response()->json(['code' => 200 , 'msg' => '取消收藏成功']);
                } else {
                    //事务回滚
                    DB::rollBack();
                    return response()->json(['code' => 203 , 'msg' => '取消收藏失败']);
                }
            } else {
                //$res = StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('exam_id' , $exam_id)->where('type' , $type)->update(['status' => 1 , 'update_at' => date('Y-m-d H:i:s')]);
                $res = StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('exam_id' , $exam_id)->update(['status' => 1 , 'update_at' => date('Y-m-d H:i:s')]);
                if($res && !empty($res)){
                    //事务提交
                    DB::commit();
                    return response()->json(['code' => 200 , 'msg' => '收藏成功']);
                } else {
                    //事务回滚
                    DB::rollBack();
                    return response()->json(['code' => 203 , 'msg' => '收藏失败']);
                }
            }
        } else {
            //收藏试题
            $collect_id = StudentCollectQuestion::insertGetId([
                'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                'bank_id'      =>   $bank_id ,
                'subject_id'   =>   $subject_id ,
                'papers_id'    =>   $papers_id ,
                'exam_id'      =>   $exam_id ,
                'type'         =>   $type ,
                'status'       =>   1 ,
                'create_at'    =>   date('Y-m-d H:i:s')
            ]);
            
            //判断是否收藏成功
            if($collect_id && $collect_id > 0){
                //事务提交
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '收藏成功']);
            } else {
                //事务回滚
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '收藏失败']);
            }
        }
    }
    
    /*
     * @param  description   做题接口
     * @param author    dzj
     * @param ctime     2020-07-09
     * return string
     */
    public function doBankMakeExam(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        $chapter_id   = isset(self::$accept_data['chapter_id']) && self::$accept_data['chapter_id'] > 0 ? self::$accept_data['chapter_id'] : 0;           //获取章id
        $joint_id     = isset(self::$accept_data['joint_id']) && self::$accept_data['joint_id'] > 0 ? self::$accept_data['joint_id'] : 0;                 //获取节id
        $papers_id    = isset(self::$accept_data['papers_id']) && self::$accept_data['papers_id'] > 0 ? self::$accept_data['papers_id'] : 0;              //获取试卷id
        $type         = isset(self::$accept_data['type']) && self::$accept_data['type'] > 0 ? self::$accept_data['type'] : 0;                             //获取类型
        $answer_time  = isset(self::$accept_data['answer_time']) && !empty(self::$accept_data['answer_time']) ? self::$accept_data['answer_time'] : '';   //答题时间
        $exam_id      = isset(self::$accept_data['exam_id']) && !empty(self::$accept_data['exam_id']) ? self::$accept_data['exam_id'] : 0;                //试题id
        $myanswer     = isset(self::$accept_data['myanswer']) && !empty(self::$accept_data['myanswer']) ? self::$accept_data['myanswer'] : '';            //我的答案
        
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //判断试卷的id是否传递合法
        if(!$papers_id || $papers_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '试卷id不合法']);
        }
        
        //判断试题的id是否传递合法
        if(!$exam_id || $exam_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '试题id不合法']);
        }
        
        //判断学员答案是否为空
        if(!$myanswer || empty($myanswer)){
            return response()->json(['code' => 202 , 'msg' => '学员答案为空']);
        }
        
        //判断类型是否传递
        if($type <= 0 || !in_array($type , [1,2,3])){
            return response()->json(['code' => 202 , 'msg' => '类型不合法']);
        }
        
        //判断是否为模拟真题
        if($type == 3){
            //通过试卷id获取试卷详情
            $papers_info = Papers::where("id" , $papers_id)->first();
        } else if($type == 1){
            //判断章id是否合法
            if(!$chapter_id || $chapter_id <= 0){
                return response()->json(['code' => 202 , 'msg' => '章id不合法']);
            }
        }
        
        //试题总得分
        $sum_score = [];
        
        //开启事务
        DB::beginTransaction();
        
        //根据试题的id获取试题信息
        $exam_info = Exam::where("id" , $exam_id)->first();

        //判断学员的答案是否和正确答案相同
        if(stringSort(trim($exam_info['answer'])) != stringSort(trim($myanswer))) {
            $is_right = 2;
        } else {
            $is_right = 1;
        }
        
        //判断此学员是否做过题
        $is_make_exam =  StudentDoTitle::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $papers_id)->where('exam_id' , $exam_id)->where('type' , $type)->first();
        if($is_make_exam && !empty($is_make_exam)){
            //判断是否答过此题
            if($is_make_exam['is_right'] > 0){
                return response()->json(['code' => 209 , 'msg' => '您已答过此题']);
            }
            
            //更新试题状态信息
            $rs = StudentDoTitle::where(['student_id' => self::$accept_data['user_info']['user_id'] , 'bank_id' => $bank_id , 'subject_id' => $subject_id , 'papers_id' => $papers_id , 'exam_id' => $exam_id , 'type' => $type])->update(['answer' => $myanswer , 'is_right' => $is_right , 'update_at' => date('Y-m-d H:i:s')]);
            if($rs && !empty($rs)){
                //判断学员答题的数量
                $count             = StudentDoTitle::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $papers_id)->where('type' , $type)->where('is_right' , 0)->count();
                //判断学员是否答到最后一道试题了
                if($count <= 0){
                    StudentPapers::where('id' , $papers_id)->update(['answer_time' => $answer_time , 'is_over' => 1 , 'update_at' => date('Y-m-d H:i:s')]);
                }
                //更改试题中的状态
                StudentDoTitle::where(['student_id' => self::$accept_data['user_info']['user_id'] , 'bank_id' => $bank_id , 'subject_id' => $subject_id , 'exam_id' => $exam_id])->update(['answer' => $myanswer , 'is_right' => $is_right , 'update_at' => date('Y-m-d H:i:s')]);
                //事务回滚
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '答题成功']);
            } else {
                //事务回滚
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '答题失败']);
            }
        } else {
            //答题试题
            $exam_id = StudentDoTitle::insertGetId([
                'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                'bank_id'      =>   $bank_id ,
                'subject_id'   =>   $subject_id ,
                'exam_id'      =>   $exam_id ,
                'papers_id'    =>   $papers_id ,
                'answer'       =>   $myanswer ,
                'is_right'     =>   $is_right ,
                'type'         =>   3 ,
                'create_at'    =>   date('Y-m-d H:i:s') ,
                'update_at'    =>   date('Y-m-d H:i:s')
            ]);
            
            //判断是否是试卷答题
            if($type == 3){
                //判断学员答题的数量
                $count             = StudentDoTitle::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $papers_id)->where('type' , 3)->where('is_right' , '>' , 0)->count();
                $papers_exam_count = PapersExam::where("papers_id" , $papers_id)->where("subject_id" , $subject_id)->where("is_del" , 0)->whereIn("type" ,[1,2,3,4])->count();
                //判断学员是否答到最后一道试题了
                if($count >= $papers_exam_count){
                    //获取此学员所有答过的题列表
                    $exam_list = StudentDoTitle::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $papers_id)->where('type' , 3)->where('is_right' , '>' , 0)->get()->toArray();
                    foreach($exam_list as $k=>$v){
                        //根据试题的id获取试题信息
                        $examinfo = Exam::where("id" , $v['exam_id'])->first();
                        
                        //总得分
                        if($v['is_right'] == 1){
                            //单选题
                            if($examinfo['type'] == 1){
                                $score = $papers_info['signle_score'];
                            } elseif($examinfo['type'] == 2){
                                $score = $papers_info['more_score'];
                            } elseif($examinfo['type'] == 3){
                                $score = $papers_info['judge_score'];
                            } elseif($examinfo['type'] == 4){
                                $score = $papers_info['options_score'];
                            }
                            $sum_score[] = $score;
                        }
                    }
                    /*StudentPapers::insertGetId([
                        'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                        'bank_id'      =>   $bank_id ,
                        'subject_id'   =>   $subject_id ,
                        'papers_id'    =>   $papers_id ,
                        'answer_time'  =>   $answer_time ,
                        'answer_score' =>   count($sum_score) > 0 ? array_sum($sum_score) : 0,
                        'type'         =>   3 ,
                        'is_over'      =>   1 ,
                        'create_at'    =>   date('Y-m-d H:i:s') ,
                        'update_at'    =>   date('Y-m-d H:i:s')
                    ]);*/
                    $answer_score = count($sum_score) > 0 ? array_sum($sum_score) : 0;
                    //更新试卷的信息
                    StudentPapers::where(['student_id' => self::$accept_data['user_info']['user_id'] , 'bank_id' => $bank_id , 'subject_id' => $subject_id , 'papers_id' => $papers_id , 'type' => 3])->update(['answer_time' => $answer_time , 'answer_score' => $answer_score , 'is_over' => 1 , 'update_at' => date('Y-m-d H:i:s')]);
                } else {
                    //判断学员试卷表中是否存在此试卷
                    $student_paperts_count = StudentPapers::where('student_id' , self::$accept_data['user_info']['user_id'])->where('bank_id' , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $papers_id)->where('type' , 3)->count();
                    if($student_paperts_count <= 0){
                        //插入试卷
                        StudentPapers::insertGetId([
                            'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                            'bank_id'      =>   $bank_id ,
                            'subject_id'   =>   $subject_id ,
                            'papers_id'    =>   $papers_id ,
                            'type'         =>   3 ,
                            'create_at'    =>   date('Y-m-d H:i:s') ,
                            'update_at'    =>   date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
            
            //判断是否添加成功
            if($exam_id && $exam_id > 0){
                //更改试题中的状态
                StudentDoTitle::where(['student_id' => self::$accept_data['user_info']['user_id'] , 'bank_id' => $bank_id , 'subject_id' => $subject_id , 'exam_id' => $exam_id])->update(['answer' => $myanswer , 'is_right' => $is_right , 'update_at' => date('Y-m-d H:i:s')]);
                //事务回滚
                DB::commit();
                return response()->json(['code' => 200 , 'msg' => '答题成功']);
            } else {
                //事务回滚
                DB::rollBack();
                return response()->json(['code' => 203 , 'msg' => '答题失败']);
            }
        }
    }
    
    /*
     * @param  description   我的收藏/错题本/做题记录数量接口
     * @param author    dzj
     * @param ctime     2020-07-09
     * return string
     */
    public function getCollectErrorExamCount(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //我的收藏
        //$collect_count    = StudentCollectQuestion::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('status' , 1)->count();
        $collect_count = StudentCollectQuestion::select(DB::raw("any_value(exam_id) as exam_id"))->where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('status' , 1)->groupBy('exam_id')->get()->count();
        
        //错题本
        //$error_count   = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('is_right' , 2)->count();
        $error_count   = StudentDoTitle::select(DB::raw("any_value(exam_id) as exam_id"))->where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('is_right' , 2)->where('answer' , '!=' , '')->groupBy('exam_id')->get()->count();
        
        //做题记录
        $exam_count    = StudentPapers::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->whereIn('type' , [1,2,3])->count();
        
        //返回数据信息
        return response()->json(['code' => 200 , 'msg' => '返回数据成功' , 'data' => ['collect_count' => $collect_count , 'error_count' => $error_count , 'exam_count' => $exam_count]]);
    }
    
    /*
     * @param  description   我的收藏试题列表接口
     * @param author    dzj
     * @param ctime     2020-07-09
     * return string
     */
    public function getMyCollectExamList(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //新数组赋值
        $exam_array = [];
        
        //题型数组
        $exam_type_arr = [1=>'单选题',2=>'多选题',3=>'判断题',4=>'不定项',5=>'填空题',6=>'简答题'];
        
        //我的收藏列表
        //$collect_list = StudentCollectQuestion::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('status' , 1)->get();
        $collect_list = StudentCollectQuestion::select(DB::raw("any_value(papers_id) as papers_id , any_value(type) as type , any_value(exam_id) as exam_id"))->where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('status' , 1)->groupBy('exam_id')->get();
        if($collect_list && !empty($collect_list)){
            $collect_list = $collect_list->toArray();
            foreach($collect_list as $k=>$v){
                //根据试题的id获取试题详情
                $exam_info = Exam::where('id' , $v['exam_id'])->first();

                //单选题,多选题,不定项
                if(in_array($exam_info['type'] , [1,2,4])){
                    //根据试题的id获取选项
                    $option_info = ExamOption::where("exam_id" , $v['exam_id'])->first();

                    //选项转化
                    $option_content = json_decode($option_info['option_content'] , true);
                    
                    //获取试题类型
                    $exam_type_name = $exam_type_arr[$exam_info['type']];
                } else {
                    $option_content = [];
                    $exam_type_name = $exam_info['type'] == 3 ? $exam_type_arr[$exam_info['type']] : "";
                }
                
                //根据条件获取此学生此题是否答了
                $info = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $v['papers_id'])->where('exam_id' , $v['exam_id'])->where('type' , $v['type'])->first();

                //试题随机展示
                $exam_array[$exam_info['type']][] = [
                    'papers_id'           =>  $v['papers_id'] ,
                    'exam_id'             =>  $v['exam_id'] ,
                    'exam_name'           =>  $exam_info['exam_content'] ,
                    'exam_type_name'      =>  $exam_type_name ,
                    'text_analysis'       =>  $exam_info['text_analysis'] ,
                    'correct_answer'      =>  trim($exam_info['answer']) ,
                    'option_list'         =>  $option_content ,
                    'my_answer'           =>  $info && !empty($info) && !empty($info['answer']) ? $info['answer'] : '' ,
                    'is_right'            =>  $info && !empty($info) ? $info['is_right'] : 0 ,
                    'is_collect'          =>  1 ,
                    'type'                =>  $v['type']
                ];
            }
            return response()->json(['code' => 200 , 'msg' => '获取收藏列表成功' , 'data' => $exam_array]);
        } else {
            return response()->json(['code' => 203 , 'msg' => '暂无收藏的试题']);
        }
    }
    
    /*
     * @param  description   错题本列表接口
     * @param author    dzj
     * @param ctime     2020-07-09
     * return string
     */
    public function getMyErrorExamList(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //新数组赋值
        $exam_array = [];
        
        //题型数组
        $exam_type_arr = [1=>'单选题',2=>'多选题',3=>'判断题',4=>'不定项',5=>'填空题',6=>'简答题'];
        
        //错题本列表
        $student_error_list = StudentDoTitle::select(DB::raw("any_value(papers_id) as papers_id , any_value(type) as type , any_value(exam_id) as exam_id"))->where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('is_right' , 2)->where('answer' , '!=' , '')->groupBy('exam_id')->get();
        if($student_error_list && !empty($student_error_list)){
            $student_error_list = $student_error_list->toArray();
            foreach($student_error_list as $k=>$v){
                //根据试题的id获取试题详情
                $exam_info = Exam::where('id' , $v['exam_id'])->first();

                //单选题,多选题,不定项
                if(in_array($exam_info['type'] , [1,2,4])){
                    //根据试题的id获取选项
                    $option_info = ExamOption::where("exam_id" , $v['exam_id'])->first();

                    //选项转化
                    $option_content = json_decode($option_info['option_content'] , true);
                    
                    //获取试题类型
                    $exam_type_name = $exam_type_arr[$exam_info['type']];
                } else {
                    $option_content = [];
                    $exam_type_name = $exam_info['type'] == 3 ? $exam_type_arr[$exam_info['type']] : "";
                }
                
                //判断学员是否收藏此题
                //$is_collect =  StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $v['papers_id'])->where('exam_id' , $v['exam_id'])->where('type' , $v['type'])->where('status' , 1)->count();
                $is_collect =  StudentCollectQuestion::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('exam_id' , $v['exam_id'])->where('status' , 1)->count();
                
                //根据条件获取此学生此题是否答了
                $info = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $v['papers_id'])->where('exam_id' , $v['exam_id'])->where('type' , $v['type'])->first();

                //试题随机展示
                $exam_array[$exam_info['type']][] = [
                    'papers_id'           =>  $v['papers_id'] ,
                    'exam_id'             =>  $v['exam_id'] ,
                    'exam_name'           =>  $exam_info['exam_content'] ,
                    'exam_type_name'      =>  $exam_type_name ,
                    'text_analysis'       =>  $exam_info['text_analysis'] ,
                    'correct_answer'      =>  trim($exam_info['answer']) ,
                    'option_list'         =>  $option_content ,
                    'my_answer'           =>  $info && !empty($info) && !empty($info['answer']) ? $info['answer'] : '' ,
                    'is_right'            =>  $info && !empty($info) ? $info['is_right'] : 0 ,
                    'is_collect'          =>  $is_collect ? 1 : 0 ,
                    'type'                =>  $v['type']
                ];
            }
            return response()->json(['code' => 200 , 'msg' => '获取错题本列表成功' , 'data' => $exam_array]);
        } else {
            return response()->json(['code' => 203 , 'msg' => '暂无收藏的试题']);
        }
    }
    
    /*
     * @param  description   做题记录列表接口
     * @param author    dzj
     * @param ctime     2020-07-09
     * return string
     */
    public function getMyMakeExamList(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        $type         = isset(self::$accept_data['type']) && self::$accept_data['type'] > 0 ? self::$accept_data['type'] : 1;                             //获取类型
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //判断类型是否传递
        if($type <= 0 || !in_array($type , [1,2,3])){
            return response()->json(['code' => 202 , 'msg' => '类型不合法']);
        }
        
        //新数组赋值
        $new_array = [];

        //获取学员的做题记录列表
        $make_exam_list = StudentPapers::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('type' , $type)->orderBy('update_at' , 'DESC')->get()->toArray();
        
        //判断信息是否为空
        if($make_exam_list && !empty($make_exam_list)){
            foreach($make_exam_list as $k=>$v){
                //试卷id
                $papers_id = $type == 3 ? $v['papers_id'] : $v['id'];
                
                //判断是否有答过题的数量了
                $is_right_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('type' , $type)->count();
                if($is_right_count && $is_right_count > 0){
                   //判断是否是章节
                    if($type == 1){
                        //判断节是否存在
                        if($v['joint_id'] > 0){
                            //通过节的id获取节的名称
                            $name = Chapters::where('id' , $v['joint_id'])->where('type' , 1)->value('name');
                        } else {
                            //通过章的id获取章的名称
                            $name = Chapters::where('id' , $v['chapter_id'])->where('type' , 0)->value('name');
                        }
                    } else if($type == 2){
                        //获取科目名称
                        $name = QuestionSubject::where('id' , $subject_id)->value('subject_name');
                    } else if($type == 3){
                        //根据试卷的id获取试卷名称
                        $name = Papers::where("id" , $papers_id)->value('papers_name');
                    }

                    //判断如果学员没有做完题则展示最近做题的时间
                    if($v['is_over'] == 1){
                        //获取学员作对的道数
                        $collect_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('type' , $type)->where('is_right' , 1)->count();
                        //获取学员做错的道数
                        $error_count   = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('type' , $type)->where('is_right' , 2)->count();

                        $make_date   =   date('Y-m-d' ,strtotime($v['update_at']));
                        $make_time   =   date('H:i:s' ,strtotime($v['update_at']));
                        $is_over     =   1;
                    } else {
                        $info = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('type' , $type)->where('is_right' , '>' , 0)->orderBy('update_at' , 'DESC')->first();
                        if($info && !empty($info)){
                            $make_date   =   date('Y-m-d' ,strtotime($info['update_at']));
                            $make_time   =   date('H:i:s' ,strtotime($info['update_at']));
                        } else {
                            $make_date   =   "";
                            $make_time   =   "";
                        }
                        $is_over       = 0;
                        $collect_count = 0;
                        $error_count   = 0;
                    }

                    //新数组赋值
                    $new_array[] = [
                        'papers_id'     =>  $papers_id ,
                        'chapter_id'    =>  $v['chapter_id'] ,
                        'joint_id'      =>  $v['joint_id'] ,
                        'name'          =>  $name ,
                        'make_date'     =>  $make_date ,
                        'make_time'     =>  $make_time ,
                        'answer_score'  =>  !empty($v['answer_score']) && $v['answer_score'] > 0 ? $v['answer_score'] : 0 ,
                        'collect_count' =>  $collect_count ,
                        'error_count'   =>  $error_count ,
                        'is_over'       =>  $is_over
                    ];
                }
            }
            return response()->json(['code' => 200 , 'msg' => '返回做题记录列表成功' , 'data' => $new_array]);
        } else {
            return response()->json(['code' => 203 , 'msg' => '暂无做题记录']);
        }
    }
    
    
    /*
     * @param  description   章节练习/快速做题/模拟真题最新做题接口
     * @param author    dzj
     * @param ctime     2020-08-19
     * return string
     */
    public function getNewMakeExamInfo(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        $data = [];
        
        //获取章节最新做题情况
        $zhangjie_info = StudentPapers::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('type' , 1)->where('is_over' , 0)->orderBy('update_at' , 'DESC')->first();
        if($zhangjie_info && !empty($zhangjie_info)){
            //通过章的id获取章的名称
            $name = Chapters::where('id' , $zhangjie_info['chapter_id'])->where('type' , 0)->value('name');
            //做题数量
            $make_exam_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $zhangjie_info['id'])->where('type' , 1)->where('is_right' , '>' , 0)->count();
            //总共多少道题
            $sum_exam_count  = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $zhangjie_info['id'])->where('type' , 1)->count();
            $data[] = ['name' => $name , 'make_exam_count' => $make_exam_count , 'sum_exam_count' => $sum_exam_count , 'chapter_id' => $zhangjie_info['chapter_id'] , 'joint_id' => $zhangjie_info['joint_id'] , 'type' => 1];
        }
        
        //获取快速做题最新做题情况
        $quckly_info = StudentPapers::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('type' , 2)->where('is_over' , 0)->orderBy('update_at' , 'DESC')->first();
        if($quckly_info && !empty($quckly_info)){
            //获取科目名称
            $name = QuestionSubject::where('id' , $subject_id)->value('subject_name');
            //做题数量
            $make_exam_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $quckly_info['id'])->where('type' , 2)->where('is_right' , '>' , 0)->count();
            //总共多少道题
            $sum_exam_count  = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $quckly_info['id'])->where('type' , 2)->count();
            $data[] = ['name' => $name , 'make_exam_count' => $make_exam_count , 'sum_exam_count' => $sum_exam_count , 'papers_id' => $quckly_info['id'] , 'type' => 2];
        }
        
        //模拟真题最新做题情况
        $moni_info = StudentPapers::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('type' , 3)->where('is_over' , 0)->orderBy('update_at' , 'DESC')->first();
        if($moni_info && !empty($moni_info)){
            //根据试卷的id获取试卷名称
            $name = Papers::where("id" , $moni_info['papers_id'])->value('papers_name');
            //做题数量
            $make_exam_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $moni_info['papers_id'])->where('type' , 3)->where('is_right' , '>' , 0)->count();
            //总共多少道题
            $sum_exam_count  = PapersExam::where("papers_id" , $moni_info['papers_id'])->where("subject_id" , $subject_id)->where("is_del" , 0)->whereIn("type" ,[1,2,3,4])->count();
            $data[] = ['name' => $name , 'make_exam_count' => $make_exam_count , 'sum_exam_count' => $sum_exam_count , 'papers_id' => $moni_info['papers_id'] , 'type' => 3];
        }
        
        //返回数据数组
        return response()->json(['code' => 200 , 'msg' => '返回数据成功' , 'data' => $data]);
    }
    
    /*
     * @param  description   做题记录详情接口
     * @param author    dzj
     * @param ctime     2020-07-09
     * return string
     */
    public function getMakeExamInfo(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        $chapter_id   = isset(self::$accept_data['chapter_id']) && self::$accept_data['chapter_id'] > 0 ? self::$accept_data['chapter_id'] : 0;           //获取章的id
        $joint_id     = isset(self::$accept_data['joint_id']) && self::$accept_data['joint_id'] > 0 ? self::$accept_data['joint_id'] : 0;                 //获取节的id
        $papers_id    = isset(self::$accept_data['papers_id']) && self::$accept_data['papers_id'] > 0 ? self::$accept_data['papers_id'] : 0;              //获取试卷的id
        $type         = isset(self::$accept_data['type']) && self::$accept_data['type'] > 0 ? self::$accept_data['type'] : 0;                             //获取类型(1代表章节练习2代表快速做题3代表模拟真题)
        $model        = isset(self::$accept_data['model']) && self::$accept_data['model'] > 0 ? self::$accept_data['model'] : 0;                          //获取模式
        
        //判断类型是否传递
        if($type <= 0 || !in_array($type , [1,2,3])){
            return response()->json(['code' => 202 , 'msg' => '类型不合法']);
        }

        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //题型数组
        $exam_type_arr = [1=>'单选题',2=>'多选题',3=>'判断题',4=>'不定项',5=>'填空题',6=>'简答题'];
        
        //判断是否为章节练习
        if($type == 1){
            //判断章的id是否传递合法
            if(!$chapter_id || $chapter_id <= 0){
                return response()->json(['code' => 202 , 'msg' => '章id不合法']);
            }
            
            //新数组赋值
            $exam_array = [];            

            //查询还未做完的题列表
            $exam_list = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('type' , 1)->where('is_right' , '>' , 0)->get();
            foreach($exam_list as $k=>$v){
                //根据试题的id获取试题详情
                $exam_info = Exam::where('id' , $v['exam_id'])->first();

                //单选题,多选题,不定项
                if(in_array($exam_info['type'] , [1,2,4])){
                    //根据试题的id获取选项
                    $option_info = ExamOption::where("exam_id" , $v['exam_id'])->first();

                    //选项转化
                    $option_content = json_decode($option_info['option_content'] , true);

                    //获取试题类型
                    $exam_type_name = $exam_type_arr[$exam_info['type']];
                } else {
                    $option_content = [];
                    $exam_type_name = $exam_info['type'] == 3 ? $exam_type_arr[$exam_info['type']] : "";
                }

                //判断学员是否收藏此题
                //$is_collect =  StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('exam_id' , $v['exam_id'])->where('type' , 1)->where('status' , 1)->count();
                $is_collect =  StudentCollectQuestion::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('exam_id' , $v['exam_id'])->where('status' , 1)->count();

                //试题随机展示
                $exam_array[$exam_info['type']][] = [
                    'exam_id'             =>  $v['exam_id'] ,
                    'exam_name'           =>  $exam_info['exam_content'] ,
                    'exam_type_name'      =>  $exam_type_name ,
                    'text_analysis'       =>  $exam_info['text_analysis'] ,
                    'correct_answer'      =>  trim($exam_info['answer']) ,
                    'option_list'         =>  $option_content ,
                    'my_answer'           =>  !empty($v['answer']) ? $v['answer'] : '' ,
                    'is_right'            =>  $v['is_right'] ,
                    'is_collect'          =>  $is_collect ? 1 : 0 ,
                    'type'                =>  1
                ];
            }
        } else if($type == 2){  //快速做题
            //新数组赋值
            $exam_array = [];            
            
            //查询还未做完的题列表
            $exam_list = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('type' , 2)->where('is_right' , '>' , 0)->get();
            foreach($exam_list as $k=>$v){
                //根据试题的id获取试题详情
                $exam_info = Exam::where('id' , $v['exam_id'])->first();

                //单选题,多选题,不定项
                if(in_array($exam_info['type'] , [1,2,4])){
                    //根据试题的id获取选项
                    $option_info = ExamOption::where("exam_id" , $v['exam_id'])->first();

                    //选项转化
                    $option_content = json_decode($option_info['option_content'] , true);

                    //获取试题类型
                    $exam_type_name = $exam_type_arr[$exam_info['type']];
                } else {
                    $option_content = [];
                    $exam_type_name = $exam_info['type'] == 3 ? $exam_type_arr[$exam_info['type']] : "";
                }

                //判断学员是否收藏此题
                //$is_collect =  StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('exam_id' , $v['exam_id'])->where('type' , 2)->where('status' , 1)->count();
                $is_collect =  StudentCollectQuestion::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('exam_id' , $v['exam_id'])->where('status' , 1)->count();
                
                //试题随机展示
                $exam_array[$exam_info['type']][] = [
                    'exam_id'             =>  $v['exam_id'] ,
                    'exam_name'           =>  $exam_info['exam_content'] ,
                    'exam_type_name'      =>  $exam_type_name ,
                    'text_analysis'       =>  $exam_info['text_analysis'] ,
                    'correct_answer'      =>  trim($exam_info['answer']) ,
                    'option_list'         =>  $option_content ,
                    'my_answer'           =>  !empty($v['answer']) ? $v['answer'] : '' ,
                    'is_right'            =>  $v['is_right'] ,
                    'is_collect'          =>  $is_collect ? 1 : 0 ,
                    'type'                =>  2
                ];
            }
        } else if($type == 3){  //模拟真题
            //新数组赋值
            $exam_array = [];
            
            //判断试卷的id是否合法
            if(!$papers_id || $papers_id <= 0){
                return response()->json(['code' => 202 , 'msg' => '试卷id不合法']);
            }
            
            //获取做过得试题
            $exam_list = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('type' , 3)->where('is_right' , '>' , 0)->get();
            
            foreach($exam_list as $k=>$v){
                //根据试题的id获取试题详情
                $exam_info = Exam::where('id' , $v['exam_id'])->first();

                //单选题,多选题,不定项
                if(in_array($exam_info['type'] , [1,2,4])){
                    //根据试题的id获取选项
                    $option_info = ExamOption::where("exam_id" , $v['exam_id'])->first();

                    //选项转化
                    $option_content = json_decode($option_info['option_content'] , true);
                    
                    //获取试题类型
                    $exam_type_name = $exam_type_arr[$exam_info['type']];
                } else {
                    $option_content = [];
                    $exam_type_name = $exam_info['type'] == 3 ? $exam_type_arr[$exam_info['type']] : "";
                }
                
                //判断学员是否收藏此题
                //$is_collect =  StudentCollectQuestion::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $papers_id)->where('exam_id' , $v['exam_id'])->where('type' , 3)->where('status' , 1)->count();
                $is_collect =  StudentCollectQuestion::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('exam_id' , $v['exam_id'])->where('status' , 1)->count();
                
                //根据条件获取此学生此题是否答了
                $info = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("papers_id" , $papers_id)->where("subject_id" , $subject_id)->where('exam_id' , $v['exam_id'])->where('type' , 3)->first();

                //试题随机展示
                $exam_array[$exam_info['type']][] = [
                    'exam_id'             =>  $v['exam_id'] ,
                    'exam_name'           =>  $exam_info['exam_content'] ,
                    'exam_type_name'      =>  $exam_type_name ,
                    'text_analysis'       =>  $exam_info['text_analysis'] ,
                    'correct_answer'      =>  trim($exam_info['answer']) ,
                    'option_list'         =>  $option_content ,
                    'my_answer'           =>  $info && !empty($info) && !empty($info['answer']) ? $info['answer'] : '' ,
                    'is_right'            =>  $info && !empty($info) ? $info['is_right'] : 0 ,
                    'is_collect'          =>  $is_collect ? 1 : 0 ,
                    'type'                =>  3
                ];
            }
        }
        return response()->json(['code' => 200 , 'msg' => '操作成功' , 'data' => $exam_array]);
    }
    
    
    /*
     * @param  description   做题交卷接口
     * @param author    dzj
     * @param ctime     2020-07-09
     * return string
     */
    public function doHandInPapers(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
        $chapter_id   = isset(self::$accept_data['chapter_id']) && self::$accept_data['chapter_id'] > 0 ? self::$accept_data['chapter_id'] : 0;           //获取章的id
        $joint_id     = isset(self::$accept_data['joint_id']) && self::$accept_data['joint_id'] > 0 ? self::$accept_data['joint_id'] : 0;                 //获取节的id
        $papers_id    = isset(self::$accept_data['papers_id']) && self::$accept_data['papers_id'] > 0 ? self::$accept_data['papers_id'] : 0;              //获取试卷的id
        $type         = isset(self::$accept_data['type']) && self::$accept_data['type'] > 0 ? self::$accept_data['type'] : 0;                             //获取类型(1代表章节练习2代表快速做题3代表模拟真题)
       
        //判断类型是否传递
        if($type <= 0 || !in_array($type , [1,2,3])){
            return response()->json(['code' => 202 , 'msg' => '类型不合法']);
        }

        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //检验用户是否有做题权限
        $iurisdiction = self::verifyUserExamJurisdiction($bank_id);
        if($iurisdiction['code'] == 209){
            return response()->json(['code' => 209 , 'msg' => $iurisdiction['msg']]);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //判断试卷的id是否传递合法
        if(!$papers_id || $papers_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '试卷id不合法']);
        }
        
        $answer_time  = isset(self::$accept_data['answer_time']) && !empty(self::$accept_data['answer_time']) ? self::$accept_data['answer_time'] : '';   //答题时间
        if(!$answer_time || empty($answer_time)){
            return response()->json(['code' => 201 , 'msg' => '耗时时间为空']);
        }
        
        //开启事务
        DB::beginTransaction();
        
        //判断是否为章节练习
        if($type == 1){
            //判断章的id是否传递合法
            if(!$chapter_id || $chapter_id <= 0){
                return response()->json(['code' => 202 , 'msg' => '章id不合法']);
            }
            
            //新数组赋值
            $exam_array = [];            

            //查询还未做完的题列表
            $exam_list = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('type' , 1)->where('is_right' , 0)->get()->toArray();
            if($exam_list && !empty($exam_list)){
                //将没有做得题得状态进行更新
                $no_title_id = array_column($exam_list , 'id');
                //批量更新未做得试题
                $rs = StudentDoTitle::whereIn("id" , $no_title_id)->update(['update_at' => date('Y-m-d H:i:s') , 'is_right' => 2 , 'answer' => '']);
                if($rs && !empty($rs)){
                    StudentPapers::where('id' , $papers_id)->update(['answer_time' => $answer_time , 'is_over' => 1 , 'update_at' => date('Y-m-d H:i:s')]);
                    //更改试题中的状态
                    StudentDoTitle::where(['student_id' => self::$accept_data['user_info']['user_id'] , 'bank_id' => $bank_id , 'subject_id' => $subject_id])->whereIn("id" , $no_title_id)->update(['answer' => '' , 'is_right' => 2 , 'update_at' => date('Y-m-d H:i:s')]);
                    //事务回滚
                    DB::commit();
                    return response()->json(['code' => 200 , 'msg' => '交卷成功' , 'data' => ['answer_time' => $answer_time , 'answer_score' => 0]]);
                } else {
                    //事务回滚
                    DB::rollBack();
                    return response()->json(['code' => 203 , 'msg' => '交卷失败']);
                }
            } else {
                //判断学员是否做过此试卷
                $info =  StudentPapers::where("id" , $papers_id)->first();
                return response()->json(['code' => 200 , 'msg' => '交卷成功' , 'data' => ['answer_time' => $info['answer_time'] , 'answer_score' => 0]]);
            }
        } else if($type == 2){  //快速做题
            //新数组赋值
            $exam_array = [];            

            //查询还未做完的题列表
            $exam_list = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('papers_id' , $papers_id)->where('type' , 2)->where('is_right' , 0)->get()->toArray();
            if($exam_list && !empty($exam_list)){
                //将没有做得题得状态进行更新
                $no_title_id = array_column($exam_list , 'id');
                //批量更新未做得试题
                $rs = StudentDoTitle::whereIn("id" , $no_title_id)->update(['update_at' => date('Y-m-d H:i:s') , 'is_right' => 2 , 'answer' => '']);
                if($rs && !empty($rs)){
                    StudentPapers::where('id' , $papers_id)->update(['answer_time' => $answer_time , 'is_over' => 1 , 'update_at' => date('Y-m-d H:i:s')]);
                    //更改试题中的状态
                    StudentDoTitle::where(['student_id' => self::$accept_data['user_info']['user_id'] , 'bank_id' => $bank_id , 'subject_id' => $subject_id])->whereIn("id" , $no_title_id)->update(['answer' => '' , 'is_right' => 2 , 'update_at' => date('Y-m-d H:i:s')]);
                    //事务回滚
                    DB::commit();
                    return response()->json(['code' => 200 , 'msg' => '交卷成功' , 'data' => ['answer_time' => $answer_time , 'answer_score' => 0]]);
                } else {
                    //事务回滚
                    DB::rollBack();
                    return response()->json(['code' => 203 , 'msg' => '交卷失败']);
                }
            } else {
                //判断学员是否做过此试卷
                $info =  StudentPapers::where("id" , $papers_id)->first();
                return response()->json(['code' => 200 , 'msg' => '交卷成功' , 'data' => ['answer_time' => $info['answer_time'] , 'answer_score' => 0]]);
            }
        } else if($type == 3){  //模拟真题
            //新数组赋值
            $sum_score = [];            
            
            //通过试卷id获取试卷详情
            $papers_info = Papers::where("id" , $papers_id)->first();
            
            //判断是否提交
            $info = StudentPapers::where('papers_id' , $papers_id)->where('type' , 3)->where('is_over' , 1)->first();
            if($info && !empty($info)){
                return response()->json(['code' => 200 , 'msg' => '交卷成功' , 'data' => ['answer_time' => $info['answer_time'] , 'answer_score' => (double)$info['answer_score']]]);
            } else {
                //获取此学员所有答过的题列表
                $exam_list = StudentDoTitle::where('student_id' , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $papers_id)->where('type' , 3)->where('is_right' , '>' , 0)->get()->toArray();
                if($exam_list && !empty($exam_list)){
                    foreach($exam_list as $k=>$v){
                        //根据试题的id获取试题信息
                        $examinfo = Exam::where("id" , $v['exam_id'])->first();

                        //总得分
                        if($v['is_right'] == 1){
                            //单选题
                            if($examinfo['type'] == 1){
                                $score = $papers_info['signle_score'];
                            } elseif($examinfo['type'] == 2){
                                $score = $papers_info['more_score'];
                            } elseif($examinfo['type'] == 3){
                                $score = $papers_info['judge_score'];
                            } elseif($examinfo['type'] == 4){
                                $score = $papers_info['options_score'];
                            }
                            $sum_score[] = $score;
                        }
                    }

                    $sum_scores = count($sum_score) > 0 ? array_sum($sum_score) : 0;

                    //判断是否插入成功
                    /*$id = StudentPapers::insertGetId([
                        'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                        'bank_id'      =>   $bank_id ,
                        'subject_id'   =>   $subject_id ,
                        'papers_id'    =>   $papers_id ,
                        'answer_time'  =>   $answer_time ,
                        'answer_score' =>   $sum_scores,
                        'type'         =>   3 ,
                        'is_over'      =>   1 ,
                        'create_at'    =>   date('Y-m-d H:i:s') ,
                        'update_at'    =>   date('Y-m-d H:i:s')
                    ]);*/
                    
                    //判断学员试卷表中是否存在此试卷
                    $student_paperts_count = StudentPapers::where('student_id' , self::$accept_data['user_info']['user_id'])->where('bank_id' , $bank_id)->where("subject_id" , $subject_id)->where("papers_id" , $papers_id)->where('type' , 3)->count();
                    if($student_paperts_count <= 0){
                        //插入试卷
                        $id = StudentPapers::insertGetId([
                            'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                            'bank_id'      =>   $bank_id ,
                            'subject_id'   =>   $subject_id ,
                            'papers_id'    =>   $papers_id ,
                            'answer_time'  =>   $answer_time ,
                            'answer_score' =>   $sum_scores,
                            'type'         =>   3 ,
                            'is_over'      =>   1 ,
                            'create_at'    =>   date('Y-m-d H:i:s') ,
                            'update_at'    =>   date('Y-m-d H:i:s')
                        ]);
                    } else {
                        //更新试卷的信息
                        $id = StudentPapers::where(['student_id' => self::$accept_data['user_info']['user_id'] , 'bank_id' => $bank_id , 'subject_id' => $subject_id , 'papers_id' => $papers_id , 'type' => 3])->update(['answer_time' => $answer_time , 'answer_score' => $sum_scores , 'is_over' => 1 , 'update_at' => date('Y-m-d H:i:s')]);
                    }
                    
                    //判断是否提交试卷成功
                    if($id && !empty($id)){
                        //事务回滚
                        DB::commit();
                        return response()->json(['code' => 200 , 'msg' => '交卷成功' , 'data' => ['answer_time' => $answer_time , 'answer_score' => $sum_scores]]);
                    } else {
                        //事务回滚
                        DB::commit();
                        return response()->json(['code' => 203 , 'msg' => '交卷失败']);
                    }
                }
            }
        }
    }
    
    /*
     * @param  description   我的题库
     * @param author    dzj
     * @param ctime     2020-07-13
     * return string
     */
    public function getMyBankList(){
        $pagesize = isset(self::$accept_data['pagesize']) && self::$accept_data['pagesize'] > 0 ? self::$accept_data['pagesize'] : 15;
        $page     = isset(self::$accept_data['page']) && self::$accept_data['page'] > 0 ? self::$accept_data['page'] : 1;
        $type     = isset(self::$accept_data['type']) && self::$accept_data['type'] > 0 ? self::$accept_data['type'] : 1;                             //获取类型(1代表已做题库2代表可做题库)
        
        //起始位置
        $offset   = ($page - 1) * $pagesize;
        
        //判断类型是否传递
        if($type <= 0 || !in_array($type , [1,2])){
            return response()->json(['code' => 202 , 'msg' => '类型不合法']);
        }
        
        $arr = [];
        
        //已做题库数量
        $bank_count = DB::table('ld_student_papers')->selectRaw("any_value(ld_student_papers.bank_id) as bank_id")->where('student_id' , self::$accept_data['user_info']['user_id'])->groupBy('bank_id')->get()->count();
        
        //可做题库数量
        $bank_list11 = DB::table('ld_question_bank')->selectRaw("any_value(ld_question_bank.id) as bank_id")->join("ld_course" , function($join){
            $join->on('ld_course.parent_id', '=', 'ld_question_bank.parent_id');
        })->join("ld_order" , function($join){
            $join->on('ld_course.id', '=', 'ld_order.class_id');
        })->where('ld_order.student_id' , self::$accept_data['user_info']['user_id'])->where('ld_question_bank.is_del' , 0)->where('ld_question_bank.is_open' , 0)->where('ld_course.is_del' , 0)->where('ld_order.status' , 2)->where('ld_order.nature' , 0)->groupBy('ld_question_bank.id')->get()->count();

        //授权题库
        $bank_list12 = DB::table('ld_question_bank')->selectRaw("any_value(ld_question_bank.id) as bank_id")->join("ld_course_ref_bank" , function($join){
            $join->on('ld_course_ref_bank.bank_id', '=', 'ld_question_bank.id');
        })->join("ld_course_school" , function($join){
            $join->on('ld_course_school.parent_id', '=', 'ld_question_bank.parent_id');
        })->join("ld_order" , function($join){
            $join->on('ld_course_school.id', '=', 'ld_order.class_id');
        })->where('ld_order.student_id' , self::$accept_data['user_info']['user_id'])->where('ld_question_bank.is_del' , 0)->where('ld_question_bank.is_open' , 0)->where('ld_course_school.is_del' , 0)->where('ld_order.status' , 2)->where('ld_order.nature' , 1)->groupBy('ld_question_bank.id')->get()->count();
        
        //可做题库数量
        $ke_bank_count = $bank_list11 + $bank_list12;
        
        //已做题库数量
        if($type == 1){
            if($bank_count && $bank_count > 0){
               $bank_list = DB::table('ld_student_papers')->selectRaw("any_value(ld_student_papers.bank_id) as bank_id")->where('student_id' , self::$accept_data['user_info']['user_id'])->groupBy('bank_id')->offset($offset)->limit($pagesize)->get()->toArray();
               foreach($bank_list as $k=>$v){
                   //题库名称
                   $bank_info = Bank::where('id' , $v->bank_id)->first();
                   
                   //获取科目id
                   $subject_list= QuestionSubject::select('id as subject_id' , 'subject_name')->where('bank_id' , $v->bank_id)->where('subject_name' , '!=' , "")->where('is_del' , 0)->get();
                   
                   $arr[] =[
                       'bank_id'     =>  $v->bank_id ,
                       'bank_name'   =>  $bank_info['topic_name'] , 
                       'subject_list'=>  $subject_list
                   ];
               }
            }
        } else {  //可做题库数量
            if($ke_bank_count && $ke_bank_count > 0){
                $bank_list1 = DB::table('ld_question_bank')->selectRaw("any_value(ld_question_bank.id) as bank_id")->join("ld_course" , function($join){
                    $join->on('ld_course.parent_id', '=', 'ld_question_bank.parent_id');
                })->join("ld_order" , function($join){
                    $join->on('ld_course.id', '=', 'ld_order.class_id');
                })->where('ld_order.student_id' , self::$accept_data['user_info']['user_id'])->where('ld_question_bank.is_del' , 0)->where('ld_question_bank.is_open' , 0)->where('ld_course.is_del' , 0)->where('ld_order.status' , 2)->where('ld_order.nature' , 0)->groupBy('ld_question_bank.id')->get()->toArray();
               
                //授权题库
                $bank_list2 = DB::table('ld_question_bank')->selectRaw("any_value(ld_question_bank.id) as bank_id")->join("ld_course_ref_bank" , function($join){
                    $join->on('ld_course_ref_bank.bank_id', '=', 'ld_question_bank.id');
                })->join("ld_course_school" , function($join){
                    $join->on('ld_course_school.parent_id', '=', 'ld_question_bank.parent_id');
                })->join("ld_order" , function($join){
                    $join->on('ld_course_school.id', '=', 'ld_order.class_id');
                })->where('ld_order.student_id' , self::$accept_data['user_info']['user_id'])->where('ld_question_bank.is_del' , 0)->where('ld_question_bank.is_open' , 0)->where('ld_course_school.is_del' , 0)->where('ld_order.status' , 2)->where('ld_order.nature' , 1)->groupBy('ld_question_bank.id')->get()->toArray();
                
                //获取总条数
                $bank_list = array_merge((array)$bank_list1 , (array)$bank_list2);
                if($bank_list && !empty($bank_list)){
                    foreach($bank_list as $k=>$v){
                        //题库名称
                        $bank_info = Bank::where('id' , $v->bank_id)->first();

                        //获取科目id
                        $subject_list= QuestionSubject::select('id as subject_id' , 'subject_name')->where('bank_id' , $v->bank_id)->where('subject_name' , '!=' , "")->where('is_del' , 0)->get();

                        $arr[] =[
                            'bank_id'     =>  $v->bank_id ,
                            'bank_name'   =>  $bank_info['topic_name'] , 
                            'subject_list'=>  $subject_list
                        ];
                    }
                    $arr   = array_slice($arr,$offset,$pagesize);
                }
            }
        }
        return response()->json(['code' => 200 , 'msg' => '获取信息成功' , 'data' => ['bank_list' => $arr , 'yi_bank_count' => $bank_count , 'ke_bank_count' => $ke_bank_count , 'page' => (int)$page , 'pagesize' => (int)$pagesize]]);
    }
}

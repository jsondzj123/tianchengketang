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
use App\Models\Coures;
use App\Models\Order;
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
            //题库数组赋值
            $bank_array = [];
                
            //获取全部题库的列表
            $bank_list = Bank::where("is_del" , 0)->where("is_open" , 0)->orderByDesc('id')->get();
            if($bank_list && !empty($bank_list)){
                $bank_list = $bank_list->toArray();
                foreach($bank_list as $k=>$v){
                    //判断科目的id是否为空
                    if($v['subject_id'] && !empty($v['subject_id'])){
                        //科目id数据格式转化
                        $subject_id   = explode(',' , $v['subject_id']);
                        
                        //根据科目的id获取列表数据
                        $subject_list = QuestionSubject::select('id as subject_id' , 'subject_name')->where('bank_id' , $v['id'])->whereIn('id' , $subject_id)->where('is_del' , 0)->get();
                    } else {
                        $subject_list = [];
                    }
                    
                    //新数组赋值
                    $bank_array[] = [
                        'bank_id'     =>   $v['id'] ,
                        'bank_name'   =>   $v['topic_name'] ,
                        'subject_list'=>   $subject_list
                    ];
                }
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
        $bank_info = Bank::where('id' , $bank_id)->where('is_del' , 0)->where('is_open' , 0)->first();
        
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
        $exam_count = $exam_count > 100 ? 100 : $exam_count;
        
        //未做试题数量
        $no_exam_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where('bank_id' , $bank_id)->where('subject_id' , $subject_id)->where('chapter_id' , $chapter_id)->where('joint_id' , $joint_id)->where('type' , 1)->where('is_right' , 0)->count();
        $no_exam_count = $no_exam_count > 0 ? $no_exam_count : $exam_count;
        
        //错题数量
        $error_exam_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where('bank_id' , $bank_id)->where('subject_id' , $subject_id)->where('chapter_id' , $chapter_id)->where('joint_id' , $joint_id)->where('type' , 1)->where('is_right' , 2)->count();
                
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
        
        //题型数组
        $exam_type_arr = [1=>'单选题',2=>'多选题',3=>'判断题',4=>'不定项',5=>'填空题',6=>'简答题'];
        
        //判断是否为章节练习
        if($type == 1){
            //判断章的id是否传递合法
            if(!$chapter_id || $chapter_id <= 0){
                return response()->json(['code' => 202 , 'msg' => '章id不合法']);
            }

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
            
            //新数组赋值
            $exam_array = [];            
            
            //判断是否做完了随机生成的快速做题数量
            $rand_exam_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('is_right' , 0)->where('type' , 1)->count();
            if($rand_exam_count <= 0){
                //根据设置的条件筛选试题
                $exam_list = Exam::select("id")->where([['bank_id' , '=' , $bank_id] , ['subject_id' , '=' , $subject_id] , ['chapter_id' , '=' , $chapter_id] , ['joint_id' , '=' , $joint_id] , ['is_del' , '=' , 0] , ['is_publish' , '=' , 1]])->whereIn('type' , $question_type)->orderByRaw("RAND()")->limit($exam_count_array[$exam_count])->get();
                if(!$exam_list || empty($exam_list)){
                    return response()->json(['code' => 203 , 'msg' => '暂无随机生成的试题']);
                }
                
                //保存随机生成的试题
                foreach($exam_list as $k=>$v){
                    //循环插入试题
                    StudentDoTitle::insertGetId([
                        'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                        'bank_id'      =>   $bank_id ,
                        'subject_id'   =>   $subject_id ,
                        'chapter_id'   =>   $chapter_id ,
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
                        $exam_type_name = "";
                    }
                    
                    //试题随机展示
                    $exam_array[] = [
                        'exam_id'             =>  $v['id'] ,
                        'exam_name'           =>  $exam_info['exam_content'] ,
                        'exam_type_name'      =>  $exam_type_name ,
                        'correct_answer'      =>  $exam_info['answer'] ,
                        'option_list'         =>  $option_content ,
                        'my_answer'           =>  '' ,
                        'is_right'            =>  0
                    ];
                }
            } else {
                //查询还未做完的题列表
                $exam_list = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('chapter_id' , $chapter_id)->where('joint_id' , $joint_id)->where('type' , 1)->get();
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
                        $exam_type_name = "";
                    }
                    
                    //试题随机展示
                    $exam_array[] = [
                        'exam_id'             =>  $v['exam_id'] ,
                        'exam_name'           =>  $exam_info['exam_content'] ,
                        'exam_type_name'      =>  $exam_type_name ,
                        'correct_answer'      =>  $exam_info['answer'] ,
                        'option_list'         =>  $option_content ,
                        'my_answer'           =>  !empty($v['answer']) ? $v['answer'] : '' ,
                        'is_right'            =>  $v['is_right']
                    ];
                }
            }
        } else if($type == 2){  //快速做题
            //新数组赋值
            $exam_array = [];            
            
            //判断是否做完了随机生成的快速做题数量
            $rand_exam_count = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('is_right' , 0)->where('type' , 2)->count();
            if($rand_exam_count <= 0){
                //快速做题随机生成20条数据
                $exam_list = Exam::select("id","exam_content","answer")->where([['bank_id' , '=' , $bank_id] , ['subject_id' , '=' , $subject_id] , ['is_del' , '=' , 0] , ['is_publish' , '=' , 1]])->whereIn('type' , [1,2,3,4])->orderByRaw("RAND()")->limit(20)->get();
                if(!$exam_list || empty($exam_list)){
                    return response()->json(['code' => 203 , 'msg' => '暂无随机生成的试题']);
                }
                
                //保存随机生成的试题
                foreach($exam_list as $k=>$v){
                    //循环插入试题
                    StudentDoTitle::insertGetId([
                        'student_id'   =>   self::$accept_data['user_info']['user_id'] ,
                        'bank_id'      =>   $bank_id ,
                        'subject_id'   =>   $subject_id ,
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
                        $exam_type_name = "";
                    }
                    
                    //试题随机展示
                    $exam_array[] = [
                        'exam_id'             =>  $v['id'] ,
                        'exam_name'           =>  $exam_info['exam_content'] ,
                        'exam_type_name'      =>  $exam_type_name ,
                        'correct_answer'      =>  $exam_info['answer'] ,
                        'option_list'         =>  $option_content ,
                        'my_answer'           =>  '' ,
                        'is_right'            =>  0
                    ];
                }
            } else {
                //查询还未做完的题列表
                $exam_list = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where('type' , 2)->get();
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
                        $exam_type_name = "";
                    }
                    
                    //试题随机展示
                    $exam_array[] = [
                        'exam_id'             =>  $v['exam_id'] ,
                        'exam_name'           =>  $exam_info['exam_content'] ,
                        'exam_type_name'      =>  $exam_type_name ,
                        'correct_answer'      =>  $exam_info['answer'] ,
                        'option_list'         =>  $option_content ,
                        'my_answer'           =>  !empty($v['answer']) ? $v['answer'] : '' ,
                        'is_right'            =>  $v['is_right']
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
            
            //通过试卷的id获取下面的试题列表
            $papers_exam = PapersExam::where("papers_id" , $papers_id)->where("subject_id" , $subject_id)->where("is_del" , 0)->whereIn("type" ,[1,2,3,4])->get();
            if(!$papers_exam || empty($papers_exam)){
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
                    $exam_type_name = "";
                }
                
                //根据条件获取此学生此题是否答了
                $info = StudentDoTitle::where("student_id" , self::$accept_data['user_info']['user_id'])->where("papers_id" , $papers_id)->where("subject_id" , $subject_id)->where('exam_id' , $v['exam_id'])->where('type' , 3)->first();

                //试题随机展示
                $exam_array[] = [
                    'exam_id'             =>  $v['exam_id'] ,
                    'exam_name'           =>  $exam_info['exam_content'] ,
                    'exam_type_name'      =>  $exam_type_name ,
                    'correct_answer'      =>  $exam_info['answer'] ,
                    'option_list'         =>  $option_content ,
                    'my_answer'           =>  $info && !empty($info) && !empty($info['answer']) ? $info['answer'] : '' ,
                    'is_right'            =>  $info && !empty($info) ? $info['is_right'] : 0
                ];
            }
        }
        return response()->json(['code' => 200 , 'msg' => '操作成功' , 'data' => $exam_array]);
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
        
        //判断题库的id是否传递合法
        if(!$bank_id || $bank_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '题库id不合法']);
        }
        
        //判断科目的id是否传递合法
        if(!$subject_id || $subject_id <= 0){
            return response()->json(['code' => 202 , 'msg' => '科目id不合法']);
        }
        
        //通过题库和科目id查询试卷的列表
        $exam_array = Papers::where("bank_id" , $bank_id)->where("subject_id" , $subject_id)->where("is_del" , 0)->where("is_publish" , 1)->get();
        if(!$exam_array || empty($exam_array)){
            return response()->json(['code' => 203 , 'msg' => '暂无对应的试卷']);
        }
        
        //数组转化
        $exam_array = $exam_array->toArray();
        foreach($exam_array as $k=>$v){
            //单选题数量
            $exam_type_list = PapersExam::selectRaw("type , count('type') as t_count")->where("papers_id" , $v['id'])->where('is_del' , 0)->whereIn('type' , [1,2,3,4])->groupBy('type')->get()->toArray();
            if($exam_type_list && !empty($exam_type_list)){
                foreach($exam_type_list as $k1=>$v1){
                    if($v1['type'] == 1){
                        $exam_type_list[$k1]['sum_score'] = $v['signle_score'] * $v1['t_count'];
                    } else if($v1['type'] == 2){
                        $exam_type_list[$k1]['sum_score'] = $v['more_score'] * $v1['t_count'];
                    } else if($v1['type'] == 3){
                        $exam_type_list[$k1]['sum_score'] = $v['judge_score'] * $v1['t_count'];
                    } else if($v1['type'] == 4){
                        $exam_type_list[$k1]['sum_score'] = $v['options_score'] * $v1['t_count'];
                    }
                }

                $array[] = [
                    'papers_id'    =>  $v['id'] ,
                    'papers_name'  =>  $v['papers_name'] ,
                    'papers_time'  =>  $v['papers_time'] ,
                    'sum_score'    =>  array_sum(array_column($exam_type_list, 'sum_score'))
                ];
            } else {
                $array = [];
            }
        }
        return response()->json(['code' => 200 , 'msg' => '操作成功' , 'data' => $array]);
    }
}

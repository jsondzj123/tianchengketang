<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\QuestionSubject;
use App\Models\Chapters;
use App\Models\Exam;
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
        
        //分类
        $type_array = [
            ['type' => 1 , 'name' => "全部题(".$exam_count.")"] ,
            ['type' => 2 , 'name' => "未做题(".$exam_count.")"] ,
            ['type' => 3 , 'name' => "错题(0)"] ,
        ];
        
        //题量
        $count_array = [['type' => 1 , 'name' => "30道题"] , ['type' => 2 , 'name' => "60道题"] , ['type' => 3, 'name' => "100道题"]];
        return response()->json(['code' => 200 , 'msg' => '获取设置列表成功' , 'data' => ['exam_type_array' => $exam_type_array , 'type_array' => $type_array , 'count_array' => $count_array]]);
    }
    
    /*
     * @param  description   章节练习随机生成试题接口
     * @param author    dzj
     * @param ctime     2020-07-07
     * return string
     */
    public function doChapterExamList(){
        $bank_id      = isset(self::$accept_data['bank_id']) && self::$accept_data['bank_id'] > 0 ? self::$accept_data['bank_id'] : 0;                    //获取题库id
        $subject_id   = isset(self::$accept_data['subject_id']) && self::$accept_data['subject_id'] > 0 ? self::$accept_data['subject_id'] : 0;           //获取科目id
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
        
        //获取题型[1,2]
        $question_type = isset(self::$accept_data['question_type']) && !empty(self::$accept_data['question_type']) ? self::$accept_data['question_type'] : '';
        if(!$question_type || empty($question_type)){
            return response()->json(['code' => 201 , 'msg' => '请选择题型']);
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
        
        //根据设置的条件筛选试题
        $exam_list = Exam::select("id")->where([['bank_id' , '=' , $bank_id] , ['subject_id' , '=' , $subject_id] , ['chapter_id' , '=' , $chapter_id] , ['joint_id' , '=' , $joint_id] , ['is_del' , '=' , 0] , ['is_publish' , '=' , 1]])->orderByRaw("RAND()")->limit($exam_count_array[$exam_count])->get();
        return response()->json(['code' => 200 , 'msg' => '设置成功' , 'data' => $exam_list]);
    }
}

<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\QuestionSubject;
use App\Models\Chapters;
use App\Models\Exam;
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
}

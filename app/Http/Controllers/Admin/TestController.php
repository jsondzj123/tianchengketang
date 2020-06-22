<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Lesson;
use App\Models\LessonChild;
use App\Models\LessonVideo;
use App\Models\SubjectLesson;
use App\Tools\MTCloud;
use Maatwebsite\Excel\Facades\Excel;


class TestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function index()
    {
//        $MTCloud = new MTCloud();
//        $res = $MTCloud->courseGet(1048458);
//        if(!array_key_exists('code', $res) && !$res['code'] == 0){
//            Log::error('进入直播间失败:'.json_encode($res));
//            return $this->response('进入直播间失败', 500);
//        }
//        return $this->response($res['data']);
        $file = isset($_FILES['file']) && !empty($_FILES['file']) ? $_FILES['file'] : '';

        //存放文件路径
        $file_path= app()->basePath() . "/public/upload/excel/";
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
            move_uploaded_file($_FILES['file']['tmp_name'], $path);
        }
        // $exam_array = Excel::toArray(new \App\Imports\VideoImport , $path);
        // dd(count($exam_array));
        $exam_list = self::doImportExcel(new \App\Imports\UsersImport , $path);
       //导入资源数据
        /*foreach($exam_list['data'] as $key=>$value){
            $video = Video::where(['course_id' => trim($value[4]), 'name' => trim($value[3])])->get();
            if(empty($video->toArray())){
                Video::create([
                    'admin_id' => 1,
                    'name' => trim($value[3]),
                    'category' => 1,
                    'url' => 'test.mp4',
                    'course_id' => trim($value[4])
                ]);
            } 
        }
        return $this->response('success');
        */
        //资源学科
        
        /*foreach($exam_list['data'] as $key=>$value){
            $lesson = Lesson::where('title', $value[0])->first();
            if(!empty($lesson)){
                $subject = SubjectLesson::where('lesson_id', $lesson['id'])->get();
                $video = Video::where('course_id' , $value[4])->get();
                dd($video->toArray());
                // $child1 = LessonChild::where(['lesson_id' => $lesson['id'], 'name' => $value[1], 'pid' => 0])->first();
                // if(!empty($child1)){
                //     $child2 = LessonChild::where(['lesson_id' => $lesson['id'], 'pid' => $child1['id'], 'name' => $value[2]])->first();
                //     if (!empty($child2)) {
                //         $video = Video::where('name' , $value[3])->first();
                //         if(!empty($video)){
                //             LessonVideo::create([
                //                 'video_id' => $video['id'],
                //                 'child_id' => $child2['id'],
                //             ]);
                //         }
                //     }
                // }
            } 
        }
        return $this->response('success');*/

        //课程关联资源
        /*foreach ($exam_list['data'] as $key=>$value) {
            $lesson = Lesson::where('title', trim($value[0]))->first();
            if(!empty($lesson)){
                //dd(1);
                $child1 = LessonChild::where(['lesson_id' => $lesson['id'], 'name' => trim($value[1]), 'pid' => 0])->first();
                if(!empty($child1)){
                    $child2 = LessonChild::where(['lesson_id' => $lesson['id'], 'pid' => $child1['id'], 'name' => trim($value[2])])->first();
                    if (!empty($child2)) {
                        $video = Video::where('name' , trim($value[3]))->first();
                        if(!empty($video)){
                            LessonVideo::create([
                                'video_id' => $video['id'],
                                'child_id' => $child2['id'],
                            ]);
                        }
                    }
                }
            }    
        }*/
        //资源关联学科
        // foreach ($exam_list['data'] as $key=>$value) {
        //     $lesson = Lesson::where('title', trim($value[0]))->first();
        //     if(!empty($lesson)){
                
        //         $subject = SubjectLesson::where('lesson_id', $lesson['id'])->get();
        //         if (!empty($subject)) {
        //                 $subject_id = $subject->pluck('subject_id');
        //                 $video = Video::where('name' , trim($value[3]))->first();
        //                 if(!empty($video)){
        //                     //dd($subject_id);
        //                     $video->subjects()->attach($subject_id);
                            
        //                 }
        //         }
        //     }    
        // }
        // return $this->response('success');      
        
    }
}

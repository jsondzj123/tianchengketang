<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\Models\Collection;
use App\Models\Student;

class CollectionController extends Controller {

    //收藏列表
    public function index(Request $request){
        $pagesize = $request->input('pagesize') ?: 15;
        $page     = $request->input('page') ?: 1;
        $offset   = ($page - 1) * $pagesize;
        $data = Collection::where('student_id', self::$accept_data['user_info']['user_id'])
                    ->with(['lessons' => function ($query) {
                        $query->with(['methods' => function($query){
                            $query->select('id', 'name');
                        }])
                        ->select('id', 'title', 'cover');
                    }])
                    ->orderBy('created_at', 'desc'); 
                    
        $total = $data->count();
        $student = $data->skip($offset)->take($pagesize)->get();
        $lessons = [];
        foreach ($student as $key => $value) {
            $lessons[$key] = $value->lessons;
        }
        $data = [
            'page_data' => $lessons,
            'total' => $total,
        ];
        return $this->response($data);
    }

     /**
     * @param 收藏课程.
     * @param
     * @param  
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $student = Student::find(self::$accept_data['user_info']['user_id']);
        $lessonIds = $student->collectionLessons()->pluck('lesson_id');
        $flipped_haystack = array_flip($lessonIds->toArray());
        if ( isset($flipped_haystack[$request->input('lesson_id')]) )
        {
            return $this->response('已经收藏', 202);
        }
        try {
            $student->collectionLessons()->attach($request->input('lesson_id'));
        } catch (Exception $e) {
            Log::error('收藏失败:'.$e->getMessage());
            return $this->response($e->getMessage(), 500);
        }
        return $this->response('收藏成功');
    }

    /**
     * @param 取消收藏课程.
     * @param
     * @param  
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $student = Student::find(self::$accept_data['user_info']['user_id']);
        $lessonIds = $student->collectionLessons()->pluck('lesson_id');
        $flipped_haystack = array_flip($lessonIds->toArray());
        if (!isset($flipped_haystack[$request->input('lesson_id')]) )
        {
            return $this->response('已经取消', 202);
        }
        try {
            $student->collectionLessons()->detach($request->input('lesson_id'));
        } catch (Exception $e) {
            Log::error('取消失败:'.$e->getMessage());
            return $this->response($e->getMessage(), 500);
        }
        return $this->response('取消成功');
    }
    
}

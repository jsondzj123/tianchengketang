<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use  App\Tools\CurrentAdmin;
use Validator;
use App\Models\LiveClass;
use Log;


class LiveClassController extends Controller {

    /**
     * @param  直播班号列表
     * @param  pagesize   page
     * @param  author  孙晓丽
     * @param  ctime   2020/6/8 
     * @return  array
     */
    public function index(Request $request){
        $validator = Validator::make($request->all(), [
            'live_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $pagesize = $request->input('pagesize') ?: 15;
        $page     = $request->input('page') ?: 1;
        $offset   = ($page - 1) * $pagesize;
        $live_id = $request->input('live_id');
        $data = LiveClass::where(['is_del' => 0, 'is_forbid' => 0, 'live_id' => $live_id]);
        $total = $data->count();
        $lesson = $data->select('id', 'course_name', 'start_time', 'end_time', 'modetype')
            ->skip($offset)->take($pagesize)
            ->get();
        $data = [
            'page_data' => $lesson,
            'total' => $total,
        ];
        return $this->response($data);
    }

     /**
     * @param   所有班号列表
     * @param  pagesize   page
     * @param  author  孙晓丽
     * @param  ctime   2020/6/8
     * @return  array
     */
    public function allList(Request $request){
        $pagesize = $request->input('pagesize') ?: 15;
        $page     = $request->input('page') ?: 1;
        $offset   = ($page - 1) * $pagesize;
        $status   = $request->input('status') ?: 0;
        $start_time = $request->input('start_time');
        $end_time = $request->input('end_time');
        $keyword     = $request->input('keyword');
        $data = LiveClass::where(['is_del' => 0, 'is_forbid' => 0])
            ->where(function($query) use ($status, $keyword){
                if(!empty($keyword)){
                    $query->where('name', 'like', '%'.$keyword.'%');
                }
            });
        $total = $data->count();
        $lesson = $data->skip($offset)->take($pagesize)->get();
    
        $data = [
            'page_data' => $lesson,
            'total' => $total,
        ];
        return $this->response($data);
    }

    /**
     * 添加班号.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'live_id' => 'required',
            'name' => 'required',
            'description' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $user = CurrentAdmin::user();
        try{
           
            LiveClass::create([
                'admin_id'    => $user->id,
                'live_id'     => $request->input('live_id'),
                'name' => $request->input('name'),
                'description'    => $request->input('description'),
            ]);

        }catch(Exception $e){
            Log::error('创建失败:'.$e->getMessage());
            return $this->response($e->getMessage(), 500);
        }
        return $this->response('添加成功');
    }


    /* 修改班号
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $subjectIds = json_decode($request->input('subject_id'), true);
        try {
            $live = LiveClass::findOrFail($request->input('id'));
            $live->name = $request->input('name') ?: $live->name;
            $live->description = $request->input('description') ?: $live->description;
            $live->save();
            return $this->response("修改成功");
        } catch (Exception $e) {
            Log::error('修改课程信息失败' . $e->getMessage());
            return $this->response("修改成功");
        }
    }

    /**
     * 启用/禁用班号
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $live = LiveClass::findOrFail($request->input('id'));
        if($live->is_forbid == 1){
            $live->is_forbid = 0;
        }else{
            $live->is_forbid = 1;
        }
        if (!$live->save()) {
            return $this->response("操作失败", 500);
        }
        return $this->response("操作成功");
    }

    /**
     * 删除班号
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), 202);
        }
        $live = LiveClass::findOrFail($id);
        if($live->is_del == 1){
            $live->is_del = 0;
        }else{
            $live->is_del = 1;
        }
        if (!$live->save()) {
            return $this->response("删除失败", 500);
        }
        return $this->response("删除成功");
    }
}

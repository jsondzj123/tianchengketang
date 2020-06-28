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
     * @param  author  zzk
     * @param  ctime   2020/6/28
     * @return  array
     */
    public function index(Request $request){
        try{
            $list = LiveClass::getLiveClassList(self::$accept_data);
            return response()->json($list);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
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
            //获取提交的参数
            try{
                $data = LiveClass::AddLiveClass(self::$accept_data);
                return response()->json($data);
            } catch (Exception $ex) {
                return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
            }
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
        try{
            $one = LiveClass::updateLiveClassStatus(self::$accept_data);
            return response()->json($one);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /**
     * 删除班号
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) {
        try{
            $one = LiveClass::updateLiveClassDelete(self::$accept_data);
            return response()->json($one);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
}

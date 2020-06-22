<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use App\Models\Method;

class SubjectController extends Controller {

    /*
     * @param  科目列表
     * @param  current_count   count
     * @param  author  孙晓丽
     * @param  ctime   2020/5/1 
     * return  array
     */
    public function index(Request $request){
        $subjects = Subject::where('pid', 0)
                ->select('id', 'name', 'pid')
                ->orderBy('created_at', 'desc')
                ->get();
        foreach ($subjects as $value) {
                $child = [['id' => 0, 'name' => '全部']];
                $value['childs'] = array_merge($child, json_decode($value->childs()));
        }
        $all = [['id' => 0, 'name' => '全部', 'pid' => 0, 'childs' => []]];
        $data['subjects'] = array_merge($all, json_decode($subjects)); 
        $data['sort'] = [
            ['sort_id' => 0, 'name' => '综合', 'type' => ['asc', 'desc']],
            ['sort_id' => 1, 'name' => '按热度', 'type' => ['asc', 'desc']],
            ['sort_id' => 2, 'name' => '按价格升', 'type' => ['asc']],
            ['sort_id' => 3, 'name' => '按价格降', 'type' => ['desc']],
        ];
        $method = [['method_id' => 0, 'name' => '全部']];
        $methods = Method::select('id as method_id', 'name')->where(['is_del' => 0, 'is_forbid' => 0])->get();
        $data['method'] = array_merge($method, json_decode($methods));
        return $this->response($data);
    }
}

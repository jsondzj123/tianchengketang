<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coures;
use App\Models\Coureschapters;
use App\Models\CouresSubject;

class CourseController extends Controller {
    //获取学科列表
    public function coursesubject(){
        $data = self::$accept_data;
        $id = empty($data['id'])?0:$data['id'];
        $list = CouresSubject::couresWhere($id);
        return response()->json($list);
    }
  /*
       * @param  课程列表
       * @param  author  苏振文
       * @param  ctime   2020/6/28 9:41
       * return  array
       */
  public function courseList(){
      //获取提交的参数
      try{
          $data = Coures::courseList(self::$accept_data);
          return response()->json($data);
      } catch (Exception $ex) {
          return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
      }
  }
  /*
       * @param  课程添加
       * @param  author  苏振文
       * @param  ctime   2020/6/28 11:08
       * return  array
       */
  public function courseAdd(){
      //获取提交的参数
      try{
          $data = Coures::courseAdd(self::$accept_data);
          return response()->json($data);
      } catch (Exception $ex) {
          return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
      }
  }
  /*
       * @param  课程删除    授权无法删除
       * @param  author  苏振文
       * @param  ctime   2020/6/28 15:26
       * return  array
       */
  public function courseDel(){
      try{
          $data = Coures::courseDel(self::$accept_data);
          return response()->json($data);
      } catch (Exception $ex) {
          return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
      }
  }
  /*
       * @param  单条查询
       * @param  author  苏振文
       * @param  ctime   2020/6/28 15:32
       * return  array
       */
    public function courseFirst(){
        try{
            $data = Coures::courseFirst(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  修改
         * @param  author  苏振文
         * @param  ctime   2020/6/28 15:42
         * return  array
         */
    public function courseUpdate(){
        try{
            $data = Coures::courseUpdate(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  推荐
         * @param  author  苏振文
         * @param  ctime   2020/6/28 16:23
         * return  array
         */
    public function courseRecommend(){
        try{
            $data = Coures::courseComment(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /********录播课程******************************************************/
    /*
         * @param  章节列表
         * @param  author  苏振文
         * @param  ctime   2020/6/29 9:59
         * return  array
         */
    public function chapterList(){
        try{
            $data = Coureschapters::chapterList(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  删除章/节
         * @param  $user_id     参数
         * @param  author  苏振文
         * @param  ctime   2020/6/29 10:31
         * return  array
         */
    public function chapterDel(){
        try{
            $data = Coureschapters::chapterDel(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*章模块*/
    /*
        * @param  添加章
        * @param  author  苏振文
        * @param  ctime   2020/6/29 10:17
        * return  array
        */
    public function chapterAdd(){
        try{
            $data = Coureschapters::chapterAdd(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  修改章
         * @param  $user_id     参数
         * @param  author  苏振文
         * @param  ctime   2020/6/29 10:49
         * return  array
         */
    public function chapterUpdate(){
        try{
            $data = Coureschapters::chapterUpdate(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*节模块*/
    /*
         * @param  小节信息
         * @param  author  苏振文
         * @param  ctime   2020/6/29 15:43
         * return  array
         */
    public function sectionFirst(){
        try{
            $data = Coureschapters::sectionFirst(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  添加小节
         * @param  author  苏振文
         * @param  ctime   2020/6/29 14:31
         * return  array
         */
    public function sectionAdd(){
        try{
            $data = Coureschapters::sectionAdd(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  小节修改
         * @param  author  苏振文
         * @param  ctime   2020/6/29 15:44
         * return  array
         */
    public function sectionUpdate(){
        try{
            $data = Coureschapters::sectionUpdate(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  小节资料删除
         * @param  author  苏振文
         * @param  ctime   2020/6/29 17:00
         * return  array
         */
    public function sectionDataDel(){
        try{
            $data = Coureschapters::sectionDataDel(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

    /*============================直播课程=============================*/
    /*
         * @param  直播课程详情
         * @param  author  苏振文
         * @param  ctime   2020/6/30 9:44
         * return  array
         */
    public function liveCourses(){
        try{
            $data = Coureschapters::sectionDataDel(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
}

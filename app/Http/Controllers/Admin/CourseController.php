<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Coures;
use App\Models\Coureschapters;
use App\Models\CouresSubject;
use App\Models\CourseLiveResource;

class CourseController extends Controller {
    //获取学科列表
    public function subject(){
        $list = CouresSubject::couresWhere();
        return response()->json($list);
    }
    //资源模块学科
    public function subjects(){
        $list = CouresSubject::couresWheres();
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
       * @param  courseType
       * @param  author  苏振文
       * @param  ctime   2020/7/11 12:04
       * return  array
       */
      public function courseType(){
        $parent = self::$accept_data;
        $newparent = json_decode($parent['parent'],true);
        print_r($newparent);die;

        $list = Coures::where(['is_del'=>0,'status'=>1])
             ->where(function ($query) use ($parent) {
                 if(!empty($parent['parent'])){
                     $newparent = json_decode($parent['parent'],true);
                     if (!empty($newparent[0]) && $newparent[0] != '') {
                         $query->where('parent_id', $newparent[0]);
                     }
                     if (!empty($newparent[1]) && $newparent[1] != '') {
                         $query->where('child_id', $newparent[1]);
                     }
                 }
            })->get()->toArray();
          return response()->json(['code' => 200 , 'msg' => '成功','data'=>$list]);
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
    /*
         * @param  课程发布/停售
         * @param  author  苏振文
         * @param  ctime   2020/7/1 16:10
         * return  array
         */
    public function courseUpStatus(){
        try{
            $data = Coures::courseUpStatus(self::$accept_data);
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
            $data = CourseLiveResource::selectFind(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  删除直播资源
         * @param  author  苏振文
         * @param  ctime   2020/7/1 15:19
         * return  array
         */
    public function liveCoursesDel(){
        try{
            $data = CourseLiveResource::delLiveCourse(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  修改直播资源
         * @param  author  苏振文
         * @param  ctime   2020/7/1 15:29
         * return  array
         */
    public function liveCoursesUp(){
        try{
            $data = CourseLiveResource::upLiveCourse(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  选择或取消直播资源
         * @param  author  苏振文
         * @param  ctime   2020/7/1 15:36
         * return  array
         */
    public function liveToCourse(){
        try{
            $data = CourseLiveResource::liveToCourse(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  直播课程关联直播资源列表
         * @param  author  苏振文
         * @param  ctime   2020/7/1 17:02
         * return  array
         */
    public function liveToCourseList(){
        try{
            $data = Coures::liveToCourseList(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }
    /*
         * @param  直播课程进行排课
         * @param  author  苏振文
         * @param  ctime   2020/7/1 17:44
         * return  array
         */
    public function liveToCourseshift(){
        try{
            $data = Coures::liveToCourseshift(self::$accept_data);
            return response()->json($data);
        } catch (Exception $ex) {
            return response()->json(['code' => 500 , 'msg' => $ex->getMessage()]);
        }
    }

}

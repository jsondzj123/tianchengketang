<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Coures;
use App\Models\Couresmethod;
use App\Models\CourseRefResource;
use App\Models\CourseSchool;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Articletype;
use App\Models\FootConfig;
use App\Models\Admin;
use App\Models\CouresSubject;
use App\Models\Article;

class NewsController extends Controller {
	protected $school;
    protected $data;
    public function __construct(){
        $this->data = $_REQUEST;
        $this->school = School::where(['dns'=>$this->data['dns']])->first();
        // $this->school = School::where(['dns'=>$_SERVER['SERVER_NAME']])->first();
    }
    //列表
    public function getList(){
    	$articleArr = [];
        $data = $this->data;
    	$pagesize = !isset($this->data['pagesize']) || $this->data['pagesize']  <= 0 ? 8:$this->data['pagesize'];   
    	$page = !isset($this->data['page']) || $this->data['page'] <= 0 ?1 :$this->data['page'];
    	$offset   = ($page - 1) * $pagesize;

    	$count = Article::leftJoin('ld_article_type','ld_article.article_type_id','=','ld_article_type.id')
                        ->where(['ld_article_type.school_id'=>$this->school['id'],'ld_article_type.status'=>1,'ld_article_type.is_del'=>1,'ld_article.status'=>1,'ld_article.is_del'=>1])
    					->where(function($query) use ($data) {
                            if(!empty($data['articleOne']) && $data['articleOne'] != ''){
                                $query->where('article_type_id',$data['articleOne']);
                            }
                        })->orderBy('ld_article.create_at','desc')
    				->select('ld_article.id')
    				->count();

    	$Articletype = Articletype::where(['school_id'=>$this->school['id'],'status'=>1,'is_del'=>1])->select('id','typename')->get();
    	if($count >0){
    		$where = ['ld_article_type.school_id'=>$this->school['id'],'ld_article_type.status'=>1,'ld_article_type.is_del'=>1,'ld_article.status'=>1,'ld_article.is_del'=>1];
    		$articleArr = Article::leftJoin('ld_article_type','ld_article.article_type_id','=','ld_article_type.id')
    					->where($where)
    					->where(function($query) use ($data) {
                            if(!empty($data['articleOne']) && $data['articleOne'] != ''){
                                $query->where('article_type_id',$data['articleOne']);
                            }
                        })->orderBy('ld_article.create_at','desc')
    				->select('ld_article.id','ld_article.article_type_id','ld_article.title','ld_article.share','ld_article.create_at','ld_article.image','ld_article_type.typename')
                    ->offset($offset)->limit($pagesize)
    				->get();
    	}
        if(!empty($articleArr)){
            foreach ($articleArr as $k => &$new) {
                if($new['share'] == null || $new['share'] == 'null'){
                    $new['share'] = 0;
                }
                if($new['watch_num'] == null || $new['watch_num'] == 'null'){
                    $new['watch_num'] = 0;
                }
                $new['share'] = $new['share'] + $new['watch_num'];
            }
        }
    	return  ['code'=>200,'msg'=>'Success','data'=>$articleArr,'total'=>$count,'article_type'=>$Articletype];
    }
    //热门文章
    public function hotList(){
    	$hotList = Article::where(['school_id'=>$this->school['id'],'status'=>1,'is_del'=>1])->orderBy('share','desc')
    	->select('id','article_type_id','title','share','create_at')
    	->limit(10)->get();
        if(!empty($hotList)){
            foreach ($hotList as $k => &$new) {
                if($new['share'] == null || $new['share'] == 'null'){
                    $new['share'] = 0;
                }
                if($new['watch_num'] == null || $new['watch_num'] == 'null'){
                    $new['watch_num'] = 0;
                }
                $new['share'] = $new['share'] + $new['watch_num'];
            }
        }
    	return ['code'=>200,'msg'=>'Success','data'=>$hotList];
    } 
    //推荐文章
    public function newestList(){    	
    	$where = ['ld_article_type.school_id'=>$this->school['id'],'ld_article_type.status'=>1,'ld_article_type.is_del'=>1,'ld_article.status'=>1,'ld_article.is_del'=>1,'ld_article.is_recommend'=>1];
    	$newestList = Articletype::leftJoin('ld_article','ld_article.article_type_id','=','ld_article_type.id')
             ->where($where)
             ->select('ld_article.id','ld_article.article_type_id','ld_article.title','ld_article.share','ld_article.create_at','ld_article.image','ld_article.description')
             ->orderBy('ld_article.update_at','desc')->limit(4)->get();
        if(!empty($newestList)){
            foreach ($newestList as $k => &$new) {
                if($new['share'] == null || $new['share'] == 'null'){
                    $new['share'] = 0;
                }
                if($new['watch_num'] == null || $new['watch_num'] == 'null'){
                    $new['watch_num'] = 0;
                }
                $new['share'] = $new['share'] + $new['watch_num'];
            }
        }     
    	return ['code'=>200,'msg'=>'Success','data'=>$newestList];
    }
    //查看详情
    public function details(){
    	$where = ['ld_article.id'=>$this->data['id'],'ld_article_type.status'=>1,'ld_article_type.is_del'=>1,'ld_article.status'=>1,'ld_article.is_del'=>1];
    	$newData = Articletype::leftJoin('ld_article','ld_article.article_type_id','=','ld_article_type.id')
             ->where($where)
           	 ->first();
        if(!empty($newData)){
            if($newData['share'] == null || $newData['share'] == 'null'){
                $newData['share'] = 0;
            }
            if($newData['watch_num'] == null || $newData['watch_num'] == 'null'){
                $newData['watch_num'] = 0;
            }
            $newData['share'] = $newData['share'] + $newData['watch_num'];
        } 
        $res = Article::increment('watch_num',1);
        return ['code'=>200,'msg'=>'Success','data'=>$newData]; 
    }



}
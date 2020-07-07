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
        // $this->school = School::where(['dns'=>$this->data['school_dns']])->first();
        $this->school = School::where(['dns'=>$_SERVER['SERVER_NAME']])->first();
    }
    //列表
    public function getList(){
    	$articleArr = [];
    	$pagesize = !isset($this->data['pagesize']) || $this->data['pagesize']  <= 0 ? 8:$this->data['pagesize'];   
    	$page = !isset($this->data['page']) || $this->data['page'] <= 0 ?1 :$this->data['page'];
    	$offset   = ($page - 1) * $pagesize;
    	$count = Article::where(['school_id'=>$this->school['id'],'status'=>1,'is_del'=>1])
    					->where(function($query) use ($body,$school_id) {
                            if(!empty($this->data['articleOne']) && $this->data['articleOne'] != ''){
                                $query->where('article_type_id',$body['subjectOne']);
                            }
                        })->order('create_at desc')
    				->select('id')
    				->count();
    	$Articletype = Articletype::where(['school_id'=>$this->school['id'],'status'=>1,'is_del'=>1])->select('id','typename')->get();
    	if($count >0){
    		$where = ['ld_article_type.school_id'=>$this->school['id'],'ld_article_type.status'=>1,'ld_article_type.is_del'=>1,'ld_article.status'=>1,'ld_article.is_del'=>1];
    		$articleArr = Article::leftJoin('ld_article_type','ld_article.article_type_id','=','ld_article_type.id')
    					->where($where)
    					->where(function($query) use ($body,$school_id) {
                            if(!empty($this->data['articleOne']) && $this->data['articleOne'] != ''){
                                $query->where('article_type_id',$body['subjectOne']);
                            }
                        })->order('create_at desc')
    				->select('id','article_type_id','title','share','create_at','image')
    				->get();
    	}
    	return  ['code'=>200,'msg'=>'Success','data'=>$articleArr,'total'=>$count,'article_type'=>$Articletype];
    }
    //热门文章
    public function hotList(){
    	$hotList = Article::where(['school_id'=>$this->school['id'],'status'=>1,'is_del'=>1])->order('share desc')
    	->select('id','article_type_id','title','share','create_at')
    	->limit(10)->get();
    	return ['code'=>200,'msg'=>'Success','data'=>$hotList];
    } 
    //最新文章
    public function newestList(){    	
    	$where = ['ld_article_type.school_id'=>$this->school['id'],'ld_article_type.status'=>1,'ld_article_type.is_del'=>1,'ld_article.status'=>1,'ld_article.is_del'=>1];
    	$newestList = Articletype::leftJoin('ld_article','ld_article.article_type_id','=','ld_article_type.id')
             ->where($where)
             ->select('id','article_type_id','title','share','create_at','image')
             ->order('create_at','desc')->limit(5)->get();
    	return ['code'=>200,'msg'=>'Success','data'=>$newestList];
    }
    //查看详情
    public function details(){
    	$where = ['ld_article.id'=>$this->data['id'],'ld_article_type.status'=>1,'ld_article_type.is_del'=>1,'ld_article.status'=>1,'ld_article.is_del'=>1];
    	$newData = Articletype::leftJoin('ld_article','ld_article.article_type_id','=','ld_article_type.id')
             ->where($where)
           	 ->first();
        return ['code'=>200,'msg'=>'Success','data'=>$newData]; 
    }



}
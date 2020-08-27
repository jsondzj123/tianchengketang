<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CorsMiddleware
{
    private $headers;
    private $allow_origin;

    public function handle(Request $request , \Closure $next)
    {
        $this->headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
            'Access-Control-Allow-Headers' => $request->header('Access-Control-Request-Headers'),
            'Access-Control-Allow-Credentials' => 'true',//允许客户端发送cookie
            'Access-Control-Max-Age' => 1728000 //该字段可选，用来指定本次预检请求的有效期，在此期间，不用发出另一条预检请求。
        ];

        $this->allow_origin = [
            'http://localhost',
            'http://localhost:8080',
            'http://localhost:8081',
            'http://192.168.1.12:8080',
            'http://192.168.1.11:8081',
            'http://test.admin.longde999.cn',
            'http://admin.longde999.cn',
            'http://testwo.admin.longde999.cn',
            'http://pay.manage.longde99.com',
            'http://ketang.longde999.cn',
            'http://tiancheng.admin.longde999.cn',
            'http://tiancheng.longde999.cn',
            'http://qc.qcedu101.com',
            'http://yd.bjyangde.com',
            'http://gl.tiancheng27.com',
            'http://hs.hongsheng369.com',
            'http://yl.tiancheng27.com',
            'http://scld.shanchuangpeixun.com',
            'http://lc.lczx99.com',
            'http://xh.xianghangedu.com',
            'http://zz.tiancheng27.com',
            'http://jy.jingyi989.com',
            'http://tc.tiancheng27.com',
            'http://edu.hxsx99.com',
            'http://jl.jinlong567.com',
            'http://bk.tiancheng27.com',
            'http://dc.tiancheng27.com',
            'http://bw.tiancheng989.com',
            'http://bk.tiancheng989.com',
            'http://gl.tiancheng989.com',
            'http://yl.tiancheng989.com',
            'http://zz.tiancheng989.com',
            'http://ty.tiancheng989.com',
            'http://edu.yiqijy17.com',
            'http://gl.glteach.com',
            'http://www.glteach.com',
            'http://mengshu.zhima989.com',
            'http://frontdeskresources.longde999.cn/',
            'http://wl.wanglongzaixian.com',
            'http://www.yilong119.com',
            'http://neibu.testwo.longde999.cn',
            'http://neibu.tiancheng.longde999.cn',
            'http://edu.jingluojiaoyu.com'
        
        ];
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

        //如果origin不在允许列表内，直接返回403
        if (!in_array($origin, $this->allow_origin) && !empty($origin))
            return new Response('Forbidden', 403);
        //如果是复杂请求，先返回一个200，并allow该origin
        if ($request->isMethod('options'))
            return $this->setCorsHeaders(new Response('OK', 200), $origin);
        //如果是简单请求或者非跨域请求，则照常设置header
        $response = $next($request);
        $methodVariable = array($response, 'header');
        //这个判断是因为在开启session全局中间件之后，频繁的报header方法不存在，所以加上这个判断，存在header方法时才进行header的设置
        if (is_callable($methodVariable, false, $callable_name)) {
            return $this->setCorsHeaders($response, $origin);
        }
        return $response;
    }

    /**
     * @param $response
     * @return mixed
     */
    public function setCorsHeaders($response, $origin)
    {
        foreach ($this->headers as $key => $value) {
            $response->header($key, $value);
        }
        if (in_array($origin, $this->allow_origin)) {
            $response->header('Access-Control-Allow-Origin', $origin);
        } else {
            $response->header('Access-Control-Allow-Origin', '');
        }
        return $response;
    }
}

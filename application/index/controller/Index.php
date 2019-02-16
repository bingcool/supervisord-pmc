<?php
namespace app\index\controller;

use think\facade\Config;

class Index
{
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    public function hello($name = 'ThinkPHP5')
    {
        var_dump('mmmmmm');
        return 'hello,' . $name;
    }

    public function upload() {
        $file = "/home/wwwroot/supervisord_pmc/test1.ini";
        $url = "http://192.168.99.103:9502/Upload/uploadfile?filename=test1&ext=ini";
        var_dump("vvvvvvvvvvvvvvvvv");
        if(file_exists($file)){
            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'content-type:application/x-www-form-urlencoded',
                    'content' => file_get_contents($file)
                )
            );
            $context = stream_context_create($opts);
            $response = file_get_contents($url, false, $context);
            //$ret = json_decode($response, true);
            //eturn $ret['success'];
        }else{
            return false;
        }
    }

    public function superctl()
    {
        
        $xmlConfs = Config::get('xmlrpc.supervisor_servers');

        $result = [];

        $xmlrpc = new \app\common\xmlrpc\XmlRpc();

        foreach($xmlConfs as $server=>$config) {
            $response = $xmlrpc->_request($server);
            $result[$server] = $response;
        }

        return json($result);
    }
}

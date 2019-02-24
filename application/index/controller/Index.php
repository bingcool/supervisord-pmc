<?php
namespace app\index\controller;

use think\Controller;
use think\facade\Config;

class Index extends Controller
{
    public function index()
    {
        $name = "bingcool";
        //var_dump($name);
        $this->assign('name', 'thinkphp');
        return $this->fetch();
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

    public function getAllServerStatus()
    {
        
        $xmlConfs = Config::get('xmlrpc.supervisor_servers');

        $result = [];

        $xmlrpc = new \app\common\xmlrpc\XmlRpc();

        foreach($xmlConfs as $server=>$config) {
            $response = $xmlrpc->_request($server);
            $result[$server] = $response;
        }

        return $result;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: bingcool
 * Date: 2019/2/16
 * Time: 9:31
 */
namespace  app\common;

use think\facade\Config;

class XmlRpc {

    /**
     * @param $server
     * @param string $method
     * @param array $request
     * @param array $config
     * @return string
     * @throws \Exception
     */
    public function _request($server, $method = "getAllProcessInfo", $request = [], $config = []) {
		if(empty($config)) {
			$xmlConfs = Config::get('xmlrpc.supervisor_servers');
			if(!isset($xmlConfs[$server])) {
				throw new \Exception("xmlrpc.supervisor_servers[$server] is undefined!", 1);
			}else {
				$config = $xmlConfs[$server];
			}
		}
		
        $xmlTimeout = Config::get('xmlrpc.timeout');
        $timeout = isset($xmlTimeout) ? $xmlTimeout : 3;

        $xml = new \app\xmlrpc\XmlRpc();
        $xml->initialize();
        $xml->server($config['url'],$config['port']);
        $xml->method('supervisor.'.$method);
        $xml->timeout($timeout);
        $xml->auth($config['username'], $config['password']);
        $xml->request($request);

        if(!$xml->send_request()){
            $response['error'] = $xml->display_error();
        }else{
            $response = $xml->display_response();
        }

        return $response;
	}
}
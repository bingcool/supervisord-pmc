<?php
namespace app\service\supervisord;

use think\facade\Config;
use app\common\xmlrpc\XmlRpc;

class supervisorctl {

    protected $supervisord_conf = [];

    protected $xmlrpc;

    /**
     * supervisorctl constructor
     */
    public function __construct() {
        $xmlConfs = Config::get('xmlrpc.supervisor_servers');
        $this->supervisord_conf = $xmlConfs;
        $this->xmlrpc = new XmlRpc();
    }

    /**
     * startProcess 启动一个进程
     * @param $process_name
     * @param bool $wait
     */
    public function startProcess($server, $process_name, $wait = true) {
        $this->xmlrpc->_request($server,'startProcess',[$process_name, $wait]);
    }

    /**
     * startAllProcesses 启动服务下的所有进程
     * @param $server
     * @param bool $wait
     * @throws \Exception
     */
    public function startAllProcesses($server, $wait = true) {
        $this->xmlrpc->_request($server,'startAllProcesses',[$wait]);
    }

    /**
     * startProcessGroup 启动某个分组下的所有进程
     * @param $server
     * @param $group_name
     * @param bool $wait
     * @throws \Exception
     */
    public function startProcessGroup($server, $group_name, $wait = true) {
        $this->xmlrpc->_request($server,'startProcessGroup',[$group_name, $wait]);
    }

    /**
     * stopProcess 停止服务的某个进程
     * @param $server
     * @param $process_name
     * @param bool $wait
     * @throws \Exception
     */
    public function stopProcess($server, $process_name, $wait = true) {
        $this->xmlrpc->_request($server,'stopProcess',[$process_name, $wait]);
    }

    /**
     * stopAllProcesses 停止服务下所有进程
     * @param $server
     * @param bool $wait
     * @throws \Exception
     */
    public function stopAllProcesses($server, $wait = true) {
        $this->xmlrpc->_request($server,'stopAllProcesses', [$wait]);
    }

    /**
     * restartProcess 重启某个进程
     * @param $server
     * @param $process_name
     * @param bool $wait
     */
    public function restartProcess($server, $process_name, $wait = true) {
        $this->stopProcess($server, $process_name, $wait);
        sleep(3);
        $this->startProcess($server, $process_name, $wait);
    }

    /**
     * restartAllProcess 重启所有的进程
     * @param $server
     * @param bool $wait
     * @throws \Exception
     */
    public function restartAllProcess($server, $wait = true) {
        $this->stopAllProcesses($server, $wait);
        sleep(3);
        $this->startAllProcesses($server, $wait);
    }

    /**
     * getProcessInfo 获取某个进程的信息
     * @param $server
     * @param $process_name
     * @return string
     * @throws \Exception
     */
    public function getProcessInfo($server, $process_name) {
        return $this->xmlrpc->_request($server, 'getProcessInfo', [$process_name]);
    }

    /**
     * getAllProcessInfo 获取所有进程信息
     * @param $server
     * @param $process_name
     * @return string
     * @throws \Exception
     */
    public function getAllProcessInfo($server, $process_name) {
        return $this->xmlrpc->_request($server, 'getAllProcessInfo', []);
    }

    /**
     * clearProcessLogs 清空打印出的错误日志
     * @param $server
     * @param $process_name
     * @throws \Exception
     */
    public function clearProcessLogs($server, $process_name) {
        $this->xmlrpc->_request($server, 'clearProcessLogs', [$process_name]);
    }

    /**
     * addProcessGroup 添加一个新的进程加入supervisor配置中
     * @param $server
     * @param $process_name
     * @throws \Exception
     */
    public function addProcessGroup($server, $process_name) {
        $this->xmlrpc->_request($server, 'addProcessGroup', [$process_name]);
    }

    /**
     * removeProcessGroup 删除一个进程
     * @param $server
     * @param $process_name
     * @throws \Exception
     */
    public function removeProcessGroup($server, $process_name) {
        $this->xmlrpc->_request($server, 'removeProcessGroup', $process_name);
    }

    /**
     * getSupervisorVersion 获取supervisord version
     * @param $server
     * @return string
     * @throws \Exception
     */
    public function getSupervisorVersion($server) {
        return $this->xmlrpc->_request($server, 'getSupervisorVersion');
    }


}
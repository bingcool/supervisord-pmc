<?php
/**
 * Created by PhpStorm.
 * User: bingcool
 * Date: 2019/2/16
 * Time: 11:46
 */
namespace app\xmlrpc;

class XmlRpcClient extends XmlRpc {
    /**
     * Path
     *
     * @var	string
     */
    public $path			= '';

    /**
     * Server hostname
     *
     * @var	string
     */
    public $server			= '';

    /**
     * Server port
     *
     * @var	int
     */
    public $port			= 80;

    /**
     *
     * Server username
     *
     * @var	string
     */
    public $username;

    /**
     * Server password
     *
     * @var	string
     */
    public $password;

    /**
     * Proxy hostname
     *
     * @var	string
     */
    public $proxy			= FALSE;

    /**
     * Proxy port
     *
     * @var	int
     */
    public $proxy_port		= 8080;

    /**
     * Error number
     *
     * @var	string
     */
    public $errno			= '';

    /**
     * Error message
     *
     * @var	string
     */
    public $errstring		= '';

    /**
     * Timeout in seconds
     *
     * @var	int
     */
    public $timeout		= 5;

    /**
     * No Multicall flag
     *
     * @var	bool
     */
    public $no_multicall	= FALSE;

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @param	string	$path
     * @param	object	$server
     * @param	int	$port
     * @param	string	$proxy
     * @param	int	$proxy_port
     * @return	void
     */
    public function __construct($path, $server, $port = 80, $proxy = FALSE, $proxy_port = 8080)
    {
        parent::__construct();

        $url = parse_url('http://'.$server);

        if (isset($url['user'], $url['pass']))
        {
            $this->username = $url['user'];
            $this->password = $url['pass'];
        }

        $this->port = $port;
        $this->server = $url['host'];
        $this->path = $path;
        $this->proxy = $proxy;
        $this->proxy_port = $proxy_port;
    }

    // --------------------------------------------------------------------

    /**
     * Send message
     *
     * @param	mixed	$msg
     * @return	object
     */
    public function send($msg)
    {
        if (is_array($msg))
        {
            // Multi-call disabled
            return new XmlRpcResponse(0, $this->xmlrpcerr['multicall_recursion'], $this->xmlrpcstr['multicall_recursion']);
        }

        return $this->sendPayload($msg);
    }

    // --------------------------------------------------------------------

    /**
     * Send payload
     *
     * @param	object	$msg
     * @return	object
     */
    public function sendPayload($msg)
    {
        if ($this->proxy === FALSE)
        {
            $server = $this->server;
            $port = $this->port;
        }
        else
        {
            $server = $this->proxy;
            $port = $this->proxy_port;
        }

        $fp = @fsockopen($server, $port, $this->errno, $this->errstring, $this->timeout);

        if ( ! is_resource($fp))
        {

            return new XmlRpcResponse(0, $this->xmlrpcerr['http_error'], $this->xmlrpcstr['http_error']);
        }

        if (empty($msg->payload))
        {
            // $msg = XML_RPC_Messages
            $msg->createPayload();
        }

        $r = "\r\n";
        $op = 'POST '.$this->path.' HTTP/1.0'.$r
            .'Host: '.$this->server.$r
            .'Content-Type: text/xml'.$r
            .(isset($this->username, $this->password) ? 'Authorization: Basic '.base64_encode($this->username.':'.$this->password).$r : '')
            .'User-Agent: '.$this->xmlrpcName.$r
            .'Content-Length: '.strlen($msg->payload).$r.$r
            .$msg->payload;

        stream_set_timeout($fp, $this->timeout); // set timeout for subsequent operations

        for ($written = $timestamp = 0, $length = strlen($op); $written < $length; $written += $result)
        {
            if (($result = fwrite($fp, substr($op, $written))) === FALSE)
            {
                break;
            }
            // See https://bugs.php.net/bug.php?id=39598 and http://php.net/manual/en/function.fwrite.php#96951
            elseif ($result === 0)
            {
                if ($timestamp === 0)
                {
                    $timestamp = time();
                }
                elseif ($timestamp < (time() - $this->timeout))
                {
                    $result = FALSE;
                    break;
                }
            }
            else
            {
                $timestamp = 0;
            }
        }

        if ($result === FALSE)
        {
            return new XmlRpcResponse(0, $this->xmlrpcerr['http_error'], $this->xmlrpcstr['http_error']);
        }

        $resp = $msg->parseResponse($fp);
        fclose($fp);
        return $resp;
    }
}
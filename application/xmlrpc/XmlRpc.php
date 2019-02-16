<?php
/**
 * Created by PhpStorm.
 * User: bingcool
 * Date: 2019/2/16
 * Time: 9:31
 */
namespace  app\xmlrpc;

class XmlRpc {

    /**
     * xmlClient version
     */
    const VERSION      = "1.0.0";

    /**
     * Debug flag
     *
     * @var	bool
     */
    public $debug		= FALSE;

    /**
     * I4 data type
     *
     * @var	string
     */
    public $xmlrpcI4	= 'i4';

    /**
     * Integer data type
     *
     * @var	string
     */
    public $xmlrpcInt	= 'int';

    /**
     * Boolean data type
     *
     * @var	string
     */
    public $xmlrpcBoolean	= 'boolean';

    /**
     * Double data type
     *
     * @var	string
     */
    public $xmlrpcDouble	= 'double';

    /**
     * String data type
     *
     * @var	string
     */
    public $xmlrpcString	= 'string';

    /**
     * DateTime format
     *
     * @var	string
     */
    public $xmlrpcDateTime	= 'dateTime.iso8601';

    /**
     * Base64 data type
     *
     * @var	string
     */
    public $xmlrpcBase64	= 'base64';

    /**
     * Array data type
     *
     * @var	string
     */
    public $xmlrpcArray	= 'array';

    /**
     * Struct data type
     *
     * @var	string
     */
    public $xmlrpcStruct	= 'struct';

    /**
     * Data types list
     *
     * @var	array
     */
    public $xmlrpcTypes	= array();

    /**
     * Valid parents list
     *
     * @var	array
     */
    public $valid_parents	= array();

    /**
     * Response error numbers list
     *
     * @var	array
     */
    public $xmlrpcerr		= array();

    /**
     * Response error messages list
     *
     * @var	string[]
     */
    public $xmlrpcstr		= array();

    /**
     * Encoding charset
     *
     * @var	string
     */
    public $xmlrpc_defencoding	= 'UTF-8';

    /**
     * XML-RPC client name
     *
     * @var	string
     */
    public $xmlrpcName		= 'XML-RPC for CodeIgniter';

    /**
     * XML-RPC version
     *
     * @var	string
     */
    public $xmlrpcVersion		= '1.1';

    /**
     * Start of user errors
     *
     * @var	int
     */
    public $xmlrpcerruser		= 800;

    /**
     * Start of XML parse errors
     *
     * @var	int
     */
    public $xmlrpcerrxml		= 100;

    /**
     * Backslash replacement value
     *
     * @var	string
     */
    public $xmlrpc_backslash	= '';

    /**
     * XML-RPC Client object
     *
     * @var	object
     */
    public $client;

    /**
     * XML-RPC Method name
     *
     * @var	string
     */
    public $method;

    /**
     * XML-RPC Data
     *
     * @var	array
     */
    public $data;

    /**
     * XML-RPC Message
     *
     * @var	string
     */
    public $message			= '';

    /**
     * Request error message
     *
     * @var	string
     */
    public $error			= '';

    /**
     * XML-RPC result object
     *
     * @var	object
     */
    public $result;

    /**
     * XML-RPC Response
     *
     * @var	array
     */
    public $response		= array(); // Response from remote server

    /**
     * XSS Filter flag
     *
     * @var	bool
     */
    public $xss_clean		= TRUE;

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * Initializes property default values
     *
     * @param	array	$config
     * @return	void
     */
    public function __construct($config = array())
    {
        $this->xmlrpc_backslash = chr(92).chr(92);

        // Types for info sent back and forth
        $this->xmlrpcTypes = array(
            $this->xmlrpcI4	 		=> '1',
            $this->xmlrpcInt		=> '1',
            $this->xmlrpcBoolean	=> '1',
            $this->xmlrpcString		=> '1',
            $this->xmlrpcDouble		=> '1',
            $this->xmlrpcDateTime	=> '1',
            $this->xmlrpcBase64		=> '1',
            $this->xmlrpcArray		=> '2',
            $this->xmlrpcStruct		=> '3'
        );

        // Array of Valid Parents for Various XML-RPC elements
        $this->valid_parents = array('BOOLEAN' => array('VALUE'),
            'I4'				=> array('VALUE'),
            'INT'				=> array('VALUE'),
            'STRING'			=> array('VALUE'),
            'DOUBLE'			=> array('VALUE'),
            'DATETIME.ISO8601'	=> array('VALUE'),
            'BASE64'			=> array('VALUE'),
            'ARRAY'			=> array('VALUE'),
            'STRUCT'			=> array('VALUE'),
            'PARAM'			=> array('PARAMS'),
            'METHODNAME'		=> array('METHODCALL'),
            'PARAMS'			=> array('METHODCALL', 'METHODRESPONSE'),
            'MEMBER'			=> array('STRUCT'),
            'NAME'				=> array('MEMBER'),
            'DATA'				=> array('ARRAY'),
            'FAULT'			=> array('METHODRESPONSE'),
            'VALUE'			=> array('MEMBER', 'DATA', 'PARAM', 'FAULT')
        );

        // XML-RPC Responses
        $this->xmlrpcerr['unknown_method'] = '1';
        $this->xmlrpcstr['unknown_method'] = 'This is not a known method for this XML-RPC Server';
        $this->xmlrpcerr['invalid_return'] = '2';
        $this->xmlrpcstr['invalid_return'] = 'The XML data received was either invalid or not in the correct form for XML-RPC. Turn on debugging to examine the XML data further.';
        $this->xmlrpcerr['incorrect_params'] = '3';
        $this->xmlrpcstr['incorrect_params'] = 'Incorrect parameters were passed to method';
        $this->xmlrpcerr['introspect_unknown'] = '4';
        $this->xmlrpcstr['introspect_unknown'] = 'Cannot inspect signature for request: method unknown';
        $this->xmlrpcerr['http_error'] = '5';
        $this->xmlrpcstr['http_error'] = "Did not receive a '200 OK' response from remote server.";
        $this->xmlrpcerr['no_data'] = '6';
        $this->xmlrpcstr['no_data'] = 'No data received from server.';

        $this->initialize($config);

    }

    // --------------------------------------------------------------------

    /**
     * Initialize
     *
     * @param	array	$config
     * @return	void
     */
    public function initialize($config = array())
    {
        if (count($config) > 0)
        {
            foreach ($config as $key => $val)
            {
                if (isset($this->$key))
                {
                    $this->$key = $val;
                }
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Parse server URL
     *
     * @param	string	$url
     * @param	int	$port
     * @param	string	$proxy
     * @param	int	$proxy_port
     * @return	void
     */
    public function server($url, $port = 80, $proxy = FALSE, $proxy_port = 8080)
    {
        if (stripos($url, 'http') !== 0)
        {
            $url = 'http://'.$url;
        }

        $parts = parse_url($url);

        if (isset($parts['user'], $parts['pass']))
        {
            $parts['host'] = $parts['user'].':'.$parts['pass'].'@'.$parts['host'];
        }

        $path = isset($parts['path']) ? $parts['path'] : '/';

        if ( ! empty($parts['query']))
        {
            $path .= '?'.$parts['query'];
        }

        $this->client = new XmlRpcClient($path, $parts['host'], $port, $proxy, $proxy_port);
    }

    public function auth($username, $password) {
        $this->client->username = $username;
        $this->client->password = $password;
    }

    // --------------------------------------------------------------------

    /**
     * Set Timeout
     *
     * @param	int	$seconds
     * @return	void
     */
    public function timeout($seconds = 5)
    {
        if ($this->client !== NULL && is_int($seconds))
        {
            $this->client->timeout = $seconds;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set Methods
     *
     * @param	string	$function	Method name
     * @return	void
     */
    public function method($function)
    {
        $this->method = $function;
    }

    // --------------------------------------------------------------------

    /**
     * Take Array of Data and Create Objects
     *
     * @param	array	$incoming
     * @return	void
     */
    public function request($incoming)
    {
        if ( ! is_array($incoming))
        {
            // Send Error
            return;
        }

        $this->data = array();

        foreach ($incoming as $key => $value)
        {
            $this->data[$key] = $this->values_parsing($value);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set Debug
     *
     * @param	bool	$flag
     * @return	void
     */
    public function set_debug($flag = TRUE)
    {
        $this->debug = ($flag === TRUE);
    }

    // --------------------------------------------------------------------

    /**
     * Values Parsing
     *
     * @param	mixed	$value
     * @return	object
     */
    public function values_parsing($value)
    {
        if (is_array($value) && array_key_exists(0, $value))
        {
            if ( ! isset($value[1], $this->xmlrpcTypes[$value[1]]))
            {
                $temp = new XmlRpcValues($value[0], (is_array($value[0]) ? 'array' : 'string'));
            }
            else
            {
                if (is_array($value[0]) && ($value[1] === 'struct' OR $value[1] === 'array'))
                {
                    foreach (array_keys($value[0]) as $k)
                    {
                        $value[0][$k] = $this->values_parsing($value[0][$k]);
                    }
                }

                $temp = new XmlRpcValues($value[0], $value[1]);
            }
        }
        else
        {
            $temp = new XmlRpcValues($value, 'string');
        }

        return $temp;
    }

    // --------------------------------------------------------------------

    /**
     * Sends XML-RPC Request
     *
     * @return	bool
     */
    public function send_request()
    {
        $this->message = new XmlRpcMessage($this->method, $this->data);
        $this->message->debug = $this->debug;

        if ( ! $this->result = $this->client->send($this->message) OR ! is_object($this->result->val))
        {
            $this->error = $this->result->errstr;
            return FALSE;
        }

        $this->response = $this->result->decode();
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Returns Error
     *
     * @return	string
     */
    public function display_error()
    {
        return $this->error;
    }

    // --------------------------------------------------------------------

    /**
     * Returns Remote Server Response
     *
     * @return	string
     */
    public function display_response()
    {
        return $this->response;
    }

    // --------------------------------------------------------------------

    /**
     * Sends an Error Message for Server Request
     *
     * @param	int	$number
     * @param	string	$message
     * @return	object
     */
    public function send_error_message($number, $message)
    {
        return new XmlRpcResponse(0, $number, $message);
    }

    // --------------------------------------------------------------------

    /**
     * Send Response for Server Request
     *
     * @param	array	$response
     * @return	object
     */
    public function send_response($response)
    {
        // $response should be array of values, which will be parsed
        // based on their data and type into a valid group of XML-RPC values
        return new XmlRpcResponse($this->values_parsing($response));
    }

    /**
     * version 
     * @return string
     */ 
    public function version() {
        return self::VERSION;
    }
}
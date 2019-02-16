<?php
/**
 * Created by PhpStorm.
 * User: bingcool
 * Date: 2019/2/16
 * Time: 11:49
 */
namespace app\rpcxml;

class XmlRpcResponse {
    /**
     * Value
     *
     * @var	mixed
     */
    public $val		= 0;

    /**
     * Error number
     *
     * @var	int
     */
    public $errno		= 0;

    /**
     * Error message
     *
     * @var	string
     */
    public $errstr		= '';

    /**
     * Headers list
     *
     * @var	array
     */
    public $headers		= array();

    /**
     * XSS Filter flag
     *
     * @var	bool
     */
    public $xss_clean	= TRUE;

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @param	mixed	$val
     * @param	int	$code
     * @param	string	$fstr
     * @return	void
     */
    public function __construct($val, $code = 0, $fstr = '')
    {
        if ($code !== 0)
        {
            // error
            $this->errno = $code;
            $this->errstr = htmlspecialchars($fstr, ENT_NOQUOTES, 'UTF-8');
        }
        elseif ( ! is_object($val))
        {
            // programmer error, not an object
            $this->val = new XmlRpcValues();
        }
        else
        {
            $this->val = $val;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Fault code
     *
     * @return	int
     */
    public function faultCode()
    {
        return $this->errno;
    }

    // --------------------------------------------------------------------

    /**
     * Fault string
     *
     * @return	string
     */
    public function faultString()
    {
        return $this->errstr;
    }

    // --------------------------------------------------------------------

    /**
     * Value
     *
     * @return	mixed
     */
    public function value()
    {
        return $this->val;
    }

    // --------------------------------------------------------------------

    /**
     * Prepare response
     *
     * @return	string	xml
     */
    public function prepare_response()
    {
        return "<methodResponse>\n"
            .($this->errno
                ? '<fault>
	<value>
		<struct>
			<member>
				<name>faultCode</name>
				<value><int>'.$this->errno.'</int></value>
			</member>
			<member>
				<name>faultString</name>
				<value><string>'.$this->errstr.'</string></value>
			</member>
		</struct>
	</value>
</fault>'
                : "<params>\n<param>\n".$this->val->serialize_class()."</param>\n</params>")
            ."\n</methodResponse>";
    }

    // --------------------------------------------------------------------

    /**
     * Decode
     *
     * @param	mixed	$array
     * @return	array
     */
    public function decode($array = NULL)
    {
        if (is_array($array))
        {
            foreach ($array as $key => &$value)
            {
                if (is_array($value))
                {
                    $array[$key] = $this->decode($value);
                }
            }

            return $array;
        }

        $result = $this->xmlrpc_decoder($this->val);

        if (is_array($result))
        {
            $result = $this->decode($result);
        }
        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * XML-RPC Object to PHP Types
     *
     * @param	object
     * @return	array
     */
    public function xmlrpc_decoder($xmlrpc_val)
    {
        $kind = $xmlrpc_val->kindOf();

        if ($kind === 'scalar')
        {
            return $xmlrpc_val->scalarval();
        }
        elseif ($kind === 'array')
        {
            reset($xmlrpc_val->me);
            $b = current($xmlrpc_val->me);
            $arr = array();

            for ($i = 0, $size = count($b); $i < $size; $i++)
            {
                $arr[] = $this->xmlrpc_decoder($xmlrpc_val->me['array'][$i]);
            }
            return $arr;
        }
        elseif ($kind === 'struct')
        {
            reset($xmlrpc_val->me['struct']);
            $arr = array();

            foreach ($xmlrpc_val->me['struct'] as $key => &$value)
            {
                $arr[$key] = $this->xmlrpc_decoder($value);
            }

            return $arr;
        }
    }

    // --------------------------------------------------------------------

    /**
     * ISO-8601 time to server or UTC time
     *
     * @param	string
     * @param	bool
     * @return	int	unix timestamp
     */
    public function iso8601_decode($time, $utc = FALSE)
    {
        // Return a time in the localtime, or UTC
        $t = 0;
        if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})/', $time, $regs))
        {
            $fnc = ($utc === TRUE) ? 'gmmktime' : 'mktime';
            $t = $fnc($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
        }
        return $t;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: bingcool
 * Date: 2019/2/16
 * Time: 11:52
 */
namespace app\rpcxml;

class XmlRpcMessage extends XmlRpc {
    /**
     * Payload
     *
     * @var	string
     */
    public $payload;

    /**
     * Method name
     *
     * @var	string
     */
    public $method_name;

    /**
     * Parameter list
     *
     * @var	array
     */
    public $params		= array();

    /**
     * XH?
     *
     * @var	array
     */
    public $xh		= array();

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @param	string	$method
     * @param	array	$pars
     * @return	void
     */
    public function __construct($method, $pars = FALSE)
    {
        parent::__construct();

        $this->method_name = $method;
        if (is_array($pars) && count($pars) > 0)
        {
            for ($i = 0, $c = count($pars); $i < $c; $i++)
            {
                // $pars[$i] = XML_RPC_Values
                $this->params[] = $pars[$i];
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Create Payload to Send
     *
     * @return	void
     */
    public function createPayload()
    {
        $this->payload = '<?xml version="1.0"?'.">\r\n<methodCall>\r\n"
            .'<methodName>'.$this->method_name."</methodName>\r\n"
            ."<params>\r\n";

        for ($i = 0, $c = count($this->params); $i < $c; $i++)
        {
            // $p = XML_RPC_Values
            $p = $this->params[$i];
            $this->payload .= "<param>\r\n".$p->serialize_class()."</param>\r\n";
        }

        $this->payload .= "</params>\r\n</methodCall>\r\n";
    }

    // --------------------------------------------------------------------

    /**
     * Parse External XML-RPC Server's Response
     *
     * @param	resource
     * @return	object
     */
    public function parseResponse($fp)
    {
        $data = '';

        while ($datum = fread($fp, 4096))
        {
            $data .= $datum;
        }

        // Display HTTP content for debugging
        if ($this->debug === TRUE)
        {
            echo "<pre>---DATA---\n".htmlspecialchars($data)."\n---END DATA---\n\n</pre>";
        }

        // Check for data
        if ($data === '')
        {
            return new XmlRpcResponse(0, $this->xmlrpcerr['no_data'], $this->xmlrpcstr['no_data']);
        }

        // Check for HTTP 200 Response
        if (strpos($data, 'HTTP') === 0 && ! preg_match('/^HTTP\/[0-9\.]+ 200 /', $data))
        {
            $errstr = substr($data, 0, strpos($data, "\n")-1);
            return new XmlRpcResponse(0, $this->xmlrpcerr['http_error'], $this->xmlrpcstr['http_error'].' ('.$errstr.')');
        }

        //-------------------------------------
        // Create and Set Up XML Parser
        //-------------------------------------

        $parser = xml_parser_create($this->xmlrpc_defencoding);
        $pname = (string) $parser;
        $this->xh[$pname] = array(
            'isf'		=> 0,
            'ac'		=> '',
            'headers'	=> array(),
            'stack'		=> array(),
            'valuestack'	=> array(),
            'isf_reason'	=> 0
        );

        xml_set_object($parser, $this);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, TRUE);
        xml_set_element_handler($parser, 'open_tag', 'closing_tag');
        xml_set_character_data_handler($parser, 'character_data');
        //xml_set_default_handler($parser, 'default_handler');

        // Get headers
        $lines = explode("\r\n", $data);
        while (($line = array_shift($lines)))
        {
            if (strlen($line) < 1)
            {
                break;
            }
            $this->xh[$pname]['headers'][] = $line;
        }
        $data = implode("\r\n", $lines);

        // Parse XML data
        if ( ! xml_parse($parser, $data, TRUE))
        {
            $errstr = sprintf('XML error: %s at line %d',
                xml_error_string(xml_get_error_code($parser)),
                xml_get_current_line_number($parser));

            $r = new XmlRpcResponse(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return']);
            xml_parser_free($parser);
            return $r;
        }
        xml_parser_free($parser);

        // Got ourselves some badness, it seems
        if ($this->xh[$pname]['isf'] > 1)
        {
            if ($this->debug === TRUE)
            {
                echo "---Invalid Return---\n".$this->xh[$pname]['isf_reason']."---Invalid Return---\n\n";
            }

            return new XmlRpcResponse(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return'].' '.$this->xh[$pname]['isf_reason']);
        }
        elseif ( ! is_object($this->xh[$pname]['value']))
        {
            return new XMLRPCResponse(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return'].' '.$this->xh[$pname]['isf_reason']);
        }

        // Display XML content for debugging
        if ($this->debug === TRUE)
        {
            echo '<pre>';

            if (count($this->xh[$pname]['headers']) > 0)
            {
                echo "---HEADERS---\n";
                foreach ($this->xh[$pname]['headers'] as $header)
                {
                    echo $header."\n";
                }
                echo "---END HEADERS---\n\n";
            }

            echo "---DATA---\n".htmlspecialchars($data)."\n---END DATA---\n\n---PARSED---\n";
            var_dump($this->xh[$pname]['value']);
            echo "\n---END PARSED---</pre>";
        }

        // Send response
        $v = $this->xh[$pname]['value'];
        if ($this->xh[$pname]['isf'])
        {
            $errno_v = $v->me['struct']['faultCode'];
            $errstr_v = $v->me['struct']['faultString'];
            $errno = $errno_v->scalarval();

            if ($errno === 0)
            {
                // FAULT returned, errno needs to reflect that
                $errno = -1;
            }

            $r = new XmlRpcResponse($v, $errno, $errstr_v->scalarval());
        }
        else
        {
            $r = new XmlRpcResponse($v);
        }

        $r->headers = $this->xh[$pname]['headers'];
        return $r;
    }

    // --------------------------------------------------------------------

    // ------------------------------------
    //  Begin Return Message Parsing section
    // ------------------------------------

    // quick explanation of components:
    //   ac - used to accumulate values
    //   isf - used to indicate a fault
    //   lv - used to indicate "looking for a value": implements
    //		the logic to allow values with no types to be strings
    //   params - used to store parameters in method calls
    //   method - used to store method name
    //	 stack - array with parent tree of the xml element,
    //			 used to validate the nesting of elements

    // --------------------------------------------------------------------

    /**
     * Start Element Handler
     *
     * @param	string
     * @param	string
     * @return	void
     */
    public function open_tag($the_parser, $name)
    {
        $the_parser = (string) $the_parser;

        // If invalid nesting, then return
        if ($this->xh[$the_parser]['isf'] > 1) return;

        // Evaluate and check for correct nesting of XML elements
        if (count($this->xh[$the_parser]['stack']) === 0)
        {
            if ($name !== 'METHODRESPONSE' && $name !== 'METHODCALL')
            {
                $this->xh[$the_parser]['isf'] = 2;
                $this->xh[$the_parser]['isf_reason'] = 'Top level XML-RPC element is missing';
                return;
            }
        }
        // not top level element: see if parent is OK
        elseif ( ! in_array($this->xh[$the_parser]['stack'][0], $this->valid_parents[$name], TRUE))
        {
            $this->xh[$the_parser]['isf'] = 2;
            $this->xh[$the_parser]['isf_reason'] = 'XML-RPC element '.$name.' cannot be child of '.$this->xh[$the_parser]['stack'][0];
            return;
        }

        switch ($name)
        {
            case 'STRUCT':
            case 'ARRAY':
                // Creates array for child elements
                $cur_val = array('value' => array(), 'type' => $name);
                array_unshift($this->xh[$the_parser]['valuestack'], $cur_val);
                break;
            case 'METHODNAME':
            case 'NAME':
                $this->xh[$the_parser]['ac'] = '';
                break;
            case 'FAULT':
                $this->xh[$the_parser]['isf'] = 1;
                break;
            case 'PARAM':
                $this->xh[$the_parser]['value'] = NULL;
                break;
            case 'VALUE':
                $this->xh[$the_parser]['vt'] = 'value';
                $this->xh[$the_parser]['ac'] = '';
                $this->xh[$the_parser]['lv'] = 1;
                break;
            case 'I4':
            case 'INT':
            case 'STRING':
            case 'BOOLEAN':
            case 'DOUBLE':
            case 'DATETIME.ISO8601':
            case 'BASE64':
                if ($this->xh[$the_parser]['vt'] !== 'value')
                {
                    //two data elements inside a value: an error occurred!
                    $this->xh[$the_parser]['isf'] = 2;
                    $this->xh[$the_parser]['isf_reason'] = 'There is a '.$name.' element following a '
                        .$this->xh[$the_parser]['vt'].' element inside a single value';
                    return;
                }

                $this->xh[$the_parser]['ac'] = '';
                break;
            case 'MEMBER':
                // Set name of <member> to nothing to prevent errors later if no <name> is found
                $this->xh[$the_parser]['valuestack'][0]['name'] = '';

                // Set NULL value to check to see if value passed for this param/member
                $this->xh[$the_parser]['value'] = NULL;
                break;
            case 'DATA':
            case 'METHODCALL':
            case 'METHODRESPONSE':
            case 'PARAMS':
                // valid elements that add little to processing
                break;
            default:
                /// An Invalid Element is Found, so we have trouble
                $this->xh[$the_parser]['isf'] = 2;
                $this->xh[$the_parser]['isf_reason'] = 'Invalid XML-RPC element found: '.$name;
                break;
        }

        // Add current element name to stack, to allow validation of nesting
        array_unshift($this->xh[$the_parser]['stack'], $name);

        $name === 'VALUE' OR $this->xh[$the_parser]['lv'] = 0;
    }

    // --------------------------------------------------------------------

    /**
     * End Element Handler
     *
     * @param	string
     * @param	string
     * @return	void
     */
    public function closing_tag($the_parser, $name)
    {
        $the_parser = (string) $the_parser;

        if ($this->xh[$the_parser]['isf'] > 1) return;

        // Remove current element from stack and set variable
        // NOTE: If the XML validates, then we do not have to worry about
        // the opening and closing of elements. Nesting is checked on the opening
        // tag so we be safe there as well.

        $curr_elem = array_shift($this->xh[$the_parser]['stack']);

        switch ($name)
        {
            case 'STRUCT':
            case 'ARRAY':
                $cur_val = array_shift($this->xh[$the_parser]['valuestack']);
                $this->xh[$the_parser]['value'] = isset($cur_val['values']) ? $cur_val['values'] : array();
                $this->xh[$the_parser]['vt']	= strtolower($name);
                break;
            case 'NAME':
                $this->xh[$the_parser]['valuestack'][0]['name'] = $this->xh[$the_parser]['ac'];
                break;
            case 'BOOLEAN':
            case 'I4':
            case 'INT':
            case 'STRING':
            case 'DOUBLE':
            case 'DATETIME.ISO8601':
            case 'BASE64':
                $this->xh[$the_parser]['vt'] = strtolower($name);

                if ($name === 'STRING')
                {
                    $this->xh[$the_parser]['value'] = $this->xh[$the_parser]['ac'];
                }
                elseif ($name === 'DATETIME.ISO8601')
                {
                    $this->xh[$the_parser]['vt']	= $this->xmlrpcDateTime;
                    $this->xh[$the_parser]['value'] = $this->xh[$the_parser]['ac'];
                }
                elseif ($name === 'BASE64')
                {
                    $this->xh[$the_parser]['value'] = base64_decode($this->xh[$the_parser]['ac']);
                }
                elseif ($name === 'BOOLEAN')
                {
                    // Translated BOOLEAN values to TRUE AND FALSE
                    $this->xh[$the_parser]['value'] = (bool) $this->xh[$the_parser]['ac'];
                }
                elseif ($name=='DOUBLE')
                {
                    // we have a DOUBLE
                    // we must check that only 0123456789-.<space> are characters here
                    $this->xh[$the_parser]['value'] = preg_match('/^[+-]?[eE0-9\t \.]+$/', $this->xh[$the_parser]['ac'])
                        ? (float) $this->xh[$the_parser]['ac']
                        : 'ERROR_NON_NUMERIC_FOUND';
                }
                else
                {
                    // we have an I4/INT
                    // we must check that only 0123456789-<space> are characters here
                    $this->xh[$the_parser]['value'] = preg_match('/^[+-]?[0-9\t ]+$/', $this->xh[$the_parser]['ac'])
                        ? (int) $this->xh[$the_parser]['ac']
                        : 'ERROR_NON_NUMERIC_FOUND';
                }
                $this->xh[$the_parser]['ac'] = '';
                $this->xh[$the_parser]['lv'] = 3; // indicate we've found a value
                break;
            case 'VALUE':
                // This if() detects if no scalar was inside <VALUE></VALUE>
                if ($this->xh[$the_parser]['vt'] == 'value')
                {
                    $this->xh[$the_parser]['value']	= $this->xh[$the_parser]['ac'];
                    $this->xh[$the_parser]['vt']	= $this->xmlrpcString;
                }

                // build the XML-RPC value out of the data received, and substitute it
                $temp = new XmlRpcValues($this->xh[$the_parser]['value'], $this->xh[$the_parser]['vt']);

                if (count($this->xh[$the_parser]['valuestack']) && $this->xh[$the_parser]['valuestack'][0]['type'] === 'ARRAY')
                {
                    // Array
                    $this->xh[$the_parser]['valuestack'][0]['values'][] = $temp;
                }
                else
                {
                    // Struct
                    $this->xh[$the_parser]['value'] = $temp;
                }
                break;
            case 'MEMBER':
                $this->xh[$the_parser]['ac'] = '';

                // If value add to array in the stack for the last element built
                if ($this->xh[$the_parser]['value'])
                {
                    $this->xh[$the_parser]['valuestack'][0]['values'][$this->xh[$the_parser]['valuestack'][0]['name']] = $this->xh[$the_parser]['value'];
                }
                break;
            case 'DATA':
                $this->xh[$the_parser]['ac'] = '';
                break;
            case 'PARAM':
                if ($this->xh[$the_parser]['value'])
                {
                    $this->xh[$the_parser]['params'][] = $this->xh[$the_parser]['value'];
                }
                break;
            case 'METHODNAME':
                $this->xh[$the_parser]['method'] = ltrim($this->xh[$the_parser]['ac']);
                break;
            case 'PARAMS':
            case 'FAULT':
            case 'METHODCALL':
            case 'METHORESPONSE':
                // We're all good kids with nuthin' to do
                break;
            default:
                // End of an Invalid Element. Taken care of during the opening tag though
                break;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Parse character data
     *
     * @param	string
     * @param	string
     * @return	void
     */
    public function character_data($the_parser, $data)
    {
        $the_parser = (string) $the_parser;

        if ($this->xh[$the_parser]['isf'] > 1) return; // XML Fault found already

        // If a value has not been found
        if ($this->xh[$the_parser]['lv'] !== 3)
        {
            if ($this->xh[$the_parser]['lv'] === 1)
            {
                $this->xh[$the_parser]['lv'] = 2; // Found a value
            }

            if ( ! isset($this->xh[$the_parser]['ac']))
            {
                $this->xh[$the_parser]['ac'] = '';
            }

            $this->xh[$the_parser]['ac'] .= $data;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Add parameter
     *
     * @param	mixed
     * @return	void
     */
    public function addParam($par)
    {
        $this->params[] = $par;
    }

    // --------------------------------------------------------------------

    /**
     * Output parameters
     *
     * @param	array	$array
     * @return	array
     */
    public function output_parameters(array $array = array())
    {

        if ( ! empty($array))
        {
            foreach ($array as $key => &$value)
            {
                if (is_array($value))
                {
                    $array[$key] = $this->output_parameters($value);
                }
            }

            return $array;
        }

        $parameters = array();

        for ($i = 0, $c = count($this->params); $i < $c; $i++)
        {
            $a_param = $this->decode_message($this->params[$i]);

            if (is_array($a_param))
            {
                $parameters[] = $this->output_parameters($a_param);
            }
            else
            {
                $parameters[] = $a_param;
            }
        }

        return $parameters;
    }

    // --------------------------------------------------------------------

    /**
     * Decode message
     *
     * @param	object
     * @return	mixed
     */
    public function decode_message($param)
    {
        $kind = $param->kindOf();

        if ($kind === 'scalar')
        {
            return $param->scalarval();
        }
        elseif ($kind === 'array')
        {
            reset($param->me);
            $b = current($param->me);
            $arr = array();

            for ($i = 0, $c = count($b); $i < $c; $i++)
            {
                $arr[] = $this->decode_message($param->me['array'][$i]);
            }

            return $arr;
        }
        elseif ($kind === 'struct')
        {
            reset($param->me['struct']);
            $arr = array();

            foreach ($param->me['struct'] as $key => &$value)
            {
                $arr[$key] = $this->decode_message($value);
            }

            return $arr;
        }
    }
}
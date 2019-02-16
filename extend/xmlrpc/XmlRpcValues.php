<?php
/**
 * Created by PhpStorm.
 * User: bingcool
 * Date: 2019/2/16
 * Time: 12:02
 */
namespace xmlrpc;

class XmlRpcValues extends XmlRpc {
    /**
     * Value data
     *
     * @var	array
     */
    public $me	= array();

    /**
     * Value type
     *
     * @var	int
     */
    public $mytype	= 0;

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @param	mixed	$val
     * @param	string	$type
     * @return	void
     */
    public function __construct($val = -1, $type = '')
    {
        parent::__construct();

        if ($val !== -1 OR $type !== '')
        {
            $type = $type === '' ? 'string' : $type;

            if ($this->xmlrpcTypes[$type] == 1)
            {
                $this->addScalar($val, $type);
            }
            elseif ($this->xmlrpcTypes[$type] == 2)
            {
                $this->addArray($val);
            }
            elseif ($this->xmlrpcTypes[$type] == 3)
            {
                $this->addStruct($val);
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Add scalar value
     *
     * @param	scalar
     * @param	string
     * @return	int
     */
    public function addScalar($val, $type = 'string')
    {
        $typeof = $this->xmlrpcTypes[$type];

        if ($this->mytype === 1)
        {
            echo '<strong>XML_RPC_Values</strong>: scalar can have only one value<br />';
            return 0;
        }

        if ($typeof != 1)
        {
            echo '<strong>XML_RPC_Values</strong>: not a scalar type (${typeof})<br />';
            return 0;
        }

        if ($type === $this->xmlrpcBoolean)
        {
            $val = (int) (strcasecmp($val, 'true') === 0 OR $val === 1 OR ($val === TRUE && strcasecmp($val, 'false')));
        }

        if ($this->mytype === 2)
        {
            // adding to an array here
            $ar = $this->me['array'];
            $ar[] = new XmlRpcValues($val, $type);
            $this->me['array'] = $ar;
        }
        else
        {
            // a scalar, so set the value and remember we're scalar
            $this->me[$type] = $val;
            $this->mytype = $typeof;
        }

        return 1;
    }

    // --------------------------------------------------------------------

    /**
     * Add array value
     *
     * @param	array
     * @return	int
     */
    public function addArray($vals)
    {
        if ($this->mytype !== 0)
        {
            echo '<strong>XML_RPC_Values</strong>: already initialized as a ['.$this->kindOf().']<br />';
            return 0;
        }

        $this->mytype = $this->xmlrpcTypes['array'];
        $this->me['array'] = $vals;
        return 1;
    }

    // --------------------------------------------------------------------

    /**
     * Add struct value
     *
     * @param	object
     * @return	int
     */
    public function addStruct($vals)
    {
        if ($this->mytype !== 0)
        {
            echo '<strong>XML_RPC_Values</strong>: already initialized as a ['.$this->kindOf().']<br />';
            return 0;
        }
        $this->mytype = $this->xmlrpcTypes['struct'];
        $this->me['struct'] = $vals;
        return 1;
    }

    // --------------------------------------------------------------------

    /**
     * Get value type
     *
     * @return	string
     */
    public function kindOf()
    {
        switch ($this->mytype)
        {
            case 3: return 'struct';
            case 2: return 'array';
            case 1: return 'scalar';
            default: return 'undef';
        }
    }

    // --------------------------------------------------------------------

    /**
     * Serialize data
     *
     * @param	string
     * @param	mixed
     * @return	string
     */
    public function serializedata($typ, $val)
    {
        $rs = '';

        switch ($this->xmlrpcTypes[$typ])
        {
            case 3:
                // struct
                $rs .= "<struct>\n";
                reset($val);
                foreach ($val as $key2 => &$val2)
                {
                    $rs .= "<member>\n<name>{$key2}</name>\n".$this->serializeval($val2)."</member>\n";
                }
                $rs .= '</struct>';
                break;
            case 2:
                // array
                $rs .= "<array>\n<data>\n";
                for ($i = 0, $c = count($val); $i < $c; $i++)
                {
                    $rs .= $this->serializeval($val[$i]);
                }
                $rs .= "</data>\n</array>\n";
                break;
            case 1:
                // others
                switch ($typ)
                {
                    case $this->xmlrpcBase64:
                        $rs .= '<'.$typ.'>'.base64_encode( (string) $val).'</'.$typ.">\n";
                        break;
                    case $this->xmlrpcBoolean:
                        $rs .= '<'.$typ.'>'.( (bool) $val ? '1' : '0').'</'.$typ.">\n";
                        break;
                    case $this->xmlrpcString:
                        $rs .= '<'.$typ.'>'.htmlspecialchars( (string) $val).'</'.$typ.">\n";
                        break;
                    default:
                        $rs .= '<'.$typ.'>'.$val.'</'.$typ.">\n";
                        break;
                }
            default:
                break;
        }

        return $rs;
    }

    // --------------------------------------------------------------------

    /**
     * Serialize class
     *
     * @return	string
     */
    public function serialize_class()
    {
        return $this->serializeval($this);
    }

    // --------------------------------------------------------------------

    /**
     * Serialize value
     *
     * @param	object
     * @return	string
     */
    public function serializeval($o)
    {
        $array = $o->me;
        list($value, $type) = array(reset($array), key($array));
        return "<value>\n".$this->serializedata($type, $value)."</value>\n";
    }

    // --------------------------------------------------------------------

    /**
     * Scalar value
     *
     * @return	mixed
     */
    public function scalarval()
    {
        return reset($this->me);
    }

    // --------------------------------------------------------------------

    /**
     * Encode time in ISO-8601 form.
     * Useful for sending time in XML-RPC
     *
     * @param	int	unix timestamp
     * @param	bool
     * @return	string
     */
    public function iso8601_encode($time, $utc = FALSE)
    {
        return ($utc) ? strftime('%Y%m%dT%H:%i:%s', $time) : gmstrftime('%Y%m%dT%H:%i:%s', $time);
    }
}
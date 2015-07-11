<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Config;

/**
 * Config class
 *
 * @category   Pop
 * @package    Pop_Config
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0
 */
class Config implements \ArrayAccess
{
    /**
     * Flag for whether or not changes are allowed after object instantiation
     * @var boolean
     */
    protected $allowChanges = false;

    /**
     * Config values as config objects
     * @var array
     */
    protected $data = [];

    /**
     * Config values as an array
     * @var array
     */
    protected $array = [];

    /**
     * Constructor
     *
     * Instantiate a config object
     *
     * @param  mixed   $data
     * @param  boolean $changes
     * @return Config
     */
    public function __construct($data, $changes = false)
    {
        $this->allowChanges = (bool)$changes;
        $this->setConfig($data);
    }

    /**
     * Merge the values of another config object into this one
     *
     * @param  mixed $data
     * @throws Exception
     * @return Config
     */
    public function merge($data)
    {
        // If data is a file
        if (!is_array($data) && !($data instanceof Config) && file_exists($data)) {
            $data = $this->parseConfig($data);
            if (!is_array($data)) {
                throw new Exception('Error: Unable to parse the config data.');
            }
        }

        $orig  = $this->toArray();
        $merge = ($data instanceof Config) ? $data->toArray() : $data;

        $this->setConfig(array_merge_recursive($orig, $merge));
        $this->array = [];

        return $this;
    }

    /**
     * Get the config values as an array or ArrayObject
     *
     * @param  boolean $arrayObject
     * @return array
     */
    public function toArray($arrayObject = false)
    {
        $this->array = ($arrayObject) ? new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS) : [];
        $this->getConfig($arrayObject);
        return $this->array;
    }

    /**
     * Return if changes to the config are allowed.
     *
     * @return boolean
     */
    public function changesAllowed()
    {
        return $this->allowChanges;
    }

    /**
     * Magic get method to return the value of config[$name].
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return (array_key_exists($name, $this->data)) ? $this->data[$name] : null;
    }

    /**
     * Magic set method to set the property to the value of config[$name].
     *
     * @param  string $name
     * @param  mixed $value
     * @throws Exception
     * @return void
     */
    public function __set($name, $value)
    {
        if ($this->allowChanges) {
            $this->data[$name] = (is_array($value) ? new Config($value, $this->allowChanges) : $value);
        } else {
            throw new Exception('Real-time configuration changes are not allowed.');
        }
    }

    /**
     * Return the isset value of config[$name].
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * Unset config[$name].
     *
     * @param  string $name
     * @throws Exception
     * @return void
     */
    public function __unset($name)
    {
        if ($this->allowChanges) {
            unset($this->data[$name]);
        } else {
            throw new Exception('Real-time configuration changes are not allowed.');
        }
    }

    /**
     * ArrayAccess offsetExists
     *
     * @param  mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * ArrayAccess offsetSet
     *
     * @param  mixed $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    /**
     * Set the config values
     *
     * @param  mixed $data
     * @throws Exception
     * @return void
     */
    protected function setConfig($data)
    {
        // If data is a file
        if (!is_array($data) && file_exists($data)) {
            $data = $this->parseConfig($data);
        }

        if (!is_array($data)) {
            throw new Exception('Error: Unable to parse the config data.');
        }
        foreach ($data as $key => $value) {
            $this->data[$key] = (is_array($value) ? new Config($value, $this->allowChanges) : $value);
        }
    }

    /**
     * Get the config values as array
     *
     * @param  boolean $arrayObject
     * @return void
     */
    protected function getConfig($arrayObject = false)
    {
        foreach ($this->data as $key => $value) {
            $this->array[$key] = ($value instanceof Config) ? $value->toArray($arrayObject) : $value;
        }
    }

    /**
     * Parse passed the config values
     *
     * @param  mixed $data
     * @return array
     */
    protected function parseConfig($data)
    {
        // If PHP
        if (((substr($data, -6) == '.phtml') ||
            (substr($data, -5) == '.php3') ||
            (substr($data, -4) == '.php'))) {
            $data = include $data;
        // If JSON
        } else if (substr($data, -5) == '.json') {
            $data = json_decode(file_get_contents($data), true);
        // If INI
        } else if (substr($data, -4) == '.ini') {
            $data = parse_ini_file($data, true);
        // If XML
        } else if (substr($data, -4) == '.xml') {
            $data = (array)simplexml_load_file($data);
        }

        return $data;
    }

}

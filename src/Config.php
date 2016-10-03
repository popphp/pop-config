<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Config implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * Flag for whether or not changes are allowed after object instantiation
     * @var boolean
     */
    protected $allowChanges = false;

    /**
     * Config values
     * @var array
     */
    protected $config = [];

    /**
     * Constructor
     *
     * Instantiate a config object
     *
     * @param  mixed   $config
     * @param  string  $key
     * @param  boolean $changes
     */
    public function __construct($config, $key = null, $changes = false)
    {
        $this->allowChanges = (bool)$changes;
        $this->setConfig($config, $key);
    }

    /**
     * Method to get the count of data in the config
     *
     * @return int
     */
    public function count()
    {
        return count($this->config);
    }

    /**
     * Method to iterate over the config
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->config);
    }

    /**
     * Merge the values of another config object into this one
     *
     * @param  mixed $config
     * @throws Exception
     * @return Config
     */
    public function merge($config)
    {
        // If data is a file
        if (!is_array($config) && !($config instanceof Config) && file_exists($config)) {
            $config = $this->parseConfig($config);
            if (!is_array($config)) {
                throw new Exception('Error: Unable to parse the config data.');
            }
        }

        $orig  = $this->toArray();
        $merge = ($config instanceof Config) ? $config->toArray() : $config;

        $this->setConfig(array_merge_recursive($orig, $merge));

        return $this;
    }

    /**
     * Get the config values as an array
     *
     * @return array
     */
    public function toArray()
    {
        $config = $this->config;

        foreach ($this->config as $key => $value) {
            if ($value instanceof Config) {
                if (!$value->isStringable()) {
                    $config[$key] = $value->toArray();
                } else {
                    $config[$key] = (string)$value;
                }
            }
        }

        return $config;
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
     * Determine if the value of the config object is stringable (non-array/scalar)
     *
     * @return boolean
     */
    public function isStringable()
    {
        return ((string)$this != '');
    }

    /**
     * Magic get method to return the value of config[$name].
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return (isset($this->config[$name])) ? $this->config[$name] : null;
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
        if (!$this->allowChanges) {
            throw new Exception('Real-time configuration changes are not allowed.');
        }

        $this->config[$name] = (!($value instanceof Config)) ? new Config($value, $this->allowChanges) : $value;
    }

    /**
     * Return the isset value of config[$name].
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
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
        if (!$this->allowChanges) {
            throw new Exception('Real-time configuration changes are not allowed.');
        }
        unset($this->config[$name]);
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
     * Attempt to render config as a string
     *
     * @return string
     */
    public function __toString()
    {
        $str = '';

        if (count($this->config) == 1) {
            $value = reset($this->config);
            if (!is_array($value) && !($value instanceof \ArrayObject) && !($value instanceof \ArrayAccess)) {
                $str = (string)$value;
            }
        }
        return $str;
    }

    /**
     * Set the config values
     *
     * @param  mixed  $config
     * @param  string $name
     * @throws Exception
     * @return void
     */
    protected function setConfig($config, $name = null)
    {
        // If data is a file
        if (!is_array($config) && file_exists($config)) {
            $config = $this->parseConfig($config);
        }

        if (is_array($config)) {
            foreach ($config as $key => $value) {
                $this->config[$key] = (!$value instanceof Config) ? new self($value, $key, $this->allowChanges) : $value;
            }
        } else {
            $this->config[$name] = $config;
        }
    }

    /**
     * Parse passed the config values
     *
     * @param  mixed $config
     * @return array
     */
    protected function parseConfig($config)
    {
        // If PHP
        if (((substr($config, -6) == '.phtml') ||
            (substr($config, -5) == '.php3') ||
            (substr($config, -4) == '.php'))) {
            $config = include $config;
        // If JSON
        } else if (substr($config, -5) == '.json') {
            $config = json_decode(file_get_contents($config), true);
        // If INI
        } else if (substr($config, -4) == '.ini') {
            $config = parse_ini_file($config, true);
        // If XML
        } else if (substr($config, -4) == '.xml') {
            $config = (array)simplexml_load_file($config);
        }

        return $config;
    }

}

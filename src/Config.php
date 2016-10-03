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
     * Config value(s)
     * @var array
     */
    protected $configValue = [];

    /**
     * Constructor
     *
     * Instantiate a config object
     *
     * @param  mixed   $configValue
     * @param  boolean $changes
     * @param  string  $name
     */
    public function __construct($configValue, $changes = false, $name = null)
    {
        $this->allowChanges = (bool)$changes;
        $this->setConfigValue($configValue, $name);
    }

    /**
     * Method to get the count of data in the config
     *
     * @return int
     */
    public function count()
    {
        return count($this->configValue);
    }

    /**
     * Method to iterate over the config
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->configValue);
    }

    /**
     * Merge the values of another config object into this one
     *
     * @param  mixed $configValue
     * @throws Exception
     * @return Config
     */
    public function merge($configValue)
    {
        if (!$this->allowChanges) {
            throw new Exception('Real-time configuration changes are not allowed.');
        }

        // If data is a file
        if (!is_array($configValue) && !($configValue instanceof Config) && file_exists($configValue)) {
            $configValue = $this->parseConfigValue($configValue);
        }

        $original  = $this->toArray();
        $mergeWith = ($configValue instanceof Config) ? $configValue->toArray() : $configValue;

        $this->setConfigValue(array_merge_recursive($original, $mergeWith));

        return $this;
    }

    /**
     * Get the config value()s as an array
     *
     * @return array
     */
    public function toArray()
    {
        $config = $this->configValue;

        foreach ($this->configValue as $key => $value) {
            if ($value instanceof Config) {
                if ($value->isScalar()) {
                    $config[$key] = $value->toScalar();
                } else {
                    $config[$key] = $value->toArray();
                }
            }
        }

        return $config;
    }

    /**
     * Attempt to render config as a scalar value
     *
     * @return mixed
     */
    public function toScalar()
    {
        $scalar = null;

        if ((count($this->configValue) == 1) && isset($this->configValue[0]) && !is_array($this->configValue[0]) &&
            !($this->configValue[0] instanceof \ArrayObject) && !($this->configValue[0] instanceof \ArrayAccess)) {
            $scalar = $this->configValue[0];
        }

        return $scalar;
    }

    /**
     * Determine if the value of the config object is scalar
     *
     * @return boolean
     */
    public function isScalar()
    {
        return ((count($this->configValue) == 1) && isset($this->configValue[0]) && !is_array($this->configValue[0]) &&
            !($this->configValue[0] instanceof \ArrayObject) && !($this->configValue[0] instanceof \ArrayAccess));
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
        $result = null;

        if (isset($this->configValue[$name])) {
            $result = (($this->configValue[$name] instanceof Config) && ($this->configValue[$name]->isScalar())) ?
                $this->configValue[$name]->toScalar() : $this->configValue[$name];
        }

        return $result;
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

        $this->configValue[$name] = (!($value instanceof Config)) ? new Config($value, $this->allowChanges) : $value;
    }

    /**
     * Return the isset value of config[$name].
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->configValue[$name]);
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
        unset($this->configValue[$name]);
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

        if ((count($this->configValue) == 1) && isset($this->configValue[0]) && !is_array($this->configValue[0]) &&
            !($this->configValue[0] instanceof \ArrayObject) && !($this->configValue[0] instanceof \ArrayAccess)) {
            $str = (string)$this->configValue[0];
        }
        return $str;
    }

    /**
     * Set the config values
     *
     * @param  mixed  $configValue
     * @param  string $name
     * @throws Exception
     * @return void
     */
    protected function setConfigValue($configValue, $name = null)
    {
        // If data is a file
        if (!is_array($configValue) && file_exists($configValue)) {
            $configValue = $this->parseConfigValue($configValue);
        }

        if (is_array($configValue)) {
            foreach ($configValue as $key => $value) {
                $this->configValue[$key] = (!$value instanceof Config) ? new self($value, $this->allowChanges) : $value;
            }
        } else {
            if (null !== $name) {
                $this->configValue[$name] = $configValue;
            } else {
                $this->configValue[] = $configValue;
            }
        }
    }

    /**
     * Parse passed the config values
     *
     * @param  mixed $configValue
     * @return array
     */
    protected function parseConfigValue($configValue)
    {
        // If PHP
        if (((substr($configValue, -6) == '.phtml') ||
            (substr($configValue, -5) == '.php3') ||
            (substr($configValue, -4) == '.php'))) {
            $configValue = include $configValue;
        // If JSON
        } else if (substr($configValue, -5) == '.json') {
            $configValue = json_decode(file_get_contents($configValue), true);
        // If INI
        } else if (substr($configValue, -4) == '.ini') {
            $configValue = parse_ini_file($configValue, true);
        // If XML
        } else if (substr($configValue, -4) == '.xml') {
            $configValue = (array)simplexml_load_file($configValue);
        }

        return $configValue;
    }

}

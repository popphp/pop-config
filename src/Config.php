<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @package    Pop\Config
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2017 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.1.0
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
    protected $values = null;

    /**
     * Constructor
     *
     * Instantiate a config object
     *
     * @param  mixed   $values
     * @param  boolean $changes
     */
    public function __construct($values = [], $changes = false)
    {
        $this->allowChanges = (bool)$changes;
        $this->setValues($values);
    }

    /**
     * Method to create a config object from parsed data
     *
     * @param  mixed   $data
     * @param  boolean $changes
     * @return self
     */
    public static function createFromData($data, $changes = false)
    {
        return new self(self::parseData($data), $changes);
    }

    /**
     * Method to parse data and return config values
     *
     * @param  mixed $data
     * @return array
     */
    public static function parseData($data)
    {
        // If PHP
        if ((strtolower((substr($data, -6)) == '.phtml') ||
            strtolower((substr($data, -5)) == '.php3') ||
            strtolower((substr($data, -4)) == '.php'))) {
            $values = include $data;
        // If JSON
        } else if (strtolower(substr($data, -5)) == '.json') {
            $values = json_decode(file_get_contents($data), true);
        // If INI
        } else if (strtolower(substr($data, -4)) == '.ini') {
            $values = parse_ini_file($data, true);
        // If XML
        } else if (strtolower(substr($data, -4)) == '.xml') {
            $values = (array)simplexml_load_file($data);
        } else {
            $values = [];
        }

        return $values;
    }

    /**
     * Method to get the count of data in the config
     *
     * @return int
     */
    public function count()
    {
        return count($this->values);
    }

    /**
     * Method to iterate over the config
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->values);
    }

    /**
     * Merge the values of another config object into this one.
     * By default, existing values are overwritten, unless the
     * $preserve flag is set to true.
     *
     * @param  mixed    $values
     * @param  boolean $preserve
     * @throws Exception
     * @return Config
     */
    public function merge($values, $preserve = false)
    {
        if (!$this->allowChanges) {
            throw new Exception('Real-time configuration changes are not allowed.');
        }

        $this->values = ($preserve) ?
            array_merge_recursive($this->values, $values) : array_replace_recursive($this->values, $values);

        return $this;
    }

    /**
     * Merge the values of another config object into this one.
     * By default, existing values are overwritten, unless the
     * $preserve flag is set to true.
     *
     * @param  mixed   $data
     * @param  boolean $preserve
     * @throws Exception
     * @return Config
     */
    public function mergeFromData($data, $preserve = false)
    {
        if (!$this->allowChanges) {
            throw new Exception('Real-time configuration changes are not allowed.');
        }

        return $this->merge(self::parseData($data), $preserve);
    }

    /**
     * Get the config value()s as an array
     *
     * @return array
     */
    public function toArray()
    {
        if ($this->values instanceof self) {
            $values = $this->values->toArray();
        } else if ($this->values instanceof \ArrayObject) {
            $values = (array)$this->values;
        } else if ($this->values instanceof \Traversable) {
            $values = iterator_to_array($this->values);
        } else {
            $values = $this->values;
        }

        foreach ($values as $key => $value) {
            if ($value instanceof Config) {
                $values[$key] = $value->toArray();
            }
        }

        return $values;
    }

    /**
     * Get the config value()s as an array
     *
     * @return \ArrayObject
     */
    public function toArrayObject()
    {
        if ($this->values instanceof self) {
            $values = $this->values->toArray();
        } else if ($this->values instanceof \ArrayObject) {
            $values = (array)$this->values;
        } else if ($this->values instanceof \Traversable) {
            $values = iterator_to_array($this->values);
        } else {
            $values = $this->values;
        }

        foreach ($values as $key => $value) {
            if ($value instanceof Config) {
                $values[$key] = $value->toArray();
            }
        }
        return new \ArrayObject($values, \ArrayObject::ARRAY_AS_PROPS);
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
     * Method to set values of the config object
     *
     * @param  mixed $values
     * @return void
     */
    protected function setValues($values)
    {
        foreach ($values as $key => $value) {
            if (is_array($value) || ($value instanceof \ArrayObject)) {
                $values[$key] = new self($value, $this->allowChanges);
            }
        }
        $this->values = $values;
    }

    /**
     * Magic get method to return the value of values[$name].
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return (isset($this->values[$name])) ? $this->values[$name] : null;
    }

    /**
     * Magic set method to set the property to the value of values[$name].
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

        $this->values[$name] = $value;
    }

    /**
     * Return the isset value of values[$name].
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->values[$name]);
    }

    /**
     * Unset values[$name].
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
        unset($this->values[$name]);
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

}

<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Config;

use Pop\Utils;

/**
 * Config class
 *
 * @category   Pop
 * @package    Pop\Config
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.3.0
 */
class Config extends Utils\ArrayObject
{

    /**
     * Flag for whether or not changes are allowed after object instantiation
     * @var boolean
     */
    protected $allowChanges = false;

    /**
     * Constructor
     *
     * Instantiate a config object
     *
     * @param  mixed   $data
     * @param  boolean $changes
     */
    public function __construct($data = [], $changes = false)
    {
        $this->allowChanges = (bool)$changes;
        parent::__construct($data);
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
            strtolower((substr($data, -4)) == '.php'))) {
            $data = include $data;
        // If JSON
        } else if (strtolower(substr($data, -5)) == '.json') {
            $data = json_decode(file_get_contents($data), true);
        // If INI
        } else if (strtolower(substr($data, -4)) == '.ini') {
            $data = parse_ini_file($data, true);
        // If XML
        } else if (strtolower(substr($data, -4)) == '.xml') {
            $data = (array)simplexml_load_file($data);
        } else {
            $data = [];
        }

        return $data;
    }

    /**
     * Merge the values of another config object into this one.
     * By default, existing values are overwritten, unless the
     * $preserve flag is set to true.
     *
     * @param  mixed    $data
     * @param  boolean $preserve
     * @throws Exception
     * @return Config
     */
    public function merge($data, $preserve = false)
    {
        if (!$this->allowChanges) {
            throw new Exception('Real-time configuration changes are not allowed.');
        }

        $this->data = ($preserve) ?
            array_merge_recursive($this->data, $data) : array_replace_recursive($this->data, $data);

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
     * Write the config data to file
     *
     * @param  string $filename
     * @throws Exception
     * @return void
     */
    public function writeToFile($filename)
    {
        if (strpos($filename, '.') !== false) {
            $ext = strtolower(substr($filename, (strrpos($filename, '.') + 1)));
            switch ($ext) {
                case 'php':
                    $config  = '<?php' . PHP_EOL . PHP_EOL;
                    $config .= 'return ' . var_export($this->toArray(), true) . ';';
                    $config .= PHP_EOL;
                    file_put_contents($filename, $config);
                    break;
                case 'json':
                    file_put_contents($filename, $this->toJson());
                    break;
                case 'ini':
                    file_put_contents($filename, $this->toIni());
                    break;
                case 'xml':
                    file_put_contents($filename, $this->toXml());
                    break;
                default:
                    throw new Exception("Invalid type '" . $ext . "'. Supported config file types are PHP, JSON, INI or XML.");
            }
        }
    }

    /**
     * Get the config values as an array
     *
     * @return \ArrayObject
     */
    public function toArrayObject()
    {
        return new \ArrayObject($this->toArray(), \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get the config values as a JSON string
     *
     * @return string
     */
    public function toJson()
    {
        return $this->jsonSerialize(JSON_PRETTY_PRINT);
    }

    /**
     * Get the config values as an INI string
     *
     * @return string
     */
    public function toIni()
    {
        return $this->arrayToIni($this->toArray());
    }

    /**
     * Get the config values as an XML string
     *
     * @return string
     */
    public function toXml()
    {
        $config = new \SimpleXMLElement('<?xml version="1.0"?><config></config>');
        $this->arrayToXml($this->toArray(), $config);

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML($config->asXML());
        return $dom->saveXML();
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
     * Method to convert array to XML
     *
     * @param  array             $array
     * @param  \SimpleXMLElement $config
     * @return void
     */
    protected function arrayToXml($array, \SimpleXMLElement &$config)
    {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                $subNode = (!is_numeric($key)) ? $config->addChild($key) : $config->addChild('item');
                $this->arrayToXml($value, $subNode);
            } else {
                if (!is_numeric($key)) {
                    $config->addChild($key, htmlspecialchars($value));
                } else {
                    $config->addChild('item', htmlspecialchars($value));
                }
            }
        }
    }

    /**
     * Method to convert array to INI
     *
     * @param  array $array
     * @return string
     */
    protected function arrayToIni(array $array)
    {
        $ini          = '';
        $lastWasArray = false;

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!$lastWasArray) {
                    $ini .= PHP_EOL;
                }
                $ini .= '[' . $key . ']' . PHP_EOL;
                foreach ($value as $k => $v) {
                    if (!is_array($v)) {
                        $ini .= $key .
                            '[' . ((!is_numeric($k)) ? $k : null) . '] = ' .
                            ((!is_numeric($v)) ? '"' . $v . '"' : $v) . PHP_EOL;
                    }
                }
                $ini .= PHP_EOL;
                $lastWasArray = true;
            } else {
                $ini .= $key . " = " . ((!is_numeric($value)) ? '"' . $value . '"' : $value) . PHP_EOL;
                $lastWasArray = false;
            }
        }

        return $ini;
    }

    /**
     * Set a value
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
        parent::__set($name, $value);
    }

    /**
     * Unset a value
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
        parent::__unset($name);
    }

}

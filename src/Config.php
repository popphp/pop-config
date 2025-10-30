<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Config;

use Pop\Utils\ArrayObject;
use SimpleXMLElement;
use DOMDocument;

/**
 * Config class
 *
 * @category   Pop
 * @package    Pop\Config
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    4.0.4
 */
class Config extends ArrayObject
{

    /**
     * Flag for whether changes are allowed after object instantiation
     * @var bool
     */
    protected bool $allowChanges = false;

    /**
     * Constructor
     *
     * Instantiate a config object
     *
     * @param mixed $data
     * @param bool  $changes
     * @throws Exception
     */
    public function __construct(mixed $data = [], bool $changes = false)
    {
        $this->allowChanges = $changes;
        parent::__construct($data);
    }

    /**
     * Method to create a config object from parsed data
     *
     * @param  mixed $data
     * @param  bool  $changes
     * @return self
     */
    public static function createFromData(mixed $data = [], bool $changes = false): Config
    {
        return new self(self::parseData($data), $changes);
    }

    /**
     * Method to parse data and return config values
     *
     * @param  mixed $data
     * @return array
     */
    public static function parseData(mixed $data): array
    {
        // If PHP
        if ((strtolower((substr($data, -6)) == '.phtml') ||
            strtolower((substr($data, -4)) == '.php'))) {
            $data = include $data;
        // If JSON
        } else if (strtolower(substr($data, -5)) == '.json') {
            $data = json_decode(file_get_contents($data), true);
        // If YAML - requires YAML ext
        } else if ((strtolower(substr($data, -5)) == '.yaml') ||
            (strtolower(substr($data, -4)) == '.yml'))  {
            $data = yaml_parse(file_get_contents($data));
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
     * @param  mixed $data
     * @param  bool  $preserve
     * @throws Exception
     * @return Config
     */
    public function merge(mixed $data, bool $preserve = false): Config
    {
        if (!$this->allowChanges) {
            throw new Exception('Real-time configuration changes are not allowed.');
        }

        if ($data instanceof Config) {
            $data = $data->toArray();
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
     * @param  mixed $data
     * @param  bool  $preserve
     * @throws Exception
     * @return Config
     */
    public function mergeFromData(mixed $data, bool $preserve = false): Config
    {
        if (!$this->allowChanges) {
            throw new Exception('Real-time configuration changes are not allowed.');
        }

        return $this->merge(self::parseData($data), $preserve);
    }

    /**
     * Render data to a string format
     *
     * @param  string $format
     * @throws Exception
     * @return string
     */
    public function render(string $format): string
    {
        $config = '';

        switch (strtolower($format)) {
            case 'php':
            case 'phtml':
                $config  = '<?php' . PHP_EOL . PHP_EOL;
                $config .= 'return ' . var_export($this->toArray(), true) . ';';
                $config .= PHP_EOL;
                break;
            case 'json':
                $config = $this->toJson();
                break;
            case 'yml':
            case 'yaml':
                return $this->toYaml();
            case 'ini':
                $config = $this->toIni();
                break;
            case 'xml':
                $config =$this->toXml();
                break;
            default:
                throw new Exception(
                    "Invalid type '" . $format . "'. Supported config file types are PHP, JSON, YAML, INI or XML."
                );
        }

        return $config;
    }

    /**
     * Write the config data to file
     *
     * @param  string $filename
     * @throws Exception
     * @return void
     */
    public function writeToFile(string $filename): void
    {
        if (str_contains($filename, '.')) {
            $ext = strtolower(substr($filename, (strrpos($filename, '.') + 1)));
            file_put_contents($filename, $this->render($ext));
        }
    }

    /**
     * Get the config values as an array
     *
     * @throws \Pop\Utils\Exception
     * @return ArrayObject|\ArrayObject
     */
    public function toArrayObject($native = false): ArrayObject|\ArrayObject
    {
        return ($native) ? new \ArrayObject($this->toArray(), \ArrayObject::ARRAY_AS_PROPS) : new ArrayObject($this->toArray());
    }

    /**
     * Get the config values as a JSON string
     *
     * @return string
     */
    public function toJson(): string
    {
        return $this->jsonSerialize(JSON_PRETTY_PRINT);
    }

    /**
     * Get the config values as an YAML string
     *
     * @return string
     */
    public function toYaml(): string
    {
        return $this->arrayToYaml($this->toArray());
    }

    /**
     * Get the config values as an INI string
     *
     * @return string
     */
    public function toIni(): string
    {
        return $this->arrayToIni($this->toArray());
    }

    /**
     * Get the config values as an XML string
     *
     * @return string
     */
    public function toXml(): string
    {
        $config = new SimpleXMLElement('<?xml version="1.0"?><config></config>');
        $this->arrayToXml($this->toArray(), $config);

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML($config->asXML());
        return $dom->saveXML();
    }

    /**
     * Return if changes to the config are allowed.
     *
     * @return bool
     */
    public function changesAllowed(): bool
    {
        return $this->allowChanges;
    }

    /**
     * Method to convert array to XML
     *
     * @param  array            $array
     * @param  SimpleXMLElement $config
     * @return void
     */
    protected function arrayToXml(array $array, SimpleXMLElement &$config): void
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
     * Method to convert array to Yaml
     *
     * @param  array $array
     * @return string
     */
    protected function arrayToYaml(array $array): string
    {
        return yaml_emit($array);
    }

    /**
     * Method to convert array to INI
     *
     * @param  array $array
     * @return string
     */
    protected function arrayToIni(array $array): string
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
     * @param  ?string $name
     * @param  mixed $value
     * @return static
     */
    public function __set(?string $name = null, mixed $value = null)
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
    public function __unset(string $name): void
    {
        if (!$this->allowChanges) {
            throw new Exception('Real-time configuration changes are not allowed.');
        }
        parent::__unset($name);
    }

}

<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Config;

use Pop\Utils\ArrayObject;
use SimpleXMLElement;
use DOMDocument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException as SymfonyYamlParseException;

/**
 * Config class
 *
 * @category   Pop
 * @package    Pop\Config
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.0.0
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
     * @throws Exception
     * @return array
     */
    public static function parseData(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (!is_string($data)) {
            throw new ParseException('Error: The config data must be a file path string or an array.');
        }

        if (!is_file($data)) {
            throw new ParseException("Error: The config file '" . $data . "' does not exist.");
        }

        // If PHP
        if ((strtolower(substr($data, -6)) == '.phtml') ||
            (strtolower(substr($data, -4)) == '.php')) {
            $result = include $data;
        // If JSON
        } else if (strtolower(substr($data, -5)) == '.json') {
            $result = json_decode(file_get_contents($data), true);
        // If YAML
        } else if ((strtolower(substr($data, -5)) == '.yaml') ||
            (strtolower(substr($data, -4)) == '.yml'))  {
            try {
                $result = Yaml::parseFile($data);
                if (is_array($result)) {
                    $result = self::normalizeYamlScalars($result);
                }
            } catch (SymfonyYamlParseException $e) {
                throw new ParseException("Error: Unable to parse the config data from '" . $data . "'.", 0, $e);
            }
        // If INI
        } else if (strtolower(substr($data, -4)) == '.ini') {
            $result = @parse_ini_file($data, true);
        // If XML
        } else if (strtolower(substr($data, -4)) == '.xml') {
            $result = (array)simplexml_load_file($data);
        } else {
            throw new UnsupportedFormatException(
                "Error: Unable to determine the config format from the file '" . $data . "'. " .
                "Supported extensions are .php, .phtml, .json, .yaml, .yml, .ini and .xml."
            );
        }

        if (!is_array($result)) {
            throw new ParseException("Error: Unable to parse the config data from '" . $data . "'.");
        }

        return $result;
    }

    /**
     * Normalize YAML scalars parsed by symfony/yaml back to the legacy behavior
     * of the PECL yaml extension (libyaml, YAML 1.1), which converted additional
     * boolean words (yes/no/on/off, etc.) and leading-zero octal-looking integers
     * to booleans and integers, respectively. symfony/yaml (YAML 1.2-ish core
     * schema) leaves those as plain strings. This is applied only to the YAML
     * read path, not to writing.
     *
     * Note: ISO date-like scalars are a known, intentionally unfixed gap -
     * symfony/yaml auto-converts them to a Unix timestamp int with no public
     * API to prevent it.
     *
     * @param  mixed $value
     * @return mixed
     */
    protected static function normalizeYamlScalars(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $v) {
                $value[$key] = self::normalizeYamlScalars($v);
            }
            return $value;
        }

        if (is_string($value)) {
            if (preg_match('/^(?:y|Y|yes|Yes|YES|n|N|no|No|NO|on|On|ON|off|Off|OFF)$/', $value)) {
                return in_array($value, ['y', 'Y', 'yes', 'Yes', 'YES', 'on', 'On', 'ON'], true);
            }
            if (preg_match('/^0[0-7]+$/', $value)) {
                return octdec($value);
            }
        }

        return $value;
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
            throw new ChangesNotAllowedException('Real-time configuration changes are not allowed.');
        }

        if ($data instanceof Config) {
            $data = $data->toArray();
        }

        $this->data = ($preserve) ?
            $this->mergeRecursivePreserve($this->data, $data) : array_replace_recursive($this->data, $data);

        return $this;
    }

    /**
     * Recursively merge $new into $original, keeping $original's value whenever
     * a key collides and at least one side isn't an array.
     *
     * @param  array $original
     * @param  array $new
     * @return array
     */
    private function mergeRecursivePreserve(array $original, array $new): array
    {
        foreach ($new as $key => $value) {
            if (!array_key_exists($key, $original)) {
                $original[$key] = $value;
            } else if (is_array($original[$key]) && is_array($value) &&
                !(array_is_list($original[$key]) && array_is_list($value))) {
                $original[$key] = $this->mergeRecursivePreserve($original[$key], $value);
            }
        }

        return $original;
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
            throw new ChangesNotAllowedException('Real-time configuration changes are not allowed.');
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
                throw new UnsupportedFormatException(
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
                    $config->addChild($key, htmlspecialchars((string)$value));
                } else {
                    $config->addChild('item', htmlspecialchars((string)$value));
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
        return Yaml::dump($array, 512);
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
     * @return void
     */
    public function __set(?string $name = null, mixed $value = null): void
    {
        if (!$this->allowChanges) {
            throw new ChangesNotAllowedException('Real-time configuration changes are not allowed.');
        }

        if ($name === null || !str_contains($name, '.') || array_key_exists($name, (array)$this->data)) {
            parent::__set($name, $value);
            return;
        }

        $segments    = explode('.', $name);
        $lastSegment = array_pop($segments);

        $data =& $this->data;
        foreach ($segments as $segment) {
            if (!isset($data[$segment]) || !is_array($data[$segment])) {
                $data[$segment] = [];
            }
            $data =& $data[$segment];
        }

        $data[$lastSegment] = $value;
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
            throw new ChangesNotAllowedException('Real-time configuration changes are not allowed.');
        }

        if (!str_contains($name, '.') || array_key_exists($name, (array)$this->data)) {
            parent::__unset($name);
            return;
        }

        $segments    = explode('.', $name);
        $lastSegment = array_pop($segments);

        $data =& $this->data;
        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return;
            }
            $data =& $data[$segment];
        }

        if (is_array($data) && array_key_exists($lastSegment, $data)) {
            unset($data[$lastSegment]);
        }
    }

    /**
     * Get a value, supporting dot notation for nested keys
     *
     * @param  string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        $data = (array)$this->data;

        if (array_key_exists($name, $data)) {
            return $data[$name];
        }

        if (!str_contains($name, '.')) {
            return null;
        }

        $value = $data;
        foreach (explode('.', $name) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Determine if a value is set, supporting dot notation for nested keys
     *
     * @param  string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        $data = (array)$this->data;

        if (array_key_exists($name, $data)) {
            return true;
        }

        if (!str_contains($name, '.')) {
            return false;
        }

        $value = $data;
        foreach (explode('.', $name) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

}

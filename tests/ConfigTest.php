<?php

namespace Pop\Config\Test;

use Pop\Config\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $config = new Config([
            'foo' => 'bar'
        ]);

        $this->assertInstanceOf('Pop\Config\Config', $config);
        $this->assertEquals('bar', $config->foo);
        $this->assertEquals('bar', $config['foo']);
        $this->assertEquals(1, count($config));
        $this->assertFalse($config->changesAllowed());

        $c = [];

        foreach ($config as $key => $value) {
            $c[$key] = $value;
        }
        $this->assertEquals(1, count($c));
    }

    public function testIterator()
    {
        $config1 = new Config([
            'foo' => 'bar'
        ]);
        $c = [];

        foreach ($config1 as $key => $value) {
            $c[$key] = $value;
        }
        $this->assertEquals(1, count($c));
    }

    public function testToArray()
    {
        $config = new Config(new Config(['foo' => 'bar']));
        $array = $config->toArray();
        $this->assertTrue(is_array($array));
        $this->assertEquals('bar', $array['foo']);

        $config = new Config(new \ArrayObject(['foo' => 'bar']));
        $array = $config->toArray();
        $this->assertTrue(is_array($array));
        $this->assertEquals('bar', $array['foo']);
    }

    public function testSetException()
    {
        $this->expectException('Pop\Config\Exception');
        $config = new Config([
            'foo' => 'bar'
        ]);
        $config->foo = 'baz';
    }

    public function testUnsetException()
    {
        $this->expectException('Pop\Config\Exception');
        $config = new Config([
            'foo' => 'bar'
        ]);
        unset($config->foo);
    }

    public function testSet()
    {
        $config = new Config([
            'foo' => 'bar'
        ], true);
        $config->foo = 'baz';
        $this->assertEquals('baz', $config['foo']);
        $config['foo'] = 'bar';
        $this->assertEquals('bar', $config['foo']);
    }

    public function testUnset()
    {
        $config = new Config([
            'foo' => 'bar'
        ], true);
        unset($config->foo);
        unset($config['foo']);
        $this->assertNull($config->foo);
    }

    public function testIsset()
    {
        $config = new Config([
            'foo' => 'bar'
        ]);
        $this->assertTrue(isset($config->foo));
        $this->assertTrue(isset($config['foo']));
    }

    public function testMerge()
    {
        $config = new Config([
            'foo' => 'bar'
        ], true);
        $config->merge([
            'baz' => 123
        ]);

        $this->assertTrue(isset($config->foo));
        $this->assertTrue(isset($config['baz']));
        $this->assertEquals(123, $config->baz);
    }

    public function testParsePhp()
    {
        $config = Config::createFromData(__DIR__ . '/tmp/config.php');
        $this->assertTrue(isset($config->foo));
        $this->assertEquals('bar', $config->foo);
    }

    public function testParseJson()
    {
        $config = Config::createFromData(__DIR__ . '/tmp/config.json');
        $this->assertTrue(isset($config->foo));
        $this->assertEquals('bar', $config->foo);
    }

    public function testParseIni()
    {
        $config = Config::createFromData(__DIR__ . '/tmp/config.ini');
        $this->assertTrue(isset($config->foo));
        $this->assertEquals('bar', $config->foo);
    }

    public function testParseXml()
    {
        $config = Config::createFromData(__DIR__ . '/tmp/config.xml');
        $this->assertTrue(isset($config->foo));
        $this->assertEquals('bar', $config->foo);
    }

    public function testMergeParse()
    {
        $config = new Config([
            'baz' => 123
        ], true);
        $config->mergeFromData(__DIR__ . '/tmp/config.php');
        $this->assertTrue(isset($config->foo));
        $this->assertTrue(isset($config['baz']));
        $this->assertEquals(123, $config->baz);
    }

    public function testMergeParseException()
    {
        $this->expectException('Pop\Config\Exception');
        $config = new Config([
            'baz' => 123
        ]);
        $config->merge(__DIR__ . '/tmp/baddata');
    }

}
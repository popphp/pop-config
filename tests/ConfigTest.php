<?php

namespace Pop\Config\Test;

use Pop\Config\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $config1 = new Config([
            'foo' => 'bar'
        ]);

        $config2 = new Config(123, true, 'baz');
        $this->assertInstanceOf('Pop\Config\Config', $config1);
        $this->assertEquals('bar', $config1->foo);
        $this->assertEquals('bar', $config1['foo']);
        $this->assertEquals(1, count($config1));
        $this->assertEquals(123, $config2->baz);
        $this->assertFalse($config1->changesAllowed());
        $this->assertTrue($config2->changesAllowed());

        $c = [];

        foreach ($config1 as $key => $value) {
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
        $config = new Config([
            'foo' => 'bar'
        ]);
        $array = $config->toArray();
        $this->assertTrue(is_array($array));
        $this->assertEquals('bar', $array['foo']);
    }

    public function testToScalar()
    {
        $config = new Config('bar');
        $this->assertEquals('bar', $config->toScalar());
    }

    public function testToString()
    {
        $config = new Config('bar');

        ob_start();
        echo $config;
        $result = ob_get_clean();

        $this->assertEquals('bar', $result);
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
        $config = new Config(__DIR__ . '/tmp/config.php');
        $this->assertTrue(isset($config->foo));
        $this->assertEquals('bar', $config->foo);
    }

    public function testParseJson()
    {
        $config = new Config(__DIR__ . '/tmp/config.json');
        $this->assertTrue(isset($config->foo));
        $this->assertEquals('bar', $config->foo);
    }

    public function testParseIni()
    {
        $config = new Config(__DIR__ . '/tmp/config.ini');
        $this->assertTrue(isset($config->foo));
        $this->assertEquals('bar', $config->foo);
    }

    public function testParseXml()
    {
        $config = new Config(__DIR__ . '/tmp/config.xml');
        $this->assertTrue(isset($config->foo));
        $this->assertEquals('bar', $config->foo);
    }

    public function testMergeParse()
    {
        $config = new Config([
            'baz' => 123
        ], true);
        $config->merge(__DIR__ . '/tmp/config.php');
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
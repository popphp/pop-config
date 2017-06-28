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

    public function testWriteToPhp()
    {
        $config = new Config([
            'foo' => 'bar',
            'baz' => [
                'hello' => 'world',
                'yo' => [
                    'whats' => [
                        'up',
                        'dude'
                    ]
                ]
            ]
        ]);
        $config->writeToFile(__DIR__ . '/tmp/write.php');
        $this->assertFileExists(__DIR__ . '/tmp/write.php');
        $this->assertContains("'hello' => 'world',", file_get_contents(__DIR__ . '/tmp/write.php'));
        if (file_exists(__DIR__ . '/tmp/write.php')) {
            unlink(__DIR__ . '/tmp/write.php');
        }
    }

    public function testWriteToJson()
    {
        $config = new Config([
            'foo' => 'bar',
            'baz' => [
                'hello' => 'world',
                'yo' => [
                    'whats' => [
                        'up',
                        'dude'
                    ]
                ]
            ]
        ]);
        $config->writeToFile(__DIR__ . '/tmp/write.json');
        $this->assertFileExists(__DIR__ . '/tmp/write.json');
        $this->assertContains('"foo": "bar",', file_get_contents(__DIR__ . '/tmp/write.json'));
        if (file_exists(__DIR__ . '/tmp/write.json')) {
            unlink(__DIR__ . '/tmp/write.json');
        }
    }

    public function testWriteToIni()
    {
        $ini = parse_ini_string(<<<INI
one = 1
five = 5
animal = "BIRD"
path = "/usr/local/bin"
URL = "http://www.example.com/~username"

[phpversion]
phpversion[] = 5.0
phpversion[] = 5.1
phpversion[] = 5.2
phpversion[] = 5.3

[urls]
urls[svn] = "http://svn.php.net"
urls[git] = "http://git.php.net"
INI
);

        $config = new Config($ini);
        $config->writeToFile(__DIR__ . '/tmp/write.ini');
        $this->assertFileExists(__DIR__ . '/tmp/write.ini');
        $this->assertContains('one = 1', file_get_contents(__DIR__ . '/tmp/write.ini'));
        $this->assertContains('phpversion[] = 5.0', file_get_contents(__DIR__ . '/tmp/write.ini'));
        $this->assertContains('urls[git] = "http://git.php.net"', file_get_contents(__DIR__ . '/tmp/write.ini'));
        if (file_exists(__DIR__ . '/tmp/write.ini')) {
            unlink(__DIR__ . '/tmp/write.ini');
        }
    }

    public function testWriteToXml()
    {
        $config = new Config([
            'foo' => 'bar',
            'baz' => [
                'hello' => 'world',
                'yo' => [
                    'whats' => [
                        'up',
                        'dude'
                    ]
                ]
            ]
        ]);
        $config->writeToFile(__DIR__ . '/tmp/write.xml');
        $this->assertFileExists(__DIR__ . '/tmp/write.xml');
        $this->assertContains('<?xml version="1.0"?>', file_get_contents(__DIR__ . '/tmp/write.xml'));
        $this->assertContains('<config>', file_get_contents(__DIR__ . '/tmp/write.xml'));
        $this->assertContains('<hello>world</hello>', file_get_contents(__DIR__ . '/tmp/write.xml'));
        $this->assertContains('</config>', file_get_contents(__DIR__ . '/tmp/write.xml'));
        if (file_exists(__DIR__ . '/tmp/write.xml')) {
            unlink(__DIR__ . '/tmp/write.xml');
        }
    }

}
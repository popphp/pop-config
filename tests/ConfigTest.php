<?php

namespace Pop\Config\Test;

use Pop\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
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

    public function testToArrayObject()
    {
        $config = new Config(['foo' => 'bar']);
        $array = $config->toArrayObject();
        $this->assertInstanceOf('Pop\Utils\ArrayObject', $array);
        $array = $config->toArrayObject(true);
        $this->assertInstanceOf('ArrayObject', $array);
        $this->assertEquals('bar', $array->foo);

        $config = new Config(new Config(['foo' => 'bar']));
        $array = $config->toArrayObject();
        $this->assertInstanceOf('Pop\Utils\ArrayObject', $array);
        $this->assertEquals('bar', $array->foo);

        $config = new Config(new \ArrayObject(['foo' => 'bar']));
        $array = $config->toArrayObject();
        $this->assertInstanceOf('Pop\Utils\ArrayObject', $array);
        $this->assertEquals('bar', $array->foo);
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

    public function testSetExceptionIsChangesNotAllowedException()
    {
        $this->expectException('Pop\Config\ChangesNotAllowedException');
        $config = new Config([
            'foo' => 'bar'
        ]);
        $config->foo = 'baz';
    }

    public function testChangesNotAllowedExceptionCaughtAsBaseException()
    {
        $config = new Config([
            'foo' => 'bar'
        ]);

        try {
            $config->foo = 'baz';
            $this->fail('Expected exception was not thrown.');
        } catch (\Pop\Config\Exception $e) {
            $this->assertInstanceOf('Pop\Config\ChangesNotAllowedException', $e);
        }
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
        $this->assertFalse(isset($config->missing));
        $this->assertFalse(isset($config['missing']));
    }

    public function testDotNotationGet()
    {
        $config = new Config([
            'database' => [
                'host' => 'localhost',
                'port' => 5432
            ]
        ]);
        $this->assertEquals('localhost', $config['database.host']);
        $this->assertEquals('localhost', $config->{'database.host'});
        $this->assertEquals(5432, $config['database.port']);
    }

    public function testDotNotationGetUnresolvedPathReturnsNull()
    {
        $config = new Config([
            'database' => [
                'host' => 'localhost'
            ]
        ]);
        $this->assertNull($config['database.missing']);
        $this->assertNull($config['missing.path.here']);
        $this->assertNull($config['database.host.too.deep']);
    }

    public function testDotNotationIsset()
    {
        $config = new Config([
            'database' => [
                'host' => 'localhost'
            ]
        ]);
        $this->assertTrue(isset($config['database.host']));
        $this->assertFalse(isset($config['database.missing']));
    }

    public function testDotNotationLiteralKeyWins()
    {
        $config = new Config([
            'example.com' => 'ok',
            'a'           => [
                'b' => 1
            ]
        ]);
        $this->assertEquals('ok', $config['example.com']);
        $this->assertEquals(1, $config['a.b']);
    }

    public function testDotNotationSetAutoVivifies()
    {
        $config = new Config([], true);
        $config['database.host'] = 'localhost';
        $this->assertEquals(['database' => ['host' => 'localhost']], $config->toArray());
    }

    public function testDotNotationSetOverwritesExistingLiteralKey()
    {
        $config = new Config([
            'example.com' => 'old'
        ], true);
        $config['example.com'] = 'new';
        $this->assertEquals(['example.com' => 'new'], $config->toArray());
    }

    public function testDotNotationUnsetRemovesLeafOnly()
    {
        $config = new Config([
            'database' => [
                'host' => 'localhost',
                'port' => 5432
            ]
        ], true);
        unset($config['database.host']);
        $this->assertEquals(['database' => ['port' => 5432]], $config->toArray());
    }

    public function testDotNotationUnsetUnresolvedPathIsNoOp()
    {
        $config = new Config([
            'database' => [
                'host' => 'localhost'
            ]
        ], true);
        unset($config['database.missing.deep']);
        $this->assertEquals(['database' => ['host' => 'localhost']], $config->toArray());
    }

    public function testDotNotationSetNewKeyCreatesNestedStructureNotLiteralKey()
    {
        $config = new Config([], true);
        $config['example.com'] = 'value';
        $this->assertEquals(['example' => ['com' => 'value']], $config->toArray());
    }

    public function testDotNotationSetOverwritesScalarMiddleSegment()
    {
        $config = new Config(['a' => 'scalar'], true);
        $config['a.b'] = 1;
        $this->assertEquals(['a' => ['b' => 1]], $config->toArray());
    }

    public function testMerge()
    {
        $config = new Config([
            'foo' => 'bar'
        ], true);
        $config->merge([
            'baz' => 123
        ]);
        $config->merge(new Config([
            'test' => 456
        ], true));

        $this->assertTrue(isset($config->foo));
        $this->assertTrue(isset($config['baz']));
        $this->assertEquals(123, $config->baz);
        $this->assertEquals(456, $config->test);
    }

    public function testMergePreserveKeepsExistingScalarOnCollision()
    {
        $config = new Config([
            'foo' => 'original'
        ], true);
        $config->merge([
            'foo' => 'incoming',
            'bar' => 'new'
        ], true);
        $this->assertEquals('original', $config->foo);
        $this->assertEquals('new', $config->bar);
    }

    public function testMergePreserveRecursesIntoNestedArrays()
    {
        $config = new Config([
            'db' => [
                'host' => 'localhost',
                'port' => 5432
            ]
        ], true);
        $config->merge([
            'db' => [
                'port' => 9999,
                'name' => 'app'
            ]
        ], true);
        $this->assertEquals([
            'db' => [
                'host' => 'localhost',
                'port' => 5432,
                'name' => 'app'
            ]
        ], $config->toArray());
    }

    public function testMergePreserveKeepsExistingListWholesaleOnListCollision()
    {
        $config = new Config([
            'a' => [1, 2]
        ], true);
        $config->merge([
            'a' => [3, 4, 5]
        ], true);
        $this->assertEquals(['a' => [1, 2]], $config->toArray());
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

    public function testParseYaml()
    {
        $config = Config::createFromData(__DIR__ . '/tmp/config.yml');
        $this->assertTrue(isset($config->invoice));
        $this->assertEquals(34843, $config->invoice);
    }

    public function testParseYamlNormalizesLegacyBooleanAndOctalScalars()
    {
        $config = Config::createFromData(__DIR__ . '/tmp/scalars.yml');
        $this->assertFalse($config->debug);
        $this->assertTrue($config->flag);
        $this->assertEquals(493, $config->octal);
        $this->assertTrue($config->truthy);
        $this->assertFalse($config->falsy);
        $this->assertTrue($config->{'onoff_on'});
        $this->assertFalse($config->{'onoff_off'});
        $this->assertEquals('hello', $config->name);
    }

    public function testNormalizeYamlScalarsDoesNotOverMatch()
    {
        $method = new \ReflectionMethod(Config::class, 'normalizeYamlScalars');
        $this->assertEquals('yesterday', $method->invoke(null, 'yesterday'));
        $this->assertEquals('089', $method->invoke(null, '089'));
        $this->assertEquals(['a' => true, 'b' => 'yesterday'], $method->invoke(null, ['a' => 'yes', 'b' => 'yesterday']));
    }

    public function testParseMalformedYamlException()
    {
        $this->expectException('Pop\Config\ParseException');

        set_error_handler(function ($errno, $errstr) {
            throw new \Exception($errstr);
        });

        try {
            Config::createFromData(__DIR__ . '/tmp/malformed.yml');
        } finally {
            restore_error_handler();
        }
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

    public function testParseUnsupportedFormatException()
    {
        $this->expectException('Pop\Config\UnsupportedFormatException');
        Config::createFromData(__DIR__ . '/tmp/baddata');
    }

    public function testCreateFromDataDefaultIsEmptyConfig()
    {
        $config = Config::createFromData();
        $this->assertEquals(0, count($config->toArray()));
    }

    public function testParseDataArrayPassthrough()
    {
        $data = Config::parseData(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $data);
    }

    public function testParseDataInvalidTypeException()
    {
        $this->expectException('Pop\Config\ParseException');
        Config::parseData(123);
    }

    public function testParseMissingFileException()
    {
        $this->expectException('Pop\Config\ParseException');
        Config::createFromData(__DIR__ . '/tmp/does-not-exist.json');
    }

    public function testParseMalformedJsonException()
    {
        $this->expectException('Pop\Config\ParseException');
        Config::createFromData(__DIR__ . '/tmp/malformed.json');
    }

    public function testParseMalformedIniException()
    {
        $this->expectException('Pop\Config\ParseException');
        Config::createFromData(__DIR__ . '/tmp/malformed.ini');
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

    public function testMergeNoChangesException()
    {
        $this->expectException('Pop\Config\Exception');
        $config = new Config([
            'baz' => 123
        ]);
        $config->mergeFromData(__DIR__ . '/tmp/config.php');
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
        $this->assertStringContainsString("'hello' => 'world',", file_get_contents(__DIR__ . '/tmp/write.php'));
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
        $this->assertStringContainsString('"foo": "bar",', file_get_contents(__DIR__ . '/tmp/write.json'));
        if (file_exists(__DIR__ . '/tmp/write.json')) {
            unlink(__DIR__ . '/tmp/write.json');
        }
    }

    public function testWriteToYaml()
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
        $config->writeToFile(__DIR__ . '/tmp/write.yaml');
        $this->assertFileExists(__DIR__ . '/tmp/write.yaml');
        $this->assertStringContainsString('foo: bar', file_get_contents(__DIR__ . '/tmp/write.yaml'));
        if (file_exists(__DIR__ . '/tmp/write.yaml')) {
            unlink(__DIR__ . '/tmp/write.yaml');
        }
    }

    public function testWriteToYamlUsesBlockStyleForDeepNesting()
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
        $yaml = $config->toYaml();
        $this->assertFalse(str_contains($yaml, '{'));
        $this->assertFalse(str_contains($yaml, '['));
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
        $this->assertStringContainsString('one = 1', file_get_contents(__DIR__ . '/tmp/write.ini'));
        $this->assertStringContainsString('phpversion[] = 5.0', file_get_contents(__DIR__ . '/tmp/write.ini'));
        $this->assertStringContainsString('urls[git] = "http://git.php.net"', file_get_contents(__DIR__ . '/tmp/write.ini'));
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
        $this->assertStringContainsString('<?xml version="1.0"?>', file_get_contents(__DIR__ . '/tmp/write.xml'));
        $this->assertStringContainsString('<config>', file_get_contents(__DIR__ . '/tmp/write.xml'));
        $this->assertStringContainsString('<hello>world</hello>', file_get_contents(__DIR__ . '/tmp/write.xml'));
        $this->assertStringContainsString('</config>', file_get_contents(__DIR__ . '/tmp/write.xml'));
        if (file_exists(__DIR__ . '/tmp/write.xml')) {
            unlink(__DIR__ . '/tmp/write.xml');
        }
    }

    public function testWriteToXmlWithNullValueNoDeprecation()
    {
        $config = new Config([
            'foo'   => 'bar',
            'empty' => null
        ]);

        set_error_handler(function ($errno, $errstr) {
            throw new \Exception($errstr);
        });

        try {
            $xml = $config->render('xml');
        } finally {
            restore_error_handler();
        }

        $this->assertStringContainsString('<empty/>', $xml);
    }

    public function testWriteException()
    {
        $this->expectException('Pop\Config\UnsupportedFormatException');
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
        $config->writeToFile(__DIR__ . '/tmp/write.bad');
    }

    public function testRenderUnsupportedFormatException()
    {
        $this->expectException('Pop\Config\UnsupportedFormatException');
        $config = new Config([
            'foo' => 'bar'
        ]);
        $config->render('bogus');
    }

}
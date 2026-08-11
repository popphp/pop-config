pop-config
==========

[![Build Status](https://github.com/popphp/pop-config/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-config/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-config)](http://cc.popphp.org/pop-config/)

[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

* [Overview](#overview)
* [Install](#install)
* [Quickstart](#quickstart)
  * [Set and access values](#set-and-access-values)
  * [Access nested values with dot notation](#access-nested-values-with-dot-notation)
  * [Allow changes](#allow-changes)
  * [Merge new values into the config object](#merge-new-values-into-the-config-object)
  * [Convert config object down to a basic array](#convert-config-object-down-to-a-basic-array)
  * [Convert config object to an `ArrayObject`](#convert-config-object-to-an-arrayobject)
  * [Parse a configuration file](#parse-a-configuration-file)
  * [Render config data to a string format](#render-config-data-to-a-string-format)
  * [Write config data to a file](#write-config-data-to-a-file)
  * [A note on YAML support](#a-note-on-yaml-support)
  * [Exceptions](#exceptions)

Overview
--------
`pop-config` is a basic configuration component that helps centralize application
configuration values and parameters. Values can be accessed via array notation,
object arrow notation, or dot notation for nested values. It can disable changes
to the configuration values if need be for the life-cycle of the application. It
also can parse configuration values from common formats, such as JSON, XML, INI
and YAML.

`pop-config` is a component of the [Pop PHP Framework](https://www.popphp.org/).

Install
-------

Install `pop-config` using Composer.

    composer require popphp/pop-config
    
Or, require it in your composer.json file

    "require": {
        "popphp/pop-config" : "^5.0.0"
    }

[Top](#pop-config)

Quickstart
----------

### Set and access values

```php
use Pop\Config\Config;

$config = new Config(['foo' => 'bar']);

$foo = $config->foo;
// OR
$foo = $config['foo'];
```

### Access nested values with dot notation

Nested values can also be accessed directly with dot notation, without manually
chaining array access at each level.

```php
use Pop\Config\Config;

$config = new Config(['database' => ['host' => 'localhost', 'port' => 5432]]);

$host = $config['database.host'];
// OR
$host = $config->{'database.host'};
```

A literal key always takes priority over dot-path traversal — a key that happens
to contain a `.` (e.g. `'example.com'`) is matched exactly first, and only falls
back to nested traversal when no literal key matches.

Setting and unsetting values also support dot notation, when changes are allowed:

```php
use Pop\Config\Config;

$config = new Config(['database' => ['host' => 'localhost']], true);

$config['database.port'] = 5432;
// $config->toArray() is now ['database' => ['host' => 'localhost', 'port' => 5432]]

unset($config['database.host']);
// removes just the 'host' key, leaving ['database' => ['port' => 5432]]
```

**Note:** setting a brand-new dotted key — one that doesn't already exist as a
literal key — always creates a nested structure, not a literal key.
`$config['example.com'] = 'x'` on an empty config produces
`['example' => ['com' => 'x']]`, not `['example.com' => 'x']`. A literal key only
"wins" when it already exists in the underlying data (e.g. loaded from a file).

### Allow changes

Changes to configuration values are disabled by default. Attempting to set or
unset a value on a config that doesn't allow changes throws a
`Pop\Config\ChangesNotAllowedException`. Check whether a config allows changes
with `changesAllowed()`.

```php
use Pop\Config\Config;

$config = new Config(['foo' => 'bar'], true);
$config->foo = 'New Value';

$config->changesAllowed(); // true
```

### Merge new values into the config object

By default, incoming values overwrite existing ones on a collision:

```php
use Pop\Config\Config;

$config = new Config($configData, true);
$config->merge($newData);
```

Pass `true` as the second argument to preserve existing values instead — on a
scalar collision, the existing value wins, and two colliding list values are
kept as the original list wholesale rather than spliced together.

```php
$config->merge($newData, true);
```

**Note:** when a list value collides with an associative array value at the
same key (in either merge mode), the two are combined into a hybrid array
rather than one side winning outright. For example, merging existing
`['a' => ['x', 'y', 'z']]` with incoming `['a' => ['one' => 1]]` produces
`['a' => ['x', 'y', 'z', 'one' => 1]]` in both default and `preserve: true`
modes. This asymmetric case is a known limitation — avoid mixing list and
associative shapes at the same key across merges if you need predictable
results.

`mergeFromData()` merges directly from a file path (or anything `parseData()`
accepts), parsing it first:

```php
$config->mergeFromData('/path/to/other-config.json');
// OR, preserving existing values on collision
$config->mergeFromData('/path/to/other-config.json', true);
```

Both `merge()` and `mergeFromData()` throw `Pop\Config\ChangesNotAllowedException`
if the config doesn't allow changes; `mergeFromData()` also throws
`Pop\Config\ParseException`/`Pop\Config\UnsupportedFormatException` if the file
can't be read or parsed.

### Convert config object down to a basic array

```php
use Pop\Config\Config;

$config = new Config($configData);
$data   = $config->toArray();
```

### Convert config object to an `ArrayObject`

```php
use Pop\Config\Config;

$config = new Config($configData);

$arrayObject       = $config->toArrayObject();       // Pop\Utils\ArrayObject
$nativeArrayObject = $config->toArrayObject(true);   // native \ArrayObject, ARRAY_AS_PROPS
```

Both are built from a fresh copy of the data — mutating the returned object never
affects the original `Config`.

### Parse a configuration file

    ; This is a sample configuration file config.ini
    [foo]
    bar = 1
    baz = 2

```php
use Pop\Config\Config;

$config = Config::createFromData('/path/to/config.ini');

// $value equals 1
$value = $config->foo['bar'];
// OR
$value = $config['foo']['bar'];
```

### Render config data to a string format

Supported formats include PHP, JSON, XML, INI and YAML

```php
use Pop\Config\Config;

$config = new Config($configData);
echo $config->render('json');
```

### Write config data to a file

`writeToFile()` picks the format from the filename's extension and writes the
rendered output directly to disk:

```php
use Pop\Config\Config;

$config = new Config($configData);
$config->writeToFile('/path/to/config.json');
```

Supported extensions are the same five formats as `render()` — an unsupported
extension throws `Pop\Config\UnsupportedFormatException`. **Note:** a filename
with no extension at all (no `.` in it) silently does nothing — no file is
written and no exception is thrown.

### A note on YAML support

YAML parsing and rendering go through
[`symfony/yaml`](https://symfony.com/doc/current/components/yaml.html) (a
required dependency, not an optional PHP extension). Two scalar-parsing
differences from the previous PECL `yaml` extension are worth knowing about:

- Boolean words (`yes`/`no`/`on`/`off`/`y`/`n`, any casing) and octal-looking
  integers (e.g. `0755`) are normalized back to `bool`/`int` to match the old
  behavior — no action needed.
- **Bare (unquoted) dates are not normalized.** A YAML value like
  `released: 2001-01-23` parses as a Unix timestamp `int`, not a string. If you
  need the literal string, quote it in the YAML file: `released: "2001-01-23"`.

### Exceptions

All exceptions thrown by `pop-config` extend the base `Pop\Config\Exception`, so
existing code catching that class continues to work. More specific subclasses are
available for finer-grained handling:

* `Pop\Config\ChangesNotAllowedException` &mdash; thrown by `__set()`, `__unset()`,
  `merge()`, and `mergeFromData()` when the config doesn't allow changes.
* `Pop\Config\ParseException` &mdash; thrown by `createFromData()`/`parseData()` for
  a missing file, unparseable content, or invalid input.
* `Pop\Config\UnsupportedFormatException` &mdash; thrown by `createFromData()`/
  `parseData()` for an unrecognized file extension, and by `render()` for an
  unrecognized format string.

```php
use Pop\Config\Config;
use Pop\Config\ParseException;

try {
    $config = Config::createFromData('/path/to/config.yml');
} catch (ParseException $e) {
    // handle a missing file or malformed content specifically
}
```

```php
use Pop\Config\Config;
use Pop\Config\ChangesNotAllowedException;

$config = new Config(['foo' => 'bar']); // changes not allowed (the default)

try {
    $config->foo = 'baz';
} catch (ChangesNotAllowedException $e) {
    // handle the immutability violation specifically
}
```

[Top](#pop-config)


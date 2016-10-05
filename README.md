pop-config
==========

[![Build Status](https://travis-ci.org/popphp/pop-config.svg?branch=master)](https://travis-ci.org/popphp/pop-config)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-config)](http://cc.popphp.org/pop-config/)

OVERVIEW
--------
`pop-config` is a basic configuration component that helps centralize application
configuration values and parameters. Values can be accessed via array notation or
object arrow notation. It can disable changes to the configuration values if need
be for the life-cycle of the application. It also can parse configuration values
from common formats, such as JSON, INI and XML.

`pop-config` is a component of the [Pop PHP Framework](http://www.popphp.org/).

INSTALL
-------

Install `pop-config` using Composer.

    composer require popphp/pop-config
    
Or, require it in your composer.json file

    "require": {
        "popphp/pop-config" : "3.0.*"
    }

BASIC USAGE
-----------

### Set and access values

```php
$config = new Pop\Config\Config(['foo' => 'bar']);

$foo = $config->foo;
// OR
$foo = $config['foo'];
```

### Allow changes

Changes to configuration values are disabled by default.

```php
$config = new Pop\Config\Config(['foo' => 'bar'], true);
$config->foo = 'New Value';
```

### Parse a configuration file

    ; This is a sample configuration file config.ini
    [foo]
    bar = 1
    baz = 2

```php
$config = new Pop\Config\Config('/path/to/config.ini');

// $value equals 1
$value = $config->foo->bar;
```

### Merge new values into the config object

```php
$config = new Pop\Config\Config($configData);
$config->merge($newData);
```

### Convert config object down to a basic array

```php
$config = new Pop\Config\Config($configData);
$data   = $config->toArray();
```

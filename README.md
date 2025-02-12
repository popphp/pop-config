pop-config
==========

[![Build Status](https://github.com/popphp/pop-config/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-config/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-config)](http://cc.popphp.org/pop-config/)

[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

* [Overview](#overview)
* [Install](#install)
* [Quickstart](#quickstart)

Overview
--------
`pop-config` is a basic configuration component that helps centralize application
configuration values and parameters. Values can be accessed via array notation or
object arrow notation. It can disable changes to the configuration values if need
be for the life-cycle of the application. It also can parse configuration values
from common formats, such as JSON, XML, INI and YAML.

`pop-config` is a component of the [Pop PHP Framework](https://www.popphp.org/).

Install
-------

Install `pop-config` using Composer.

    composer require popphp/pop-config
    
Or, require it in your composer.json file

    "require": {
        "popphp/pop-config" : "^4.0.3"
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

### Allow changes

Changes to configuration values are disabled by default.

```php
use Pop\Config\Config;

$config = new Config(['foo' => 'bar'], true);
$config->foo = 'New Value';
```

### Merge new values into the config object

```php
use Pop\Config\Config;

$config = new Config($configData);
$config->merge($newData);
```

### Convert config object down to a basic array

```php
use Pop\Config\Config;

$config = new Config($configData);
$data   = $config->toArray();
```

### Parse a configuration file

    ; This is a sample configuration file config.ini
    [foo]
    bar = 1
    baz = 2

```php
use Pop\Config\Config;

$config = Config::createFromData('/path/to/config.ini');

// $value equals 1
$value = $config->foo->bar;
```

### Render config data to a string format

Supported formats include PHP, JSON, XML, INI and YAML

```php
use Pop\Config\Config;

$config = new Config($configData);
echo $config->render('json');
```

[Top](#pop-config)


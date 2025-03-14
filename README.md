# AP\Env

[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

AP\Conff is a library for managing project configuration variables, featuring validation and object transformation capabilities.

## Installation

```bash
composer require ap-lib/conf
```

## Features

- Retrieve environment variables and PHP configuration files as objects with validation.
- Built-in validation mechanisms with error handling.

## Requirements

- PHP 8.3 or higher

## Getting started

### Lazy Loading with `MyConf`

You can create a custom configuration loader using a static class that extends `Conf`

```php
use AP\Conf\Conf;
use AP\Scheme\ToObject;
use AP\Scheme\Validation;

readonly class SecuritySettings implements ToObject, Validation {
    public function __construct() {
        public string $salt
    }
}

class MyConf extends Conf
{
    public static function security(): SecuritySettings
    {
        return self::obj(__FUNCTION__, SecuritySettings::class);
    }
}

class Core 
{
    static public function conf(): MyConf
    {
        return new MyConf(
            [
                __DIR__ . "/../conf",
                __DIR__ . "/../conf/local",
            ]
        );
    }
}

$salt = Core::conf()->security()->salt;
```

### Configuration Loading Behavior

1. The library first checks the `$_SERVER['security']` variable first, expecting a JSON string.
2. If not found in the environment variables, it merges values from the following files if they exist:
    - `__DIR__/../conf/security.php`
    - `__DIR__/../conf/local/security.php`
3. Values from the next file, such as `conf/local/security.php`, take priority and override those from the previous file, such as `conf/security.php`.

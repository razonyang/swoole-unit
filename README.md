# Swoole Unit Test Helpers

[![Latest Stable Version](https://poser.pugx.org/razonyang/swoole-unit/v/stable.png)](https://packagist.org/packages/razonyang/swoole-unit)
[![Total Downloads](https://poser.pugx.org/razonyang/swoole-unit/downloads.png)](https://packagist.org/packages/razonyang/swoole-unit)
[![Build Status](https://github.com/razonyang/swoole-unit/actions/workflows/build.yml/badge.svg)](https://github.com/razonyang/swoole-unit/actions)
[![Coverage Status](https://coveralls.io/repos/github/razonyang/swoole-unit/badge.svg?branch=main)](https://coveralls.io/github/razonyang/swoole-unit?branch=main)

## Installation

```bash
composer require razonyang/swoole-unit --prefer-dist --dev
```

## Helpers

### Request Builder

The `RequestBuilder` generates `Swoole\Http\Request` instances.

```php
<?php
$request = RequestBuilder::get('/')
    ->protocol('HTTP/1.1')
    ->host('localhost')
    ->contentType('application/x-www-form-urlencoded')
    ->contentLength(8)
    ->headers([
        'X-Foo' => [
            'Bar',
        ],
    ])
    ->body('hello=world')
    ->create();
```

The `RequestBuilder` supports chaining calls until `create`.

#### Form Data

```php
$data = [
    'hello' => 'world',
];
$request = RequestBuilder::post('/users')
    ->formData($data)
    ->create()
```

#### Multipart Form Data

```php
$data = [
    'hello' => 'world',
];
$files = [
    'avatar' => __DIR__ . DIRECTORY_SEPARATOR . 'avatar.jpg',
];
$request = RequestBuilder::post('/users')
    ->multipart($data, $files)
    ->create()
```

#### JSON Data

```php
$data = [
    'hello' => 'world',
];
$request = RequestBuilder::post('/users')
    ->jsonData($data)
    ->create()
```

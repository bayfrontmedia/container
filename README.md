## Container

An easy to use PSR-11 compatible dependency injection container.

- [License](#license)
- [Author](#author)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)

## License

This project is open source and available under the [MIT License](LICENSE).

## Author

<img src="https://cdn1.onbayfront.com/bfm/brand/bfm-logo.svg" alt="Bayfront Media" width="250" />

- [Bayfront Media homepage](https://www.bayfrontmedia.com?utm_source=github&amp;utm_medium=direct)
- [Bayfront Media GitHub](https://github.com/bayfrontmedia)

## Requirements

* PHP `^7.2.0|^8.0`

## Installation

```
composer require bayfrontmedia/container
```

## Usage

### Start using the container

```
use Bayfront\Container;

$container = new Container();
```

### Public methods

- [set](#set)
- [getEntries](#getentries)
- [get](#get)
- [resolve](#resolve)
- [has](#has)
- [remove](#remove)
- [setAlias](#setalias)
- [getAliases](#getaliases)
- [hasAlias](#hasalias)
- [removeAlias](#removealias)

<hr />

### set

**Description:**

Set an entry into the container.

**Parameters:**

- `$id` (string)
- `$value` (mixed)
- `$overwrite = false` (bool): If false, a `ContainerException` is thrown if an entry with the same ID already exists.
Otherwise, it is overwritten.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\ContainerException`

**Example:**

Both services and parameters can be set in the container. 
All services (classes) must be built using an anonymous function (a `\Closure`), 
and must return an instance of the class. 
Any other value will be considered a parameter.

The first time a service is requested from the container, 
the anonymous function is called and the result is saved. 
On subsequent calls, the same ID always returns the same result.

The anonymous function always calls the container instance as the first argument. 
This allows you to reference other items from the container, if needed. 
If you do not need access to the container, the parameter may be omitted from the function signature.

```php
// Set a service with no dependencies

$container->set('Fully\Namespaced\ClassName', function () {
    return new ClassName();
});

// Set a service with dependencies

$container->set('Fully\Namespaced\ClassName', function (ContainerInterface $container) {
    $dependency = $container->get('Fully\Namespaced\Dependency');
    return new ClassName($dependency);
});
```

<hr />

### getEntries

**Description:**

Returns an array of all ID's existing in the container.

**Parameters:**

- None.

**Returns:**

- (array)

**Example:**

```php
$entries = $container->getEntries();
```

<hr />

### get

**Description:**

Get an entry from the container by its ID or alias.

**Parameters:**

- `$id` (string)

**Returns:**

- (mixed)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```php
$service = $container->get('Fully\Namespaced\ClassName');
```

<hr />

### resolve

**Description:**

Resolves a class instance using dependency injection with entries which exist in the container.

**Parameters:**

- `$class` (string)
- `$params = []` (array): Additional parameters to pass to the class constructor.

**Returns:**

- (object)

**Throws:**

- `Bayfront\Container\ContainerException`
- `Bayfront\Container\NotFoundException`

**Example:**

```php

class ClassName {

    protected $service;
    protected $config;
    
    public function __construct(AnotherService $service, array $config)
    {
        $this->service = $service;
        $this->config = $config;
    }

}

$instance = $container->resolve('Fully\Namespaced\ClassName', [
    'config' => []
]);
```

<hr />

### has

**Description:**

Does entry exist in the container?

**Parameters:**

- `$id` (string): ID or alias

**Returns:**

- (bool)

**Example:**

```php
if ($container->has('Fully\Namespaced\ClassName')) {
    // Do something
}
```

<hr />

### remove

**Description:**

Remove entry from container.

**Parameters:**

- `$id` (string)

**Returns:**

- (void)

**Example:**

```php
$container->remove('Fully\Namespaced\ClassName');
```

<hr />

### setAlias

**Description:**

Set an alias for a given ID.

**Parameters:**

- `$alias` (string)
- `$id` (string)
- `$overwrite = false` (bool): If false, a `ContainerException` is thrown if an alias with the same name already exists.
Otherwise, it will be overwritten.

**Returns:**

- (void)

**Throws:**

- `Bayfront\Container\ContainerException`

**Example:**

```php
$container->setAlias('alias', 'Fully\Namespaced\ClassName');
```

One benefit of aliases is that they allow you to retrieve entries from the container in a concise, easy to remember manner.
In addition, aliases allow you to bind an interface to an implementation.

For example:

```php
$container->setAlias('Fully\Namespaced\Implementation', 'Fully\Namespaced\Interface');
```

Now, whenever a class requires an implementation of `Fully\Namespaced\Interface`,
an instance of `Fully\Namespaced\Implementation` will be returned, if existing in the container.

<hr />

### getAliases

**Description:**

Returns an array of all existing aliases.

**Parameters:**

- None.

**Returns:**

- (array)

**Example:**

```php
$aliases = $container->getAliases();
```

<hr />

### hasAlias

**Description:**

Does alias exist?

**Parameters:**

- `$alias` (string)

**Returns:**

- (bool)

**Example:**

```php
if ($container->hasAlias('alias')) {
    // Do something
}
```

<hr />

### removeAlias

**Description:**

Remove alias.

**Parameters:**

- `$alias` (string)

**Returns:**

- (void)

**Example:**

```php
$container->removeAlias('alias');
```
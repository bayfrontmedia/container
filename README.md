## container

An easy to use PSR-11 compatible dependency injection container.

- [License](#license)
- [Author](#author)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)

## License

This project is open source and available under the [MIT License](https://github.com/bayfrontmedia/php-array-helpers/blob/master/LICENSE).

## Author

John Robinson, [Bayfront Media](https://www.bayfrontmedia.com)

## Requirements

* PHP >= 7.2.0

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

- [get](#get)
- [getContents](#getcontents)
- [has](#has)
- [put](#put)
- [set](#set)
- [create](#create)
- [forget](#forget)

<hr />

### get

**Description:**

Finds and returns an entry in the container by its identifier.

**Parameters:**

- `$id` (string)

**Returns:**

- (object)

**Throws:**

- `Bayfront\Container\NotFoundException`

**Example:**

```
try {

    $object = $container->get('Dependency');

} catch (NotFoundException $e) {
    echo $e->getMessage();
}
```

<hr />

### getContents

**Description:**

Returns an array containing all the IDs which currently exist in the container.

**Parameters:**

- None

**Returns:**

- (array)

**Example:**

```
print_r($container->getContents());
```

<hr />

### has

**Description:**

Checks if the container can return an entry for the given identifier.

**Parameters:**

- `$id` (string)

**Returns:**

- (bool)

**Example:**

```
if ($container->has('Dependency')) {
    // Do something
}
```

<hr />

### put

**Description:**

Saves a preexisting class instance into the container identified by `$id`.

If another entry exists in the container with the same `$id`, it will be overwritten.

Saving a class instance to the container using its namespaced name as the `$id` will allow it to be used by the container whenever another class requires it as a dependency.

**Parameters:**

- `$id` (string)
- `$object` (object)

**Returns:**

- (self)

**Example:**

```
$some_class = new SomeClass();

$container->put('SomeClass', $some_class);
```

<hr />

### set

**Description:**

Creates a class instance using `create()`, and saves it into the container identified by `$id`. An instance of the class will be returned. 

If another entry exists in the container with the same `$id`, it will be overwritten.

Saving a class instance to the container using its namespaced name as the `$id` will allow it to be used by the container whenever another class requires it as a dependency.

**Parameters:**

- `$id` (string)
- `$class` (string): Fully namespaced class name
- `$params = []` (array): Named parameters to pass to the class constructor

**Returns:**

- (object)

**Throws:**

- `Bayfront\Container\ContainerException`

**Example:**

```
try {

    $object = $container->set('Dependency');

} catch (ContainerException $e) {
    echo $e->getMessage();
}
```

<hr />

### create

**Description:**

Creates a class instance using dependency injection. An instance of the class will be returned, but not saved in the container.

If this namespaced class already exists in the container as an `$id`, the instance existing in the container will be returned by default.

**Parameters:**

- `$class` (string): Fully namespaced class name
- `$params = []` (array): Named parameters to pass to the class constructor
- `$force_unique = false` (bool): Force return a new class instance by ignoring if it already exists in the container

**Returns:**

- (object)

**Throws:**

- `Bayfront\Container\ContainerException`

**Example:**

```
try {

    $object = $container->create('Dependency');

} catch (ContainerException $e) {
    echo $e->getMessage();
}
```

<hr />

### forget

**Description:**

Remove class instance from the container, if existing.

**Parameters:**

- `$id` (string)

**Returns:**

- (bool): Returns `TRUE` if class existed in the container before being removed.

**Example:**

```
$container->forget('Dependency');
```
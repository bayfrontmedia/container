<?php

/**
 * @package container
 * @link https://github.com/bayfrontmedia/container
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;

/*
 * PSR-11 valid Container Interface
 *
 * See: https://www.php-fig.org/psr/psr-11/
 * See: https://github.com/php-fig/container
 */

class Container implements ContainerInterface
{

    private static $instances = [];

    /**
     * Finds and returns an entry in the container by its identifier
     *
     * NOTE:
     *
     * Removed return type "object" declaration, as this does not allow
     * specific class name type hinting/return type declarations
     * where this method is used.
     *
     * @param string $id
     *
     * @return mixed
     *
     * @throws NotFoundException
     */

    public function get($id)
    {

        if (!$this->has($id)) {
            throw new NotFoundException($id);
        }

        return self::$instances[$id];

    }

    /**
     * Returns an array containing all the IDs which currently exist in the container
     *
     * @return array
     */

    public function getContents(): array
    {
        $contents = array_keys(self::$instances);

        sort($contents);

        return $contents;
    }

    /**
     * Checks if the container can return an entry for the given identifier
     *
     * @param string $id
     *
     * @return bool
     */

    public function has($id): bool
    {
        return isset (self::$instances[$id]);
    }

    /**
     * Saves a preexisting class instance into the container identified by $id.
     *
     * If another entry exists in the container with the same `$id`, it will be overwritten.
     *
     * Saving a class instance to the container using its namespaced name as the `$id` will allow it
     * to be used by the container whenever another class requires it as a dependency.
     *
     * @param string $id
     * @param object $object
     *
     * @return self
     */

    public function put(string $id, object $object): self
    {
        self::$instances[$id] = $object;
        return $this;
    }

    /**
     * Creates a class instance using create() and saves it into the container identified by $id.
     * An instance of the class will be returned.
     *
     * If another entry exists in the container with the same $id, it will be overwritten
     *
     * Saving a class instance to the container using its namespaced name as the `$id` will allow it
     * to be used by the container whenever another class requires it as a dependency.
     *
     * NOTE:
     * See get() regarding removal of return type "object" declaration.
     *
     * @param string $id
     * @param string $class (Fully namespaced class name)
     * @param array $params (Named parameters to pass to the class constructor)
     *
     * @return mixed
     *
     * @throws ContainerException
     */

    public function set(string $id, string $class, array $params = [])
    {

        self::$instances[$id] = $this->create($class, $params);

        return self::$instances[$id];

    }

    /**
     * Creates a class instance using dependency injection.
     * An instance of the class will be returned, but not saved in the container.
     *
     * If this namespaced class already exists in the container as an $id,
     * the instance existing in the container will be returned by default.
     *
     * NOTE:
     * See get() regarding removal of return type "object" declaration.
     *
     * @param string $class (Fully namespaced class name)
     * @param array $params (Named parameters to pass to the class constructor)
     * @param bool $force_unique
     *
     * (Force return a new class instance by ignoring if it already exists in the container)
     *
     * @return mixed
     *
     * @throws ContainerException
     */

    public function create(string $class, array $params = [], bool $force_unique = false)
    {

        /*
         * First, see if an instance of this class exists in the container
         */

        if ($this->has($class) && false === $force_unique) {

            try {

                return $this->get($class);

            } catch (NotFoundException $e) {

                throw new ContainerException('Error resolving class from container: ' . $class, 0, $e);

            }

        }

        /*
         * If not, try to create one
         */

        try {

            $reflection = new ReflectionClass($class);

        } catch (ReflectionException $e) {

            throw new ContainerException('Unable to locate class: ' . $class, 0, $e);

        }

        if (!$reflection->isInstantiable()) {

            throw new ContainerException('Unable to instantiate class: ' . $class);

        }

        // Constructor

        $constructor = $reflection->getConstructor();

        /*
         * If class has no constructor (no dependencies)
         * it can be instantiated now
         */

        if (NULL === $constructor) { // No constructor

            return new $class; // Class instance

        }

        /*
         * Class has constructor, get and pass its dependencies
         * (The class arguments required in its constructor)
         */

        $dependencies = $this->_getDependencies($class, $constructor->getParameters(), $params);

        /*
         * Create the class instance using the identified dependencies
         */

        return $reflection->newInstanceArgs($dependencies); // Class instance

    }

    /**
     * Resolve the class dependencies
     *
     * @param string $class (Fully namespaced class name)
     * @param array $class_params
     * @param array $given_params
     *
     * @return array
     *
     * @throws ContainerException
     */

    private function _getDependencies(string $class, array $class_params, array $given_params): array
    {

        $return = [];

        foreach ($class_params as $parameter) {

            // $parameter->name = Argument name assigned to the parameter within the class constructor

            if (isset($given_params[$parameter->name])) { // If a value was given for this parameter

                $return[$parameter->name] = $given_params[$parameter->name];

                continue; // Continue to the next parameter

            }

            // A value was not passed. Attempt to resolve it

            $dependency = $parameter->getClass();

            if (NULL === $dependency) { // If not a class object

                if ($parameter->isDefaultValueAvailable()) { // Is a default value available

                    $return[$parameter->name] = $parameter->getDefaultValue();

                    continue; // Continue to the next parameter

                }

                /*
                 * Parameter is not a class, a value was not given, and the class
                 * has no default value defined
                 */

                throw new ContainerException('Unable to resolve parameter (' . $parameter . ') for class: ' . $class);

            }

            /*
             * Parameter is a class, and its instance was not given.
             * Attempt to resolve it.
             *
             * $dependency->name = Namespaced class
             */

            /*
             * First, see if an instance of this class exists in the container
             */

            if ($this->has($dependency->name)) {

                try {

                    $return[$parameter->name] = $this->get($dependency->name); // Class instance

                } catch (NotFoundException $e) {

                    throw new ContainerException('Error resolving dependent class from container: ' . $dependency->name, 0, $e);

                }

            }

            try {

                $resolved = $this->create($dependency->name); // Returns object

            } catch (ContainerException $e) {

                throw new ContainerException('Unable to resolve object parameter (' . $parameter . ') for class: ' . $class, 0, $e);

            }

            $return[$parameter->name] = $resolved; // Class instance

            continue; // Continue to the next parameter

        }

        return $return;

    }

    /**
     * Remove class instance from the container, if existing
     *
     * @param string $id
     *
     * @return bool (Returns TRUE if class existed in the container before being removed)
     */

    public function forget($id): bool
    {

        if ($this->has($id)) {

            self::$instances[$id] = NULL;

            unset(self::$instances[$id]);

            return true;

        }

        return false;

    }

}
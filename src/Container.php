<?php

namespace Bayfront\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class Container implements ContainerInterface
{

    protected $aliases = [];
    protected $entries = [];

    /**
     * Resolve dependencies.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */

    protected function resolveDependencies(ReflectionMethod $constructor, array $params = []): array
    {

        $return = [];

        $parameters = $constructor->getParameters();

        foreach ($parameters as $parameter) {

            // If a value was given for this parameter

            if (isset($params[$parameter->name])) {

                $return[$parameter->name] = $params[$parameter->name];
                continue; // Continue to the next parameter

            }

            // A value was not passed. Attempt to resolve it

            /*
             * Depreciated:
             * $dependency = $parameter->getClass();
             */

            try {
                $dependency = $parameter->getType() && !$parameter->getType()->isBuiltin() ? new ReflectionClass($parameter->getType()->getName()) : null;
            } catch (ReflectionException $e) {
                throw new ContainerException('Unable to determine parameter (' . $parameter . ') for class: ' . $constructor->getDeclaringClass());
            }

            if (null === $dependency) { // If not a class

                /*
                 * Primitive values can be resolved from the container using their name,
                 * but this should be avoided as names can cause conflicts.
                 * Pass the primitive values to the constructor instead.
                 */

                /*
                if ($this->has($parameter->name)) { // Does parameter exist in container

                    $return[$parameter->name] = $this->get($parameter->name);
                    continue;

                }
                */

                if ($parameter->isDefaultValueAvailable()) { // Is a default value available

                    $return[$parameter->name] = $parameter->getDefaultValue();
                    continue; // Continue to the next parameter

                }

                // A value was not given for this parameter, it is not a class, does not exist in container, and a default value was not given

                throw new ContainerException('Unable to resolve parameter (' . $parameter . ') for class: ' . $constructor->getDeclaringClass());

            }

            /*
             * Parameter is a class, and its value was not given.
             * Attempt to resolve it.
             *
             * $dependency->name = Namespaced class
             */

            if ($this->has($dependency->name)) { // First, see if an instance of this class exists in the container

                $return[$parameter->name] = $this->get($dependency->name); // Class instance

            } else { // No instance in the container - attempt to resolve

                $return[$parameter->name] = $this->make($dependency->name, $params);

            }

        }

        return $return;

    }

    /**
     * Set an entry into the container.
     * Anonymous functions (closures) are called on the first get().
     *
     * @param string $id
     * @param $value
     * @param bool $overwrite (If false, a ContainerException is thrown if an entry with the same ID already exists)
     * @return void
     * @throws ContainerException
     */

    public function set(string $id, $value, bool $overwrite = false): void
    {

        if ($overwrite === false && $this->has($id)) {
            throw new ContainerException('Unable to set entry: ID (' . $id . ') already exists');
        }

        $this->entries[$id] = $value;

    }

    /**
     * Returns an array of all ID's existing in the container.
     *
     * @return array
     */

    public function getEntries(): array
    {
        return array_keys($this->entries);
    }

    /**
     * Returns an entry by ID.
     * ID must already be confirmed to exist via has().
     *
     * @param string $id
     * @return mixed
     */

    protected function getEntry(string $id)
    {

        if (!$this->entries[$id] instanceof Closure) { // Already resolved
            return $this->entries[$id];
        }

        $this->entries[$id] = $this->entries[$id]($this); // Resolve and save

        return $this->entries[$id];

    }

    /**
     * Get an entry from the container by its ID or alias.
     *
     * @param string $id
     * @return mixed
     * @throws NotFoundException
     */

    public function get(string $id)
    {

        // Check alias

        if ($this->hasAlias($id) && $this->has($this->aliases[$id])) {
            return $this->getEntry($this->aliases[$id]);
        }

        // No alias

        if (!isset($this->entries[$id])) {
            throw new NotFoundException('Unable to get entry: ID (' . $id . ') does not exist');
        }

        return $this->getEntry($id);

    }

    /**
     * Makes and returns a new class instance, automatically injecting dependencies which exist in the container.
     *
     * @param string $class
     * @param array $params (Additional parameters to pass to the class constructor)
     * @return mixed
     * @throws ContainerException
     * @throws NotFoundException
     */

    public function make(string $class, array $params = [])
    {

        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new ContainerException('Unable to make class: class (' . $class . ') does not exist', 0, $e);
        }

        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class;
        }

        $dependencies = $this->resolveDependencies($constructor, $params);

        try {
            return $reflection->newInstanceArgs($dependencies);
        } catch (ReflectionException $e) {
            throw new ContainerException('Unable to make class: ' . $class, 0, $e);
        }

    }

    /**
     * Does entry or alias exist in the container?
     * (ie: Can an entry be resolved using get() with this ID?)
     *
     * @param string $id (ID or alias)
     * @return bool
     */

    public function has(string $id): bool
    {

        // Check alias

        if ($this->hasAlias($id) && isset($this->aliases[$id])) {
            return true;
        }

        return isset($this->entries[$id]);
    }

    /**
     * Remove entry from container, if existing.
     *
     * @param string $id
     * @return void
     */

    public function remove(string $id): void
    {

        if (isset($this->entries[$id])) {
            unset($this->entries[$id]);
        }

    }

    /**
     * Set an alias for a given ID.
     *
     * @param string $alias
     * @param string $id
     * @param bool $overwrite (If false, a ContainerException is thrown if an alias with the same name already exists)
     * @return void
     * @throws ContainerException
     */

    public function setAlias(string $alias, string $id, bool $overwrite = false): void
    {

        if ($overwrite === false && $this->hasAlias($alias)) {
            throw new ContainerException('Unable to set alias: Alias (' . $alias . ') already exists');
        }

        $this->aliases[$alias] = $id;

    }

    /**
     * Returns an array of all existing aliases.
     *
     * @return array
     */

    public function getAliases(): array
    {
        return array_keys($this->aliases);
    }

    /**
     * Does alias exist?
     *
     * @param string $alias
     * @return bool
     */

    public function hasAlias(string $alias): bool
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * Remove alias.
     *
     * @param string $alias
     * @return void
     */

    public function removeAlias(string $alias): void
    {
        if (isset($this->aliases[$alias])) {
            unset($this->aliases[$alias]);
        }
    }

}
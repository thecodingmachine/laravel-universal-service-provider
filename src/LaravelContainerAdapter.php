<?php
namespace TheCodingMachine\Laravel;

use Illuminate\Container\Container as LaravelContainerInterface;
use Psr\Container\ContainerInterface;
use TheCodingMachine\Laravel\Exception\ContainerException;
use TheCodingMachine\Laravel\Exception\NotFoundException;

/**
 * An adapter from a Laravel Container to the standardized ContainerInterface
 */
class LaravelContainerAdapter implements ContainerInterface
{
    /**
     * @var LaravelContainerInterface A Laravel Container
     */
    private $container;
    /**
     * @param LaravelContainerInterface $container A Laravel Container
     */
    public function __construct(LaravelContainerInterface $container)
    {
        $this->container = $container;
    }
    public function get($id)
    {
        if (isset($_ENV[$id])) {
            return env($id);
        }

        $configValue = config('app.'.$id, null);
        if ($configValue !== null) {
            return $configValue;
        }
        if ($this->container->bound($id)) {
            try {
                return $this->container->make($id);
            } catch (\Exception $prev) {
                throw ContainerException::fromPrevious($id, $prev);
            }
        } else {
            throw NotFoundException::fromPrevious($id);
        }
    }
    public function has($id)
    {
        if (isset($_ENV[$id])) {
            return true;
        }
        $configValue = config('app.'.$id, null);
        if ($configValue !== null) {
            return true;
        }
        return $this->container->bound($id);
    }
}

<?php

/**
 * @package container
 * @link https://github.com/bayfrontmedia/container
 * @author John Robinson <john@bayfrontmedia.com>
 * @copyright 2020 Bayfront Media
 */

namespace Bayfront\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class ContainerException extends Exception implements ContainerExceptionInterface
{

    public function __construct($dependency, $code = 0, Exception $previous = NULL)
    {

        $message = sprintf('Error creating or resolving resource from container: %s', $dependency);

        parent::__construct($message, $code, $previous);

    }

}
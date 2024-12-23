<?php

namespace Bayfront\Container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends Exception implements NotFoundExceptionInterface
{

    public function __construct($resource, $code = 0, ?Exception $previous = NULL)
    {

        $message = sprintf('Resource not found in container: %s', $resource);

        parent::__construct($message, $code, $previous);

    }

}
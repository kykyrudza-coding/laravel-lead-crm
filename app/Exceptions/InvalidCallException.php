<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\BaseException;

class InvalidCallException extends BaseException
{
    protected int $statusCode = 422;
}

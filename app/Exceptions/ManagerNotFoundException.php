<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\BaseException;
use Illuminate\Support\Str;

class ManagerNotFoundException extends BaseException
{
    protected int $statusCode = 404;

    public function __construct(int $managerId)
    {
        parent::__construct(
            Str::of('Manager with ID :id not found.')->replace(':id', (string) $managerId)->toString()
        );
    }
}

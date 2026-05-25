<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\BaseException;
use Illuminate\Support\Str;

class LeadNotFoundException extends BaseException
{
    protected int $statusCode = 404;

    public function __construct(int $leadId)
    {
        parent::__construct(
            Str::of('Lead with ID :id not found.')->replace(':id', (string) $leadId)->toString()
        );
    }
}

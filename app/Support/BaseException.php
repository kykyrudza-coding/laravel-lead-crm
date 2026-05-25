<?php

declare(strict_types=1);

namespace App\Support;

use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

abstract class BaseException extends Exception
{
    protected int $statusCode = 400 {
        get {
            return $this->statusCode;
        }
    }

    public function __construct(string $message = '', int $statusCode = 0, ?Throwable $previous = null)
    {
        if ($statusCode > 0) {
            $this->statusCode = $statusCode;
        }

        parent::__construct($message, $this->statusCode, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->statusCode);
    }
}

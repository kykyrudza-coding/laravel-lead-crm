<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class ApiResponse
{
    /**
     * Successful response with data.
     */
    public static function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
        ], $status);
    }

    /**
     * Successful response for resource creation.
     */
    public static function created(mixed $data = null): JsonResponse
    {
        return self::success($data, 201);
    }

    /**
     * Error response.
     */
    public static function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $payload = Arr::whereNotNull([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ]);

        return response()->json($payload, $status);
    }
}

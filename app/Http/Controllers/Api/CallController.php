<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidCallException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCallRequest;
use App\Models\Lead;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CallController extends Controller
{
    /**
     * POST /api/leads/{lead}/calls
     *
     * Add a call to a lead. Business logic (status transitions,
     * manager assignment) is handled by CallObserver.
     *
     * @throws InvalidCallException
     */
    public function store(StoreCallRequest $request, Lead $lead): JsonResponse
    {
        if ($lead->status->isTerminal()) {
            throw new InvalidCallException(
                Str::of("Cannot add a call to a lead with status ':status'.")
                    ->replace(':status', $lead->status->value)
                    ->toString()
            );
        }

        $call = $lead->calls()->create($request->validated());

        // Reload the lead to reflect any changes made by the observer
        $lead->refresh();

        return ApiResponse::created(compact('call', 'lead'));
    }
}

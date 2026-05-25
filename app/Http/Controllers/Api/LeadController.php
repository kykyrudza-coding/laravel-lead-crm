<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    /**
     * POST /api/leads
     *
     * Create a new lead with the status 'new' and no manager.
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $data = $request->validated();

        $lead = Lead::query()
            ->create($data);

        return ApiResponse::created($lead);
    }
}

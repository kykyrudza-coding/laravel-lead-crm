<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manager;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class ManagerLeadController extends Controller
{
    /**
     * GET /api/managers/{manager}/leads
     *
     * Returns all leads for a given manager, each with:
     *  - calls_count
     *  - total_call_duration (sum of all call durations)
     */
    public function index(Manager $manager): JsonResponse
    {
        $leads = $manager->leads()
            ->withCount('calls')
            ->withSum('calls', 'duration')
            ->get()
            ->map(fn ($lead) => [
                ...Arr::only($lead->toArray(), ['id', 'name', 'status']),
                'calls_count' => $lead->calls_count,
                'total_call_duration' => intval(data_get($lead, 'calls_sum_duration', 0)),
            ]);

        return ApiResponse::success($leads);
    }
}

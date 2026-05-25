<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CallResult;
use App\Enums\LeadStatus;
use App\Models\Call;
use App\Models\Lead;

class CallObserver
{
    /**
     * Handle the Call "created" event.
     *
     * All lead status transitions are handled here automatically:
     * - First call: new → in_progress
     * - No manager assigned: assign the caller
     * - result=success: status → won
     * - Last 3 calls all no_answer: status → lost
     */
    public function created(Call $call): void
    {
        $lead = $call->lead;

        $this->assignManagerIfNeeded($lead, $call);
        $this->transitionStatus($lead, $call);
    }

    /**
     * If the lead has no manager, assign the one who made this call.
     */
    private function assignManagerIfNeeded(Lead $lead, Call $call): void
    {
        if (blank($lead->manager_id)) {
            $lead->manager_id = $call->manager_id;
        }
    }

    /**
     * Apply status transition rules in priority order:
     * 1. success → won
     * 2. The last 3 calls are no_answer → lost
     * 3. First call → in_progress
     */
    private function transitionStatus(Lead $lead, Call $call): void
    {
        if ($call->result === CallResult::Success) {
            $lead->status = LeadStatus::Won;
            $lead->save();

            return;
        }

        if ($this->lastThreeCallsAreNoAnswer($lead)) {
            $lead->status = LeadStatus::Lost;
            $lead->save();

            return;
        }

        if ($lead->status === LeadStatus::New) {
            $lead->status = LeadStatus::InProgress;
        }

        $lead->save();
    }

    /**
     * Check whether the last 3 calls for a lead all have result = no_answer.
     */
    private function lastThreeCallsAreNoAnswer(Lead $lead): bool
    {
        $lastThree = $lead->calls()
            ->latest()
            ->take(3)
            ->pluck('result');

        return $lastThree->count() === 3
            && $lastThree->every(fn ($result) => $result === CallResult::NoAnswer || $result === CallResult::NoAnswer->value);
    }
}

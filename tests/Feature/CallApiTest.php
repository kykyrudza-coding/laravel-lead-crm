<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_call_for_lead(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create();

        $response = $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 120,
            'result' => 'callback_later',
            'manager_id' => $manager->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('calls', [
            'lead_id' => $lead->id,
            'manager_id' => $manager->id,
            'duration' => 120,
            'result' => 'callback_later',
        ]);
    }

    public function test_first_call_changes_status_to_in_progress(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create();

        $this->assertEquals('new', $lead->status->value);

        $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 30,
            'result' => 'no_answer',
            'manager_id' => $manager->id,
        ]);

        $lead->refresh();
        $this->assertEquals('in_progress', $lead->status->value);
    }

    public function test_first_call_assigns_manager_to_lead(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create();

        $this->assertNull($lead->manager_id);

        $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 30,
            'result' => 'callback_later',
            'manager_id' => $manager->id,
        ]);

        $lead->refresh();
        $this->assertEquals($manager->id, $lead->manager_id);
    }

    public function test_success_call_changes_status_to_won(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create();

        $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 60,
            'result' => 'success',
            'manager_id' => $manager->id,
        ]);

        $lead->refresh();
        $this->assertEquals('won', $lead->status->value);
    }

    public function test_three_consecutive_no_answer_calls_mark_lead_as_lost(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson("/api/leads/$lead->id/calls", [
                'duration' => 15,
                'result' => 'no_answer',
                'manager_id' => $manager->id,
            ]);
        }

        $lead->refresh();
        $this->assertEquals('lost', $lead->status->value);
    }

    public function test_two_no_answer_calls_do_not_mark_lead_as_lost(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create();

        for ($i = 0; $i < 2; $i++) {
            $this->postJson("/api/leads/$lead->id/calls", [
                'duration' => 15,
                'result' => 'no_answer',
                'manager_id' => $manager->id,
            ]);
        }

        $lead->refresh();
        $this->assertEquals('in_progress', $lead->status->value);
    }

    public function test_non_consecutive_no_answer_does_not_mark_lost(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create();

        // 2 no_answer, 1 callback, 1 no_answer — should NOT be lost
        $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 15, 'result' => 'no_answer', 'manager_id' => $manager->id,
        ]);
        $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 15, 'result' => 'no_answer', 'manager_id' => $manager->id,
        ]);
        $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 30, 'result' => 'callback_later', 'manager_id' => $manager->id,
        ]);
        $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 15, 'result' => 'no_answer', 'manager_id' => $manager->id,
        ]);

        $lead->refresh();
        $this->assertEquals('in_progress', $lead->status->value);
    }

    public function test_call_validation_requires_all_fields(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->postJson("/api/leads/$lead->id/calls");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_call_validation_rejects_invalid_result(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create();

        $response = $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 60,
            'result' => 'invalid_value',
            'manager_id' => $manager->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_call_to_nonexistent_lead_returns_404(): void
    {
        $manager = Manager::factory()->create();

        $response = $this->postJson('/api/leads/999/calls', [
            'duration' => 60,
            'result' => 'success',
            'manager_id' => $manager->id,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Lead with ID 999 not found.');
    }

    public function test_cannot_add_call_to_won_lead(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create();

        // First call with success → lead becomes "won"
        $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 60,
            'result' => 'success',
            'manager_id' => $manager->id,
        ]);

        $lead->refresh();
        $this->assertEquals('won', $lead->status->value);

        // Attempting another call should fail
        $response = $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 30,
            'result' => 'callback_later',
            'manager_id' => $manager->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Cannot add a call to a lead with status 'won'.");
    }

    public function test_cannot_add_call_to_lost_lead(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create();

        // 3 no_answer calls → lead becomes "lost"
        for ($i = 0; $i < 3; $i++) {
            $this->postJson("/api/leads/$lead->id/calls", [
                'duration' => 15,
                'result' => 'no_answer',
                'manager_id' => $manager->id,
            ]);
        }

        $lead->refresh();
        $this->assertEquals('lost', $lead->status->value);

        // Attempting another call should fail
        $response = $this->postJson("/api/leads/$lead->id/calls", [
            'duration' => 30,
            'result' => 'callback_later',
            'manager_id' => $manager->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Cannot add a call to a lead with status 'lost'.");
    }
}

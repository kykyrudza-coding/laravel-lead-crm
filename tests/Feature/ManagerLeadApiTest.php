<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Lead;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerLeadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_manager_leads_with_call_stats(): void
    {
        $manager = Manager::factory()->create();
        $lead = Lead::factory()->create(['manager_id' => $manager->id]);

        // Create calls directly (bypassing observer for deterministic setup)
        Call::factory()
            ->create([
                'lead_id' => $lead->id,
                'manager_id' => $manager->id,
                'duration' => 60,
                'result' => 'no_answer',
            ]);

        Call::factory()
            ->create([
                'lead_id' => $lead->id,
                'manager_id' => $manager->id,
                'duration' => 120,
                'result' => 'callback_later',
            ]);

        $response = $this->getJson("/api/managers/$manager->id/leads");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $lead->id)
            ->assertJsonPath('data.0.name', $lead->name)
            ->assertJsonPath('data.0.calls_count', 2)
            ->assertJsonPath('data.0.total_call_duration', 180);
    }

    public function test_returns_empty_array_for_manager_with_no_leads(): void
    {
        $manager = Manager::factory()->create();

        $response = $this->getJson("/api/managers/$manager->id/leads");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_returns_404_for_nonexistent_manager(): void
    {
        $response = $this->getJson('/api/managers/999/leads');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Manager with ID 999 not found.');
    }

    public function test_lead_with_no_calls_has_zero_stats(): void
    {
        $manager = Manager::factory()->create();
        Lead::factory()
            ->create([
                'manager_id' => $manager->id,
            ]);

        $response = $this->getJson("/api/managers/$manager->id/leads");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.calls_count', 0)
            ->assertJsonPath('data.0.total_call_duration', 0);
    }
}

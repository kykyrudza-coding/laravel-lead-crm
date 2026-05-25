<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_lead(): void
    {
        $response = $this->postJson('/api/leads', [
            'name' => 'John Doe',
            'phone' => '+380991234567',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.phone', '+380991234567')
            ->assertJsonPath('data.status', 'new');

        $this->assertDatabaseHas('leads', [
            'name' => 'John Doe',
            'phone' => '+380991234567',
            'status' => 'new',
            'manager_id' => null,
        ]);
    }

    public function test_create_lead_requires_name_and_phone(): void
    {
        $response = $this->postJson('/api/leads');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed.');
    }

    public function test_status_cannot_be_set_directly(): void
    {
        $response = $this->postJson('/api/leads', [
            'name' => 'John Doe',
            'phone' => '+380991234567',
            'status' => 'won',
        ]);

        $response->assertStatus(201);

        // Status should still be 'new' regardless of what was passed
        $this->assertDatabaseHas('leads', [
            'name' => 'John Doe',
            'status' => 'new',
        ]);
    }
}

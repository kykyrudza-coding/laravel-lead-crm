<?php

namespace Database\Factories;

use App\Models\Call;
use App\Models\Lead;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Factories\Factory;

class CallFactory extends Factory
{
    protected $model = Call::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'manager_id' => Manager::factory(),
            'duration' => fake()->numberBetween(10, 600),
            'result' => fake()->randomElement(['no_answer', 'callback_later', 'success']),
        ];
    }
}

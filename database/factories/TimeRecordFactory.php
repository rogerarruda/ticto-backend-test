<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeRecord>
 */
class TimeRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'recorded_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }
}

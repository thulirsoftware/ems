<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'admin_id' => null,
            'type' => 'assessment_assigned',
            'title' => 'New assessment assigned',
            'message' => fake()->sentence(),
            'data' => [],
            'is_read' => false,
        ];
    }

    public function read(): static
    {
        return $this->state(['is_read' => true]);
    }

    public function forAdmin(): static
    {
        return $this->state([
            'user_id' => null,
            'admin_id' => \App\Models\Admin::factory(),
        ]);
    }
}

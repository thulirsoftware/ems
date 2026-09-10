<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Assessment;
use App\Models\AssessmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assessment>
 */
class AssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'admin_id' => Admin::factory(),
            'assessment_type_id' => AssessmentType::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'is_active' => true,
            'difficulty_level' => fake()->randomElement(['easy', 'medium', 'hard']),
            'shuffle' => fake()->boolean(70),
            'is_library' => false,
            'has_negative' => false,
            'negative_marks' => 0,
            'scheduling_type' => Assessment::SCHEDULING_FIXED,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function library(): static
    {
        return $this->state(['is_library' => true]);
    }

    public function batchWise(): static
    {
        return $this->state([
            'scheduling_type' => Assessment::SCHEDULING_BATCH_WISE,
        ]);
    }

    public function flexible(): static
    {
        return $this->state([
            'scheduling_type' => Assessment::SCHEDULING_FLEXIBLE,
        ]);
    }

    public function withNegativeMarking(float $marks = 0.25): static
    {
        return $this->state([
            'has_negative' => true,
            'negative_marks' => $marks,
        ]);
    }
}

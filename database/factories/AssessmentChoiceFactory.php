<?php

namespace Database\Factories;

use App\Models\AssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssessmentChoice>
 */
class AssessmentChoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => AssessmentQuestion::factory(),
            'option' => fake()->word(),
            'is_correct' => false,
            'order' => 1,
        ];
    }

    public function correct(): static
    {
        return $this->state(['is_correct' => true]);
    }
}

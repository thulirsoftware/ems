<?php

namespace Database\Factories;

use App\Models\Assessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Batch>
 */
class BatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'name' => fake()->unique()->bothify('Batch-####'),
            'publish_date' => app_now()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'expiry_date' => null,
            'duration_minutes' => null,
            'capacity' => 30,
        ];
    }

    // Scheduled window on a given date, matching the fixed-schedule flow.
    public function window(string $date, string $start = '09:00:00', string $end = '12:00:00'): static
    {
        return $this->state([
            'publish_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    // Flexible assessments only track an expiry date + duration, not a start/end window.
    public function flexible(int $expiresInDays = 30, int $durationMinutes = 30): static
    {
        return $this->state([
            'publish_date' => null,
            'start_time' => null,
            'end_time' => null,
            'expiry_date' => app_now()->addDays($expiresInDays)->toDateString(),
            'duration_minutes' => $durationMinutes,
        ]);
    }
}

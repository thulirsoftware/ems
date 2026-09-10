<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Batch;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $today = app_now()->toDateString();
        $tomorrow = app_now()->addDay()->toDateString();

        foreach (Assessment::all() as $assessment) {

            if ($assessment->scheduling_type === Assessment::SCHEDULING_BATCH_WISE) {

                // Two batches with different schedules so admin batch/report
                // endpoints and the assignment time-conflict check have
                // something real to compare against.
                Batch::create([
                    'assessment_id' => $assessment->id,
                    'name' => $assessment->title . ' - Morning Batch',
                    'publish_date' => $today,
                    'start_time' => '09:00:00',
                    'end_time' => '11:00:00',
                    'capacity' => 20,
                ]);

                Batch::create([
                    'assessment_id' => $assessment->id,
                    'name' => $assessment->title . ' - Evening Batch',
                    'publish_date' => $tomorrow,
                    'start_time' => '17:00:00',
                    'end_time' => '19:00:00',
                    'capacity' => 20,
                ]);

                continue;
            }

            if ($assessment->scheduling_type === Assessment::SCHEDULING_FLEXIBLE) {

                // Available anytime within [publish_date, expiry_date]
                // (start_date/end_date), timed per attempt via duration_minutes.
                Batch::create([
                    'assessment_id' => $assessment->id,
                    'name' => 'individual_batch_' . $assessment->id,
                    'publish_date' => $today,
                    'expiry_date' => app_now()->addDays(30)->toDateString(),
                    'duration_minutes' => 30,
                ]);

                continue;
            }

            // Fixed-schedule, non-batch-wise: exactly one implicit batch,
            // open all of today so it's immediately usable after seeding.
            Batch::create([
                'assessment_id' => $assessment->id,
                'name' => 'individual_batch_' . $assessment->id,
                'publish_date' => $today,
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'capacity' => null,
            ]);
        }
    }
}

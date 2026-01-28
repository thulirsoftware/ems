<?php

namespace Database\Seeders;

use App\Models\Assessment;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    public function run(): void
    {
        Assessment::create([
            'admin_id' => 1,
            'assessment_type_id' => 1,
            'title' => 'Basic Math Test',
            'description' => 'Simple math assessment',
            'is_active' => true,

            'publish_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',

            'shuffle' => true,
            'is_library' => true,
        ]);
    }
}

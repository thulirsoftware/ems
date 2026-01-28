<?php

namespace Database\Seeders;

use App\Models\AssessmentType;
use Illuminate\Database\Seeder;

class AssessmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        AssessmentType::create([
            'name' => 'MCQ',
            'slug' => 'mcq',
        ]);
    }
}
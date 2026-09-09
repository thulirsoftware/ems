<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentChoice;
use App\Models\AssessmentQuestion;
use Illuminate\Database\Seeder;

class AssessmentQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMcq('Basic Math Test', [
            ['What is 2 + 2?', ['3', '4', '5', '22'], 1],
            ['What is 9 - 5?', ['3', '4', '5', '6'], 1],
            ['What is 6 x 7?', ['36', '40', '42', '48'], 2],
            ['What is 100 / 4?', ['20', '25', '30', '40'], 1],
            ['What is the square root of 81?', ['7', '8', '9', '11'], 2],
        ]);

        $this->seedMcq('Advanced Algebra Quiz', [
            ['Solve for x: 2x + 3 = 11', ['3', '4', '5', '6'], 1],
            ['Solve for x: x^2 = 49', ['6', '7', '8', '9'], 1],
            ['Simplify: 3(x + 4) - 2x', ['x + 12', 'x + 4', '2x + 12', '5x + 4'], 0],
            ['What is the slope of y = 3x + 5?', ['3', '5', '8', '-3'], 0],
            ['Factorize: x^2 - 9', ['(x-3)(x+3)', '(x-9)(x+1)', '(x-1)(x+9)', '(x+3)(x+3)'], 0],
        ]);

        $this->seedMcq('Flexible Practice Test', [
            ['Which planet is known as the Red Planet?', ['Venus', 'Mars', 'Jupiter', 'Saturn'], 1],
            ['What is the chemical symbol for water?', ['H2O', 'CO2', 'O2', 'NaCl'], 0],
            ['How many continents are there on Earth?', ['5', '6', '7', '8'], 2],
            ['What is the capital of Japan?', ['Seoul', 'Beijing', 'Tokyo', 'Bangkok'], 2],
            ['Which gas do plants absorb from the atmosphere?', ['Oxygen', 'Nitrogen', 'Carbon dioxide', 'Hydrogen'], 2],
        ]);

        $this->seedDescriptive('General Knowledge Descriptive', [
            'Explain the water cycle in your own words.',
            'Describe the main causes of climate change.',
            'What are the three branches of government and their roles?',
        ]);

        $this->seedDescriptive('Coding Challenge - Arrays', [
            'Write a function that returns the maximum value in an array of integers.',
            'Write a function that removes duplicate values from an array while preserving order.',
        ], ['language' => 'python']);

        $this->seedDescriptive('Startup Case Study', [
            'Given the startup scenario provided, identify the biggest risk to the business model and justify your answer.',
            'Propose one strategy the startup could use to improve customer retention.',
        ]);
    }

    private function seedMcq(string $assessmentTitle, array $questions): void
    {
        $assessment = Assessment::where('title', $assessmentTitle)->firstOrFail();

        foreach ($questions as $order => [$text, $options, $correctIndex]) {

            $question = AssessmentQuestion::create([
                'assessment_id' => $assessment->id,
                'type' => 'mcq',
                'question_text' => $text,
                'order' => $order + 1,
            ]);

            foreach ($options as $index => $option) {
                AssessmentChoice::create([
                    'question_id' => $question->id,
                    'option' => $option,
                    'is_correct' => $index === $correctIndex,
                    'order' => $index + 1,
                ]);
            }
        }
    }

    private function seedDescriptive(string $assessmentTitle, array $questions, ?array $config = null): void
    {
        $assessment = Assessment::where('title', $assessmentTitle)->firstOrFail();

        foreach ($questions as $order => $text) {
            AssessmentQuestion::create([
                'assessment_id' => $assessment->id,
                'type' => 'descriptive',
                'question_text' => $text,
                'config' => $config,
                'order' => $order + 1,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssessmentAnswerSeeder extends Seeder
{
    public function run(): void
    {
        // Basic Math Test: user1 completed with 4/5 (last question wrong).
        $this->mcqAnswers('Basic Math Test', 'user1@gmail.com', [1, 1, 2, 1, 0]);
        // user2 is still in progress: only the first two questions answered.
        $this->mcqAnswers('Basic Math Test', 'user2@gmail.com', [1, 1], ungraded: true);

        // Advanced Algebra Quiz: user1 completed with 3 correct / 2 wrong (2.5/5 after negative marking).
        $this->mcqAnswers('Advanced Algebra Quiz', 'user1@gmail.com', [1, 1, 0, 1, 1]);
        $this->mcqAnswers('Advanced Algebra Quiz', 'user2@gmail.com', [1], ungraded: true);

        // Flexible Practice Test: user2 completed with a perfect score.
        $this->mcqAnswers('Flexible Practice Test', 'user2@gmail.com', [1, 0, 2, 2, 2]);
        $this->mcqAnswers('Flexible Practice Test', 'user4@gmail.com', [1, 0, 2], ungraded: true);

        // General Knowledge Descriptive: manual grading, so is_correct stays null either way.
        $this->descriptiveAnswers('General Knowledge Descriptive', 'user1@gmail.com', [
            'The sun heats water, which evaporates, condenses into clouds, and falls back as precipitation.',
            'Burning fossil fuels, deforestation, and industrial emissions raise greenhouse gas levels.',
            'The legislature makes laws, the executive enforces them, and the judiciary interprets them.',
        ]);
        $this->descriptiveAnswers('General Knowledge Descriptive', 'user3@gmail.com', [
            'The sun heats water and it evaporates into the atmosphere.',
        ]);

        // Startup Case Study: manual grading.
        $this->descriptiveAnswers('Startup Case Study', 'user1@gmail.com', [
            'The biggest risk is customer acquisition cost outpacing lifetime value, since the model relies on paid channels.',
            'Introduce a loyalty program with usage-based rewards to encourage repeat engagement.',
        ]);
    }

    // $answerIndexes maps each question (in order) to the chosen choice's index (0-based).
    private function mcqAnswers(string $assessmentTitle, string $email, array $answerIndexes, bool $ungraded = false): void
    {
        $attempt = $this->attempt($assessmentTitle, $email);

        $questions = Assessment::where('title', $assessmentTitle)
            ->firstOrFail()
            ->questions()
            ->orderBy('order')
            ->with('choices')
            ->get();

        foreach ($answerIndexes as $i => $choiceIndex) {

            $question = $questions[$i];
            $choice = $question->choices->sortBy('order')->values()[$choiceIndex];

            AssessmentAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'answer' => ['choice_id' => $choice->id],
                'is_correct' => $ungraded ? null : $choice->is_correct,
            ]);
        }
    }

    private function descriptiveAnswers(string $assessmentTitle, string $email, array $texts): void
    {
        $attempt = $this->attempt($assessmentTitle, $email);

        $questions = Assessment::where('title', $assessmentTitle)
            ->firstOrFail()
            ->questions()
            ->orderBy('order')
            ->get();

        foreach ($texts as $i => $text) {
            AssessmentAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $questions[$i]->id,
                'answer' => ['text' => $text],
                'is_correct' => null,
            ]);
        }
    }

    private function attempt(string $assessmentTitle, string $email): AssessmentAttempt
    {
        $assessment = Assessment::where('title', $assessmentTitle)->firstOrFail();
        $user = User::where('email', $email)->firstOrFail();

        $batchIds = Batch::where('assessment_id', $assessment->id)->pluck('id');

        return AssessmentAttempt::where('user_id', $user->id)
            ->whereIn('batch_id', $batchIds)
            ->firstOrFail();
    }
}

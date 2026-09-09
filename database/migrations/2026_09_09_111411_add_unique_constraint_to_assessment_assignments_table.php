<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A prior migration dropped the unique index on (assessment_id, user_id)
     * to allow re-exams (a new batch for the same assessment/user), but never
     * replaced it with one that also accounts for batch_id. Without any
     * uniqueness constraint at the database level, concurrent "assign users"
     * requests can race past the application's check-then-create logic in
     * AssessmentAssignmentController::store() and insert duplicate rows for
     * the same assessment/user/batch.
     */
    public function up(): void
    {
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->unique(
                ['assessment_id', 'user_id', 'batch_id'],
                'assessment_assignments_assessment_user_batch_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->dropUnique('assessment_assignments_assessment_user_batch_unique');
        });
    }
};

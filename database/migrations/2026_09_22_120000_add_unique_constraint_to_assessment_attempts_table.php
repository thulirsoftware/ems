<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Closes the same check-then-create race that
// 2026_09_09_111411_add_unique_constraint_to_assessment_assignments_table
// already fixed for assignments: two concurrent start() calls for the same
// assessment/user/batch could both pass the "no existing attempt" check and
// both insert, producing two attempt rows for one exam. StudentAttemptService
// now catches the resulting UniqueConstraintViolationException and resumes
// the winning attempt instead of erroring.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->unique(['assessment_id', 'user_id', 'batch_id'], 'assessment_attempts_assessment_user_batch_unique');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropUnique('assessment_attempts_assessment_user_batch_unique');
        });
    }
};

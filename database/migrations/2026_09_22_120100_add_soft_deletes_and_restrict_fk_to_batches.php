<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Batch was the one exam-integrity table without SoftDeletes, while both
// assessment_assignments.batch_id and assessment_attempts.batch_id were
// ON DELETE CASCADE — unlike every sibling FK in the schema (admin_id,
// assessment_id, user_id, question_id, attempt_id), which is RESTRICT paired
// with SoftDeletes on the owning model. BatchService::delete() already
// guards against deleting a batch with attempts via an app-level isLocked()
// check, but nothing backed that up at the DB level: any other hard-delete
// path (tinker, a maintenance script, a future bulk-cleanup job) would
// silently and permanently cascade away real submitted attempts/scores and
// assignment history. This brings Batch in line with the rest of the schema.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->dropForeign('fk_batch');
            $table->foreign('batch_id', 'fk_batch')
                ->references('id')->on('batches')
                ->restrictOnDelete();
        });

        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropForeign('assessment_attempts_batch_id_foreign');
            $table->foreign('batch_id', 'assessment_attempts_batch_id_foreign')
                ->references('id')->on('batches')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropForeign('assessment_attempts_batch_id_foreign');
            $table->foreign('batch_id', 'assessment_attempts_batch_id_foreign')
                ->references('id')->on('batches')
                ->cascadeOnDelete();
        });

        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->dropForeign('fk_batch');
            $table->foreign('batch_id', 'fk_batch')
                ->references('id')->on('batches')
                ->cascadeOnDelete();
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

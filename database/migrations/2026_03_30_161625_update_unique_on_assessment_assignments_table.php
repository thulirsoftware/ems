<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_assignments', function (Blueprint $table) {

            // drop old unique (assessment_id + user_id)
            $table->dropUnique('assessment_assignments_assessment_id_user_id_unique');

            // add new unique (assessment_id + user_id + batch_id)
            $table->unique(
                ['assessment_id', 'user_id', 'batch_id'],
                'assessment_assignments_unique_batch'
            );
        });
    }

    public function down(): void
    {
        Schema::table('assessment_assignments', function (Blueprint $table) {

            // drop new unique
            $table->dropUnique('assessment_assignments_unique_batch');

            // restore old unique
            $table->unique(
                ['assessment_id', 'user_id'],
                'assessment_assignments_assessment_id_user_id_unique'
            );
        });
    }
};
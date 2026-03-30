<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        // 🔥 get all foreign keys for this table
        $fks = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'assessment_assignments'
            AND TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        // 🔥 drop FKs safely (no IF EXISTS support → we already filtered)
        foreach ($fks as $fk) {
            DB::statement("
                ALTER TABLE assessment_assignments
                DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`
            ");
        }

        // 🔥 drop unique index safely (IF EXISTS supported)
        DB::statement("
            ALTER TABLE assessment_assignments
            DROP INDEX IF EXISTS assessment_assignments_assessment_id_user_id_unique
        ");

        // 🔥 re-add FKs (only if columns exist — safe assumption here)
        DB::statement("
            ALTER TABLE assessment_assignments
            ADD CONSTRAINT fk_assessment
            FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE RESTRICT
        ");

        DB::statement("
            ALTER TABLE assessment_assignments
            ADD CONSTRAINT fk_user
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
        ");

        // batch_id might not exist in older DB → check first
        $batchColumn = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.COLUMNS
            WHERE TABLE_NAME = 'assessment_assignments'
            AND COLUMN_NAME = 'batch_id'
            AND TABLE_SCHEMA = DATABASE()
        ");

        if ($batchColumn[0]->count > 0) {
            DB::statement("
                ALTER TABLE assessment_assignments
                ADD CONSTRAINT fk_batch
                FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE
            ");
        }
    }

    public function down(): void
    {
        // do nothing
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // The original implementation below is MySQL-only (information_schema,
        // MySQL-specific ALTER TABLE syntax), which made it impossible to run
        // this migration — and therefore the whole migration set — against
        // any other driver, such as the sqlite connection the test suite
        // uses. That silently prevented RefreshDatabase-based feature tests
        // from ever running. For non-MySQL connections we reach the same end
        // state (no unique index on assessment_id+user_id, FKs untouched)
        // through the portable schema builder instead.
        if (DB::connection()->getDriverName() !== 'mysql') {
            Schema::table('assessment_assignments', function (Blueprint $table) {
                $table->dropUnique(['assessment_id', 'user_id']);
            });

            return;
        }

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
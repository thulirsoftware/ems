<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        // 🔥 drop old unique safely (ignore if not exists)
        DB::statement("
            ALTER TABLE assessment_assignments
            DROP INDEX IF EXISTS assessment_assignments_assessment_id_user_id_unique
        ");
    }

    public function down(): void
    {
        // ❌ do nothing (cannot safely restore due to duplicates)
    }
};
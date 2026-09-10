<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('scheduling_type', ['batch_wise', 'fixed', 'flexible'])
                ->default('fixed')
                ->after('assessment_type_id');
        });

        DB::table('assessments')->where('is_batch_wise', true)->update(['scheduling_type' => 'batch_wise']);
        DB::table('assessments')->where('is_flexible', true)->update(['scheduling_type' => 'flexible']);

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['is_batch_wise', 'is_flexible']);
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->boolean('is_batch_wise')->default(false)->after('assessment_type_id');
            $table->boolean('is_flexible')->default(false)->after('is_batch_wise');
        });

        DB::table('assessments')->where('scheduling_type', 'batch_wise')->update(['is_batch_wise' => true]);
        DB::table('assessments')->where('scheduling_type', 'flexible')->update(['is_flexible' => true]);

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('scheduling_type');
        });
    }
};

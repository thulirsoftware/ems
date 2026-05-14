<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->boolean('is_flexible')
                ->default(false)
                ->after('is_batch_wise');
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->date('expiry_date')
                ->nullable()
                ->after('end_time');

            $table->integer('duration_minutes')
                ->nullable()
                ->after('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('is_flexible');
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn([
                'expiry_date',
                'duration_minutes',
            ]);
        });
    }
};
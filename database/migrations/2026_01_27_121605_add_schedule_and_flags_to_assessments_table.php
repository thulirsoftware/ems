<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->timestamp('publish_date')->nullable()->after('description');
            $table->timestamp('start_time')->nullable()->after('publish_date');
            $table->timestamp('end_time')->nullable()->after('start_time');

            $table->boolean('shuffle')->default(false)->after('end_time');
            $table->boolean('is_library')->default(false)->after('shuffle');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn([
                'publish_date',
                'start_time',
                'end_time',
                'shuffle',
                'is_library',
            ]);
        });
    }
};

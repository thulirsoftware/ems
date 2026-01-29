<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->string('score')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->float('score')->nullable()->change();
        });
    }
};

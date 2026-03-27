<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'user_ids')) {
                $table->dropColumn('user_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->json('user_ids')->nullable();
        });
    }
};

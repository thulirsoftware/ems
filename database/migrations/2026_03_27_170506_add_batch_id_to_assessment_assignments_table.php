<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('batches')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropColumn('batch_id');
        });
    }
};

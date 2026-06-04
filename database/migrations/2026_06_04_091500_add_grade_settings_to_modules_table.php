<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->unsignedTinyInteger('cc_count')->default(3)->after('description');
            $table->decimal('efm_max_score', 5, 2)->default(40)->after('cc_count');
            $table->string('grade_formula')->default('moy_module = (moy_cc + efm) / 3')->after('efm_max_score');
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['cc_count', 'efm_max_score', 'grade_formula']);
        });
    }
};

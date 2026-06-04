<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('behavior_score_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['absence_non_justified', 'late_repeated', 'manual_adjustment', 'justification_accepted']);
            $table->decimal('points', 5, 2);
            $table->decimal('old_score', 5, 2);
            $table->decimal('new_score', 5, 2);
            $table->string('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('behavior_score_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('formateur_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 20);
            $table->decimal('coefficient', 5, 2)->nullable();
            $table->decimal('max_score', 5, 2)->default(20);
            $table->date('evaluation_date');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'module_id', 'type']);
            $table->index(['status', 'type']);
        });

        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->boolean('absent')->default(false);
            $table->string('observation')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_id', 'stagiaire_id']);
        });

        Schema::create('module_grade_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('formateur_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('cc1', 5, 2)->nullable();
            $table->decimal('cc2', 5, 2)->nullable();
            $table->decimal('cc3', 5, 2)->nullable();
            $table->decimal('moy_cc', 5, 2)->nullable();
            $table->decimal('efm', 5, 2)->nullable();
            $table->decimal('moy_module', 5, 2)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['stagiaire_id', 'group_id', 'module_id']);
            $table->index(['group_id', 'module_id', 'status']);
        });

        Schema::create('grade_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_grade_id')->constrained('student_grades')->cascadeOnDelete();
            $table->decimal('old_score', 5, 2)->nullable();
            $table->decimal('new_score', 5, 2)->nullable();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->string('reason');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_audit_logs');
        Schema::dropIfExists('module_grade_summaries');
        Schema::dropIfExists('student_grades');
        Schema::dropIfExists('evaluations');
    }
};

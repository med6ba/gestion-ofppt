<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filieres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filiere_id')->constrained('filieres')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('year_level')->nullable();
            $table->unsignedSmallInteger('capacity')->default(30);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('role')->constrained('groups')->nullOnDelete();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedSmallInteger('capacity')->default(30);
            $table->enum('type', ['classroom', 'lab', 'workshop', 'amphi'])->default('classroom');
            $table->timestamps();
        });

        Schema::create('formateur_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formateur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->timestamps();
            $table->unique(['formateur_id', 'group_id', 'module_id']);
        });

        Schema::create('formateur_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formateur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['formateur_id', 'module_id']);
        });

        Schema::create('timetable_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('formateur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->index();
            $table->date('starts_on')->index();
            $table->date('ends_on')->index();
            $table->unsignedTinyInteger('week_number')->nullable()->index();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->enum('status', ['scheduled', 'changed', 'cancelled'])->default('scheduled')->index();
            $table->text('change_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_session_id')->constrained('timetable_sessions')->cascadeOnDelete();
            $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['present', 'absent', 'late', 'justified'])->default('present')->index();
            $table->enum('method', ['manual', 'qr', 'code'])->default('manual')->index();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
            $table->unique(['timetable_session_id', 'stagiaire_id']);
        });

        Schema::create('qr_attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_session_id')->constrained('timetable_sessions')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('secure_token', 96)->unique();
            $table->string('short_code', 12)->unique();
            $table->timestamp('expires_at')->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('attendance_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('timetable_session_id')->nullable()->constrained('timetable_sessions')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('reason');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['private', 'administrative'])->default('private')->index();
            $table->string('title')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_read')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->enum('level', ['Low', 'Medium', 'High'])->default('Low')->index();
            $table->unsignedSmallInteger('absence_count')->default(0);
            $table->unsignedSmallInteger('late_count')->default(0);
            $table->unsignedSmallInteger('suspicious_count')->default(0);
            $table->decimal('attendance_rate', 5, 2)->default(100);
            $table->json('reasons')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('credential_id')->unique();
            $table->text('public_key')->nullable();
            $table->unsignedInteger('counter')->default(0);
            $table->json('transports')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkeys');
        Schema::dropIfExists('risk_scores');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('attendance_attempts');
        Schema::dropIfExists('qr_attendance_sessions');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('timetable_sessions');
        Schema::dropIfExists('formateur_module');
        Schema::dropIfExists('formateur_group');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('modules');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
        });
        Schema::dropIfExists('groups');
        Schema::dropIfExists('filieres');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo')->nullable()->after('password');
            $table->enum('gender', ['male', 'female', 'unknown'])->nullable()->after('profile_photo');
        });

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE conversations CHANGE type type ENUM('private', 'group', 'administrative') DEFAULT 'private'");
        }

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->string('role_in_conversation')->nullable()->after('user_id');
        });

        if (!in_array($driver, ['sqlite'], true)) {
            Schema::table('messages', function (Blueprint $table) {
                $table->text('body')->nullable()->change();
            });
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('body');
            $table->enum('attachment_type', ['image', 'pdf'])->nullable()->after('attachment_path');
            $table->string('attachment_original_name')->nullable()->after('attachment_type');
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::dropIfExists('app_settings');

        Schema::table('messages', function (Blueprint $table) {
            // we cannot easily revert a nullable to non-nullable if there are nulls, but we can drop columns
            $table->dropColumn(['attachment_path', 'attachment_type', 'attachment_original_name']);
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn('role_in_conversation');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('module_id');
            $table->dropConstrainedForeignId('group_id');
        });
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE conversations CHANGE type type ENUM('private', 'administrative') DEFAULT 'private'");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_photo', 'gender']);
        });
    }
};

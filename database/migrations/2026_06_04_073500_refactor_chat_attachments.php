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
        Schema::table('messages', function (Blueprint $table) {
            // Drop old enum type
            $table->dropColumn('attachment_type');
            
            // Add new flexible columns
            $table->string('attachment_category')->nullable()->after('attachment_path');
            $table->string('attachment_mime_type')->nullable()->after('attachment_original_name');
            $table->unsignedInteger('attachment_size')->nullable()->after('attachment_mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_category', 'attachment_mime_type', 'attachment_size']);
            $table->enum('attachment_type', ['image', 'pdf'])->nullable()->after('attachment_path');
        });
    }
};

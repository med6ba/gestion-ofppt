<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'cni')) {
                $table->string('cni', 40)->nullable()->after('registration_number')->unique();
            }

            if (!Schema::hasColumn('users', 'qr_login_token')) {
                $table->string('qr_login_token', 96)->nullable()->after('cni')->unique();
            }

            if (!Schema::hasColumn('users', 'badge_id')) {
                $table->string('badge_id', 40)->nullable()->after('qr_login_token')->unique();
            }
        });

        $this->backfillStagiaireBadgeCredentials();

        Schema::create('attestation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('filiere')->nullable();
            $table->string('cni', 40);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('surveillant_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->timestamps();
            $table->index(['stagiaire_id', 'status']);
        });

        Schema::create('absence_authorization_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
            $table->date('absence_date')->index();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('surveillant_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->timestamps();
            $table->index(['stagiaire_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_authorization_requests');
        Schema::dropIfExists('attestation_requests');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'badge_id')) {
                $table->dropUnique('users_badge_id_unique');
                $table->dropColumn('badge_id');
            }

            if (Schema::hasColumn('users', 'qr_login_token')) {
                $table->dropUnique('users_qr_login_token_unique');
                $table->dropColumn('qr_login_token');
            }

            if (Schema::hasColumn('users', 'cni')) {
                $table->dropUnique('users_cni_unique');
                $table->dropColumn('cni');
            }
        });
    }

    private function backfillStagiaireBadgeCredentials(): void
    {
        DB::table('users')
            ->where('role', 'stagiaire')
            ->orderBy('id')
            ->get(['id', 'qr_login_token', 'badge_id'])
            ->each(function (object $user) {
                $updates = [];

                if (blank($user->qr_login_token)) {
                    $updates['qr_login_token'] = $this->uniqueToken();
                }

                if (blank($user->badge_id)) {
                    $updates['badge_id'] = $this->uniqueBadgeId((int) $user->id);
                }

                if ($updates) {
                    $updates['updated_at'] = now();
                    DB::table('users')->where('id', $user->id)->update($updates);
                }
            });
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (DB::table('users')->where('qr_login_token', $token)->exists());

        return $token;
    }

    private function uniqueBadgeId(int $userId): string
    {
        do {
            $badgeId = 'SC-OFPPT-'.str_pad((string) $userId, 5, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(4));
        } while (DB::table('users')->where('badge_id', $badgeId)->exists());

        return $badgeId;
    }
};

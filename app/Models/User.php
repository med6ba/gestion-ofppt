<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_DIRECTEUR = 'directeur';
    public const ROLE_SURVEILLANT = 'surveillant';
    public const ROLE_FORMATEUR = 'formateur';
    public const ROLE_STAGIAIRE = 'stagiaire';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'group_id',
        'approval_status',
        'enabled',
        'phone', 'device_id',
        'registration_number',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'enabled' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function teachingGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'formateur_group', 'formateur_id', 'group_id')
            ->withPivot('module_id')
            ->withTimestamps();
    }

    public function teachingModules(): BelongsToMany
    {
        return $this->belongsToMany(TrainingModule::class, 'formateur_module', 'formateur_id', 'module_id')
            ->withTimestamps();
    }

    public function timetableSessions(): HasMany
    {
        return $this->hasMany(TimetableSession::class, 'formateur_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'stagiaire_id');
    }

    public function attendanceAttempts(): HasMany
    {
        return $this->hasMany(AttendanceAttempt::class, 'stagiaire_id');
    }

    public function riskScore(): HasOne
    {
        return $this->hasOne(RiskScore::class, 'stagiaire_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    public function isDirecteur(): bool
    {
        return $this->role === self::ROLE_DIRECTEUR;
    }

    public function isSurveillant(): bool
    {
        return $this->role === self::ROLE_SURVEILLANT;
    }

    public function isFormateur(): bool
    {
        return $this->role === self::ROLE_FORMATEUR;
    }

    public function isStagiaire(): bool
    {
        return $this->role === self::ROLE_STAGIAIRE;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_DIRECTEUR => 'directeur.dashboard',
            self::ROLE_SURVEILLANT => 'surveillant.dashboard',
            self::ROLE_FORMATEUR => 'formateur.dashboard',
            self::ROLE_STAGIAIRE => 'stagiaire.dashboard',
            default => 'login',
        };
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_DIRECTEUR => 'Directeur',
            self::ROLE_SURVEILLANT => 'Surveillant General',
            self::ROLE_FORMATEUR => 'Formateur',
            self::ROLE_STAGIAIRE => 'Stagiaire',
            default => ucfirst((string) $this->role),
        };
    }

    public function scopeRole($query, string|array $roles)
    {
        return $query->whereIn('role', (array) $roles);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }
}

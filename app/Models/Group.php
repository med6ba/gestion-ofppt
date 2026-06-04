<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = ['filiere_id', 'name', 'code', 'year_level', 'capacity'];

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class);
    }

    public function stagiaires(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_STAGIAIRE);
    }

    public function formateurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'formateur_group', 'group_id', 'formateur_id')
            ->withPivot('module_id')
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TimetableSession::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function gradeSummaries(): HasMany
    {
        return $this->hasMany(ModuleGradeSummary::class);
    }
}

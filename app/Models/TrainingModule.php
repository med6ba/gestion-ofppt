<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingModule extends Model
{
    protected $table = 'modules';

    protected $fillable = ['name', 'code', 'description', 'cc_count', 'efm_max_score', 'grade_formula'];

    protected function casts(): array
    {
        return [
            'cc_count' => 'integer',
            'efm_max_score' => 'decimal:2',
        ];
    }

    public function ccTypes(): array
    {
        return array_slice(['cc1', 'cc2', 'cc3'], 0, max(2, min(3, (int) ($this->cc_count ?? 3))));
    }

    public function evaluationTypes(): array
    {
        return [...$this->ccTypes(), 'efm'];
    }

    public function formateurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'formateur_module', 'module_id', 'formateur_id')
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TimetableSession::class, 'module_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'module_id');
    }

    public function gradeSummaries(): HasMany
    {
        return $this->hasMany(ModuleGradeSummary::class, 'module_id');
    }
}

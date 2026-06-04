<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleGradeSummary extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'stagiaire_id',
        'group_id',
        'module_id',
        'formateur_id',
        'cc1',
        'cc2',
        'cc3',
        'moy_cc',
        'efm',
        'moy_module',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'cc1' => 'decimal:2',
            'cc2' => 'decimal:2',
            'cc3' => 'decimal:2',
            'moy_cc' => 'decimal:2',
            'efm' => 'decimal:2',
            'moy_module' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function stagiaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stagiaire_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }

    public function formateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'formateur_id');
    }

    public function isComplete(): bool
    {
        return $this->moy_cc !== null && $this->efm !== null && $this->moy_module !== null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    public const TYPE_CC1 = 'cc1';
    public const TYPE_CC2 = 'cc2';
    public const TYPE_CC3 = 'cc3';
    public const TYPE_EFM = 'efm';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'group_id',
        'module_id',
        'formateur_id',
        'title',
        'type',
        'coefficient',
        'max_score',
        'evaluation_date',
        'status',
        'created_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'coefficient' => 'decimal:2',
            'max_score' => 'decimal:2',
            'evaluation_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public static function types(): array
    {
        return [self::TYPE_CC1, self::TYPE_CC2, self::TYPE_CC3, self::TYPE_EFM];
    }

    public static function maxScoreFor(string $type): int
    {
        return $type === self::TYPE_EFM ? 40 : 20;
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}

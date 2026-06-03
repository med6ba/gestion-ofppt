<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingModule extends Model
{
    protected $table = 'modules';

    protected $fillable = ['name', 'code', 'description'];

    public function formateurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'formateur_module', 'module_id', 'formateur_id')
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TimetableSession::class, 'module_id');
    }
}

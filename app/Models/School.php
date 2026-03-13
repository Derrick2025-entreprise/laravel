<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sigle',
        'description',
        'email',
        'telephone',
        'city',
        'region',
        'address',
        'website',
        'status',
        'admin_user_id',
        'parameters',
    ];

    protected $casts = [
        'parameters' => 'json',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}

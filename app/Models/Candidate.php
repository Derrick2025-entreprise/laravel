<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'prenom',
        'nom',
        'telephone',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'nationalite',
        'numero_identite',
        'type_identite',
        'photo_path',
        'adresse',
        'statut',
        'motif_rejet',
        'validated_at',
        'validated_by',
        'additional_info',
    ];

    protected $casts = [
        'additional_info' => 'array',
        'validated_at' => 'datetime',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidateExams(): HasMany
    {
        return $this->hasMany(CandidateExam::class);
    }

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

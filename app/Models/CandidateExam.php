<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateExam extends Model
{
    protected $fillable = [
        'candidate_id',
        'user_id',
        'exam_id', 
        'exam_filiere_id',
        'statut',
        'registered_at',
        'confirmed_at',
        'notes'
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(ExamFiliere::class, 'exam_filiere_id');
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class, 'candidate_exam_id', 'id');
    }
}

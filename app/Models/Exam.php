<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'school_id',
        'year',
        'session',
        'registration_start_date',
        'registration_end_date',
        'exam_date',
        'num_candidates',
        'registration_fee',
        'conditions',
        'status',
        'is_public',
    ];

    protected $casts = [
        'registration_start_date' => 'datetime',
        'registration_end_date' => 'datetime',
        'exam_date' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function filieres(): HasMany
    {
        return $this->hasMany(ExamFiliere::class);
    }

    public function candidateExams(): HasMany
    {
        return $this->hasMany(CandidateExam::class);
    }

    public function compositionCenters(): HasMany
    {
        return $this->hasMany(CompositionCenter::class);
    }
}

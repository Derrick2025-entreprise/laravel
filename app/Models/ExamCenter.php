<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sigle',
        'address',
        'city',
        'region',
        'capacity',
        'facilities',
        'contact_phone',
        'contact_email',
        'status',
        'coordinates',
        'description'
    ];

    protected $casts = [
        'facilities' => 'array',
        'coordinates' => 'array'
    ];

    /**
     * Relation avec les examens
     */
    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_exam_centers')
                    ->withPivot(['capacity_allocated', 'status'])
                    ->withTimestamps();
    }

    /**
     * Relation avec les inscriptions de candidats
     */
    public function candidateExams()
    {
        return $this->hasMany(CandidateExam::class);
    }

    /**
     * Scope pour les centres actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope par région
     */
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Obtenir la capacité disponible
     */
    public function getAvailableCapacityAttribute()
    {
        $allocated = $this->exams()->sum('exam_exam_centers.capacity_allocated');
        return $this->capacity - $allocated;
    }

    /**
     * Vérifier si le centre peut accueillir un nombre de candidats
     */
    public function canAccommodate($numberOfCandidates)
    {
        return $this->available_capacity >= $numberOfCandidates;
    }
}
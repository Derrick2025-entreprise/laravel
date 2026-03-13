<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Filiere extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'department_id',
        'school_id',
        'duration_years',
        'enrollment_fee',
        'tuition_fee',
        'capacity',
        'requirements',
        'status',
        'created_by'
    ];

    protected $casts = [
        'requirements' => 'array',
        'enrollment_fee' => 'decimal:2',
        'tuition_fee' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relation avec le département
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relation avec l'école
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Relation avec les étudiants
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Relation avec les examens
     */
    public function examFilieres()
    {
        return $this->hasMany(ExamFiliere::class, 'filiere_id');
    }

    /**
     * Relation avec le créateur
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope pour les filières actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope par département
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope par école
     */
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Obtenir le nombre d'étudiants inscrits
     */
    public function getEnrolledStudentsCountAttribute()
    {
        return $this->students()->where('status', 'enrolled')->count();
    }

    /**
     * Obtenir le taux de remplissage
     */
    public function getFillRateAttribute()
    {
        if ($this->capacity <= 0) return 0;
        return round(($this->enrolled_students_count / $this->capacity) * 100, 2);
    }

    /**
     * Obtenir les places disponibles
     */
    public function getAvailableSpotsAttribute()
    {
        return max(0, $this->capacity - $this->enrolled_students_count);
    }

    /**
     * Obtenir les statistiques de paiement
     */
    public function getPaymentStatsAttribute()
    {
        $students = $this->students()->with('payments')->get();
        
        return [
            'total_students' => $students->count(),
            'paid_students' => $students->filter(function($student) {
                return $student->payments->where('status', 'validated')->count() > 0;
            })->count(),
            'pending_payments' => $students->filter(function($student) {
                return $student->payments->where('status', 'pending')->count() > 0;
            })->count(),
            'total_revenue' => $students->sum(function($student) {
                return $student->payments->where('status', 'validated')->sum('amount');
            }),
            'expected_revenue' => $students->count() * ($this->enrollment_fee + $this->tuition_fee)
        ];
    }

    /**
     * Vérifier si la filière peut accepter de nouveaux étudiants
     */
    public function canAcceptNewStudents()
    {
        return $this->status === 'active' && $this->available_spots > 0;
    }
}
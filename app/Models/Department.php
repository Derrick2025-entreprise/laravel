<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'school_id',
        'head_of_department',
        'contact_email',
        'contact_phone',
        'status',
        'created_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relation avec l'école
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Relation avec les filières
     */
    public function filieres()
    {
        return $this->hasMany(Filiere::class);
    }

    /**
     * Relation avec les étudiants via les filières
     */
    public function students()
    {
        return $this->hasManyThrough(Student::class, Filiere::class);
    }

    /**
     * Relation avec le créateur
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope pour les départements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
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
    public function getStudentsCountAttribute()
    {
        return $this->students()->count();
    }

    /**
     * Obtenir le nombre de filières
     */
    public function getFilieresCountAttribute()
    {
        return $this->filieres()->count();
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
            'total_amount' => $students->sum(function($student) {
                return $student->payments->where('status', 'validated')->sum('amount');
            })
        ];
    }
}
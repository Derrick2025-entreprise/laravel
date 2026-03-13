<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'student_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'place_of_birth',
        'gender',
        'nationality',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'filiere_id',
        'department_id',
        'school_id',
        'enrollment_date',
        'academic_year',
        'status',
        'profile_photo',
        'documents'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'documents' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec la filière
     */
    public function filiere()
    {
        return $this->belongsTo(Filiere::class);
    }

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
     * Relation avec les paiements
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relation avec les documents
     */
    public function studentDocuments()
    {
        return $this->hasMany(StudentDocument::class);
    }

    /**
     * Relation avec les inscriptions aux examens
     */
    public function candidateExams()
    {
        return $this->hasMany(CandidateExam::class, 'candidate_id');
    }

    /**
     * Scope pour les étudiants actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'enrolled');
    }

    /**
     * Scope par filière
     */
    public function scopeByFiliere($query, $filiereId)
    {
        return $query->where('filiere_id', $filiereId);
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
     * Scope par année académique
     */
    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    /**
     * Obtenir le nom complet
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Obtenir l'âge
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Obtenir le statut de paiement
     */
    public function getPaymentStatusAttribute()
    {
        $validatedPayments = $this->payments()->where('status', 'validated')->sum('amount');
        $totalRequired = ($this->filiere->enrollment_fee ?? 0) + ($this->filiere->tuition_fee ?? 0);
        
        if ($validatedPayments >= $totalRequired) {
            return 'paid';
        } elseif ($validatedPayments > 0) {
            return 'partial';
        } else {
            return 'unpaid';
        }
    }

    /**
     * Obtenir le montant payé
     */
    public function getPaidAmountAttribute()
    {
        return $this->payments()->where('status', 'validated')->sum('amount');
    }

    /**
     * Obtenir le montant restant à payer
     */
    public function getRemainingAmountAttribute()
    {
        $totalRequired = ($this->filiere->enrollment_fee ?? 0) + ($this->filiere->tuition_fee ?? 0);
        return max(0, $totalRequired - $this->paid_amount);
    }

    /**
     * Générer un numéro d'étudiant unique
     */
    public static function generateStudentNumber($schoolId, $year = null)
    {
        $year = $year ?? date('Y');
        $school = School::find($schoolId);
        $schoolCode = $school ? $school->sigle : 'SGE';
        
        $lastStudent = static::where('student_number', 'like', $schoolCode . $year . '%')
                            ->orderBy('student_number', 'desc')
                            ->first();
        
        if ($lastStudent) {
            $lastNumber = intval(substr($lastStudent->student_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $schoolCode . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Vérifier si l'étudiant a tous les documents requis
     */
    public function hasRequiredDocuments()
    {
        $requiredDocs = ['birth_certificate', 'photo', 'academic_transcript'];
        $uploadedDocs = $this->studentDocuments()->pluck('document_type')->toArray();
        
        return empty(array_diff($requiredDocs, $uploadedDocs));
    }

    /**
     * Obtenir le pourcentage de complétion du profil
     */
    public function getProfileCompletionAttribute()
    {
        $requiredFields = [
            'first_name', 'last_name', 'email', 'phone', 'date_of_birth',
            'place_of_birth', 'gender', 'nationality', 'address'
        ];
        
        $completedFields = 0;
        foreach ($requiredFields as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }
        
        $documentsScore = $this->hasRequiredDocuments() ? 1 : 0;
        
        return round((($completedFields + $documentsScore) / (count($requiredFields) + 1)) * 100, 2);
    }
}
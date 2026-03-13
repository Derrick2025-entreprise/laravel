<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'student_payments';

    protected $fillable = [
        'student_id',
        'user_id',
        'payment_type',
        'amount',
        'payment_method',
        'reference_number',
        'transaction_id',
        'receipt_file',
        'status',
        'payment_date',
        'validated_at',
        'validated_by',
        'notes',
        'academic_year',
        // Nouveaux champs pour paiements mobiles
        'mobile_number',
        'bank_name',
        'account_number',
        'transaction_reference',
        'gateway_response',
        'payment_url'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'validated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relation avec l'étudiant
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le validateur
     */
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Scope pour les paiements validés
     */
    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    /**
     * Scope pour les paiements en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope par type de paiement
     */
    public function scopeByType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    /**
     * Scope par année académique
     */
    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    /**
     * Générer un numéro de référence unique
     */
    public static function generateReferenceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $lastPayment = static::where('reference_number', 'like', "PAY{$year}{$month}%")
                            ->orderBy('reference_number', 'desc')
                            ->first();
        
        if ($lastPayment) {
            $lastNumber = intval(substr($lastPayment->reference_number, -6));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return "PAY{$year}{$month}" . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Valider le paiement
     */
    public function validate($validatorId, $notes = null)
    {
        $this->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => $validatorId,
            'notes' => $notes
        ]);
    }

    /**
     * Rejeter le paiement
     */
    public function reject($validatorId, $notes)
    {
        $this->update([
            'status' => 'rejected',
            'validated_at' => now(),
            'validated_by' => $validatorId,
            'notes' => $notes
        ]);
    }
}
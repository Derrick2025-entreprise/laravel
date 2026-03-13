<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompositionCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'adresse',
        'ville',
        'capacite',
        'responsable',
        'telephone',
        'email',
        'actif'
    ];

    protected $casts = [
        'actif' => 'boolean',
        'capacite' => 'integer'
    ];

    /**
     * Examens organisés dans ce centre
     */
    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_composition_centers');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamFiliere extends Model
{
    protected $fillable = [
        'exam_id',
        'filiere_name',
        'filiere_code',
        'quota',
        'registered',
        'description'
    ];

    protected $casts = [
        'quota' => 'integer',
        'registered' => 'integer'
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}

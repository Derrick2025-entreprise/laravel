<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSubmissionCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sigle',
        'address',
        'city',
        'region',
        'phone',
        'email',
        'opening_hours',
        'accepted_documents',
        'directions',
        'latitude',
        'longitude',
        'is_active'
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'accepted_documents' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean'
    ];

    /**
     * Scope pour les centres actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope par région
     */
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope par ville
     */
    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Obtenir les horaires formatés
     */
    public function getFormattedOpeningHoursAttribute()
    {
        if (!$this->opening_hours) {
            return 'Horaires non spécifiés';
        }

        $formatted = [];
        foreach ($this->opening_hours as $day => $hours) {
            if ($hours['closed']) {
                $formatted[] = ucfirst($day) . ': Fermé';
            } else {
                $formatted[] = ucfirst($day) . ': ' . $hours['open'] . ' - ' . $hours['close'];
            }
        }

        return implode(', ', $formatted);
    }

    /**
     * Vérifier si le centre est ouvert maintenant
     */
    public function isOpenNow()
    {
        if (!$this->opening_hours) {
            return false;
        }

        $currentDay = strtolower(date('l')); // lundi, mardi, etc.
        $currentTime = date('H:i');

        if (!isset($this->opening_hours[$currentDay])) {
            return false;
        }

        $dayHours = $this->opening_hours[$currentDay];
        
        if ($dayHours['closed']) {
            return false;
        }

        return $currentTime >= $dayHours['open'] && $currentTime <= $dayHours['close'];
    }

    /**
     * Obtenir la distance depuis des coordonnées
     */
    public function getDistanceFrom($latitude, $longitude)
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadius = 6371; // Rayon de la Terre en km

        $latFrom = deg2rad($latitude);
        $lonFrom = deg2rad($longitude);
        $latTo = deg2rad($this->latitude);
        $lonTo = deg2rad($this->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}

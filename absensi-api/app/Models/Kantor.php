<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'alamat',
        'latitude',
        'longitude',
        'radius',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius' => 'float',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Helper methods
    public function calculateDistance($lat, $lon)
    {
        $earthRadius = 6371000; // dalam meter

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($lat);
        $lonTo = deg2rad($lon);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }

    public function isWithinRadius($lat, $lon)
    {
        $distance = $this->calculateDistance($lat, $lon);
        return $distance <= $this->radius;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
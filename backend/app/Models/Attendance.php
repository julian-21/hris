<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal',
        'jadwal_checkin',
        'jadwal_checkout',
        'check_in',
        'check_out',
        'latitude',
        'longitude',
        'schedule_id',
        'kantor_id',
        'lama_lembur',
        'makan_lembur',
        'alasan_lembur',
        'alasan_cuti',
        'lama_cuti',
        'jeniscuti_id',
        'status_cuti',
        'is_holiday',
        'out_of_office', // Tambahan
        'out_of_office_reason', // Tambahan
    ];

    protected $casts = [
        'tanggal' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'is_holiday' => 'boolean',
        'out_of_office' => 'boolean', // Tambahan
        'lama_cuti' => 'float',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kantor()
    {
        return $this->belongsTo(Kantor::class);
    }

    // Helper methods
    public function isLate()
    {
        if (!$this->check_in || !$this->jadwal_checkin) {
            return false;
        }

        $checkInTime = $this->check_in->format('H:i:s');
        return $checkInTime > $this->jadwal_checkin;
    }

    public function getWorkingHours()
    {
        if (!$this->check_in || !$this->check_out) {
            return 0;
        }

        return $this->check_out->diffInHours($this->check_in);
    }

    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        // Haversine formula untuk menghitung jarak
        $earthRadius = 6371000; // dalam meter

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }

    public function isWithinOfficeRadius()
    {
        if (!$this->kantor || !$this->latitude || !$this->longitude) {
            return false;
        }

        $distance = $this->calculateDistance(
            $this->latitude,
            $this->longitude,
            $this->kantor->latitude,
            $this->kantor->longitude
        );

        return $distance <= $this->kantor->radius;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Lembur extends Model
{
    use HasFactory;

    protected $table = 'lemburs';

    protected $fillable = [
        'tanggal_lembur',
        'lama_lembur',
        'alasan_lembur',
        'user_id',
        'status',
        'sisa_waktu_claim',
        'final_status',
        'expire_at',
        'is_expired',
    ];

    protected $casts = [
        'tanggal_lembur' => 'date',
        'lama_lembur' => 'integer',
        'sisa_waktu_claim' => 'integer',
        'expire_at' => 'date',
        'is_expired' => 'boolean',
    ];

    protected $appends = ['status_label', 'final_status_label'];

    // Constants untuk status
    const STATUS_WAITING = 'waiting';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';

    const STATUSES = [
        self::STATUS_WAITING => 'Menunggu',
        self::STATUS_ACCEPTED => 'Diterima',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    // TAMBAHKAN INI: Auto set expire_at saat create
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lembur) {
            if (!$lembur->expire_at) {
                $lembur->expire_at = Carbon::parse($lembur->tanggal_lembur)
                    ->addMonths(3);
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getFinalStatusLabelAttribute()
    {
        return self::STATUSES[$this->final_status] ?? $this->final_status;
    }

    public function getLamaLemburFormatAttribute()
    {
        $hours = floor($this->lama_lembur / 60);
        $minutes = $this->lama_lembur % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours} jam {$minutes} menit";
        } elseif ($hours > 0) {
            return "{$hours} jam";
        } else {
            return "{$minutes} menit";
        }
    }

    public function getSisaWaktuClaimFormatAttribute()
    {
        $hours = floor($this->sisa_waktu_claim / 60);
        $minutes = $this->sisa_waktu_claim % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours} jam {$minutes} menit";
        } elseif ($hours > 0) {
            return "{$hours} jam";
        } else {
            return "{$minutes} menit";
        }
    }

    // TAMBAHKAN accessor untuk days_until_expire dan is_expiring_soon
    public function getDaysUntilExpireAttribute()
    {
        if (!$this->expire_at || $this->is_expired) {
            return 0;
        }
        
        $now = Carbon::now();
        $expireDate = Carbon::parse($this->expire_at);
        
        if ($expireDate->isPast()) {
            return 0;
        }
        
        return $now->diffInDays($expireDate);
    }

    public function getIsExpiringSoonAttribute()
    {
        return $this->days_until_expire > 0 && 
               $this->days_until_expire <= 30 &&
               !$this->is_expired;
    }

    // Scopes
    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeFinalWaiting($query)
    {
        return $query->where('final_status', self::STATUS_WAITING);
    }

    public function scopeFinalAccepted($query)
    {
        return $query->where('final_status', self::STATUS_ACCEPTED);
    }

    public function scopeFinalRejected($query)
    {
        return $query->where('final_status', self::STATUS_REJECTED);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInMonth($query, $year, $month)
    {
        return $query->whereYear('tanggal_lembur', $year)
                    ->whereMonth('tanggal_lembur', $month);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_lembur', [$startDate, $endDate]);
    }

    // TAMBAHKAN scope active
    public function scopeActive($query)
    {
        return $query->where('is_expired', false);
    }

    public function scopeExpiringSoon($query)
    {
        return $query->where('is_expired', false)
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', Carbon::now()->addDays(30))
            ->where('expire_at', '>', Carbon::now());
    }

    // Helper Methods
    public function canBeEdited()
    {
        return $this->status === self::STATUS_WAITING && !$this->is_expired;
    }

    public function canBeDeleted()
    {
        return $this->status === self::STATUS_WAITING && !$this->is_expired;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isFinalApproved()
    {
        return $this->final_status === self::STATUS_ACCEPTED;
    }

    public function hasSisaWaktuClaim()
    {
        return $this->sisa_waktu_claim > 0;
    }

    public function markAsExpired()
    {
        $this->update([
            'is_expired' => true,
            'sisa_waktu_claim' => 0,
        ]);
    }

    // Static Methods
    public static function getTotalLemburByUser($userId, $year = null, $month = null)
    {
        $query = self::where('user_id', $userId)
                    ->where('status', self::STATUS_ACCEPTED);

        if ($year) {
            $query->whereYear('tanggal_lembur', $year);
        }

        if ($month) {
            $query->whereMonth('tanggal_lembur', $month);
        }

        return $query->sum('lama_lembur');
    }

    public static function getTotalSisaClaimByUser($userId)
    {
        return self::where('user_id', $userId)
                  ->where('status', self::STATUS_ACCEPTED)
                  ->where('is_expired', false)
                  ->sum('sisa_waktu_claim');
    }

    public static function getLemburStatistics($userId, $startDate = null, $endDate = null)
    {
        $query = self::where('user_id', $userId);

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_lembur', [$startDate, $endDate]);
        }

        return [
            'total_lembur' => $query->sum('lama_lembur') ?? 0,
            'total_waiting' => (clone $query)->where('status', self::STATUS_WAITING)->count(),
            'total_accepted' => (clone $query)->where('status', self::STATUS_ACCEPTED)->count(),
            'total_rejected' => (clone $query)->where('status', self::STATUS_REJECTED)->count(),
            'total_sisa_claim' => (clone $query)->where('is_expired', false)->sum('sisa_waktu_claim') ?? 0,
            'total_expired' => (clone $query)->where('is_expired', true)->count(),
            'total_expiring_soon' => (clone $query)->expiringSoon()->count(),
        ];
    }

    public static function checkExpiredLemburs()
    {
        $expired = self::where('is_expired', false)
            ->whereNotNull('expire_at')
            ->where('expire_at', '<', Carbon::now())
            ->get();

        foreach ($expired as $lembur) {
            $lembur->markAsExpired();
        }

        return $expired->count();
    }
}
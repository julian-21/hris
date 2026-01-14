<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LemburClaim extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'lembur_claims';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'time',
        'date',
        'user_id',
        'status',
        'reject_reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date' => 'date',
        'time' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['status_label'];

    /**
     * Status constants
     */
    const STATUS_WAITING = 'waiting';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Relationships
     */
    
    /**
     * Get the user that owns the lembur claim.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessors
     */
    
    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_WAITING => 'Menunggu',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Query Scopes
     */
    
    /**
     * Scope a query to only include waiting claims.
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    /**
     * Scope a query to only include approved claims.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope a query to only include rejected claims.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by month and year.
     */
    public function scopeByMonthYear($query, $month, $year)
    {
        return $query->whereMonth('date', $month)
                     ->whereYear('date', $year);
    }

    /**
     * Methods
     */
    
    /**
     * Check if the claim can be edited.
     */
    public function canBeEdited()
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * Check if the claim can be deleted.
     */
    public function canBeDeleted()
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * Approve the claim.
     */
    public function approve()
    {
        return $this->update([
            'status' => self::STATUS_APPROVED
        ]);
    }

    /**
     * Reject the claim with optional reason.
     */
    public function reject($reason = null)
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'reject_reason' => $reason,
        ]);
    }

    /**
     * Check if the claim is waiting.
     */
    public function isWaiting()
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * Check if the claim is approved.
     */
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the claim is rejected.
     */
    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Static Methods
     */
    
    /**
     * Get total approved claims for a user.
     */
    public static function getTotalApprovedTime($userId, $startDate = null, $endDate = null)
    {
        $query = self::where('user_id', $userId)
                     ->where('status', self::STATUS_APPROVED);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        return $query->sum('time');
    }

    /**
     * Get claim statistics for a user.
     */
    public static function getClaimStatistics($userId, $startDate = null, $endDate = null)
    {
        $query = self::where('user_id', $userId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        return [
            'total_claims' => (clone $query)->count(),
            'total_waiting' => (clone $query)->where('status', self::STATUS_WAITING)->count(),
            'total_approved' => (clone $query)->where('status', self::STATUS_APPROVED)->count(),
            'total_rejected' => (clone $query)->where('status', self::STATUS_REJECTED)->count(),
            'total_approved_time' => (clone $query)->where('status', self::STATUS_APPROVED)->sum('time'),
        ];
    }
}
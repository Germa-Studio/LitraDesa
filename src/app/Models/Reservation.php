<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'reserved_at',
        'expires_at',
        'notified_at',
        'picked_up_at',
        'cancellation_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'notified_at' => 'datetime',
        'picked_up_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Reservation $reservation) {
            if (empty($reservation->reserved_at)) {
                $reservation->reserved_at = now();
            }
            
            if (empty($reservation->expires_at)) {
                // 24-hour reservation window
                $reservation->expires_at = now()->addHours(24);
            }
        });
    }

    /**
     * Get the user who made the reservation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the book that was reserved.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Check if the reservation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && $this->status === 'pending';
    }

    /**
     * Check if the reservation is active (pending or ready).
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'ready']);
    }

    /**
     * Mark reservation as ready for pickup.
     */
    public function markAsReady(): void
    {
        $this->update([
            'status' => 'ready',
            'notified_at' => now(),
        ]);
    }

    /**
     * Mark reservation as completed (picked up).
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'picked_up_at' => now(),
        ]);
    }

    /**
     * Cancel the reservation.
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Mark reservation as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Get time remaining until expiration.
     */
    public function getTimeRemainingAttribute(): ?Carbon
    {
        if ($this->status !== 'pending' && $this->status !== 'ready') {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Scope to get only active reservations.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'ready']);
    }

    /**
     * Scope to get only pending reservations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get only ready reservations.
     */
    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    /**
     * Scope to get expired reservations.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<', now());
    }

    /**
     * Scope to get reservations for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get reservations for a specific book.
     */
    public function scopeForBook($query, int $bookId)
    {
        return $query->where('book_id', $bookId);
    }

    /**
     * Get hours remaining until expiration.
     */
    public function getHoursRemainingAttribute(): int
    {
        if ($this->status !== 'pending' && $this->status !== 'ready') {
            return 0;
        }

        return max(0, now()->diffInHours($this->expires_at, false));
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
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
        'book_copy_id',
        'reservation_id',
        'processed_by',
        'status',
        'loan_date',
        'due_date',
        'return_date',
        'returned_by',
        'days_overdue',
        'fine_amount',
        'fine_paid',
        'notes',
        'return_notes',
        'book_condition_at_loan',
        'book_condition_at_return',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'days_overdue' => 'integer',
        'fine_amount' => 'decimal:2',
        'fine_paid' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Loan $loan) {
            if (empty($loan->loan_date)) {
                $loan->loan_date = now()->toDateString();
            }
            
            if (empty($loan->due_date)) {
                // Default 14-day loan period
                $loan->due_date = now()->addDays(14)->toDateString();
            }
        });

        static::updating(function (Loan $loan) {
            // Auto-calculate overdue days and status
            if ($loan->status === 'active' && $loan->due_date < now()->toDateString()) {
                $loan->status = 'overdue';
                $loan->days_overdue = now()->diffInDays($loan->due_date);
            }
        });
    }

    /**
     * Get the user who borrowed the book.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the book that was borrowed.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the specific book copy that was borrowed.
     */
    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    /**
     * Get the reservation associated with this loan.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the admin who processed the loan.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the admin who processed the return.
     */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /**
     * Check if the loan is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'active' && $this->due_date < now()->toDateString();
    }

    /**
     * Check if the loan is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' || $this->status === 'overdue';
    }

    /**
     * Process the return of the book.
     */
    public function processReturn(User $admin, string $condition = 'good', string $notes = null): void
    {
        $this->update([
            'status' => 'returned',
            'return_date' => now()->toDateString(),
            'returned_by' => $admin->id,
            'book_condition_at_return' => $condition,
            'return_notes' => $notes,
            'days_overdue' => $this->calculateOverdueDays(),
            'fine_amount' => $this->calculateFine(),
        ]);

        // Update book copy status
        if ($this->bookCopy) {
            $this->bookCopy->update(['status' => 'available']);
        }

        // Update book availability
        $this->book->syncAvailableCopies();
    }

    /**
     * Mark the book as lost.
     */
    public function markAsLost(User $admin, string $notes = null): void
    {
        $this->update([
            'status' => 'lost',
            'return_notes' => $notes,
            'returned_by' => $admin->id,
        ]);

        // Update book copy status
        if ($this->bookCopy) {
            $this->bookCopy->update(['status' => 'lost']);
        }

        // Update book availability
        $this->book->syncAvailableCopies();
    }

    /**
     * Calculate overdue days.
     */
    public function calculateOverdueDays(): int
    {
        if ($this->return_date && $this->return_date > $this->due_date) {
            return Carbon::parse($this->due_date)->diffInDays(Carbon::parse($this->return_date));
        }

        if ($this->status === 'active' && now()->toDateString() > $this->due_date) {
            return Carbon::parse($this->due_date)->diffInDays(now());
        }

        return 0;
    }

    /**
     * Calculate fine amount (example: Rp 1,000 per day overdue).
     */
    public function calculateFine(): float
    {
        $overdueDays = $this->calculateOverdueDays();
        $finePerDay = 1000; // Rp 1,000 per day
        
        return $overdueDays * $finePerDay;
    }

    /**
     * Get days remaining until due date.
     */
    public function getDaysRemainingAttribute(): int
    {
        if ($this->status !== 'active') {
            return 0;
        }

        return max(0, now()->diffInDays($this->due_date, false));
    }

    /**
     * Get human-readable due date status.
     */
    public function getDueStatusAttribute(): string
    {
        if ($this->status === 'returned') {
            return 'Dikembalikan';
        }

        if ($this->status === 'lost') {
            return 'Hilang';
        }

        if ($this->isOverdue()) {
            return "Terlambat {$this->days_overdue} hari";
        }

        $daysRemaining = $this->days_remaining;
        
        if ($daysRemaining === 0) {
            return 'Jatuh tempo hari ini';
        }

        if ($daysRemaining === 1) {
            return 'Jatuh tempo besok';
        }

        return "Sisa {$daysRemaining} hari";
    }

    /**
     * Scope to get only active loans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only overdue loans.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'active')
                  ->where('due_date', '<', now()->toDateString());
            });
    }

    /**
     * Scope to get returned loans.
     */
    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    /**
     * Scope to get loans for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get loans for a specific book.
     */
    public function scopeForBook($query, int $bookId)
    {
        return $query->where('book_id', $bookId);
    }

    /**
     * Scope to get loans due within specified days.
     */
    public function scopeDueWithin($query, int $days)
    {
        return $query->where('status', 'active')
            ->whereBetween('due_date', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ]);
    }
}

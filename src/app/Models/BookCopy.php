<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BookCopy extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'book_id',
        'qr_code',
        'copy_number',
        'status',
        'location_code',
        'notes',
        'last_borrowed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_borrowed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($bookCopy) {
            if (empty($bookCopy->qr_code)) {
                $bookCopy->qr_code = self::generateUniqueQrCode();
            }
            
            // Auto-generate copy number if not provided
            if (empty($bookCopy->copy_number)) {
                $bookCopy->copy_number = self::generateCopyNumber($bookCopy->book_id);
            }
        });
    }

    /**
     * Generate a unique QR code for the book copy.
     */
    protected static function generateUniqueQrCode(): string
    {
        do {
            $qrCode = 'BC-' . strtoupper(Str::random(10));
        } while (self::where('qr_code', $qrCode)->exists());

        return $qrCode;
    }

    /**
     * Generate the next copy number for a book.
     */
    protected static function generateCopyNumber(int $bookId): string
    {
        $lastCopy = self::where('book_id', $bookId)
            ->orderBy('copy_number', 'desc')
            ->first();

        if (!$lastCopy) {
            return '001';
        }

        $nextNumber = (int) $lastCopy->copy_number + 1;
        return str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get the book that owns the copy.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Scope a query to only include available copies.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope a query to only include borrowed copies.
     */
    public function scopeBorrowed($query)
    {
        return $query->where('status', 'borrowed');
    }

    /**
     * Scope a query to only include damaged copies.
     */
    public function scopeDamaged($query)
    {
        return $query->where('status', 'damaged');
    }

    /**
     * Scope a query to only include lost copies.
     */
    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    /**
     * Check if the copy is available for borrowing.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Check if the copy is currently borrowed.
     */
    public function isBorrowed(): bool
    {
        return $this->status === 'borrowed';
    }

    /**
     * Mark the copy as borrowed.
     */
    public function markAsBorrowed(): void
    {
        $this->update([
            'status' => 'borrowed',
            'last_borrowed_at' => now(),
        ]);
    }

    /**
     * Mark the copy as returned (available).
     */
    public function markAsReturned(): void
    {
        $this->update([
            'status' => 'available',
        ]);
    }

    /**
     * Mark the copy as damaged.
     */
    public function markAsDamaged(string $notes = null): void
    {
        $this->update([
            'status' => 'damaged',
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Mark the copy as lost.
     */
    public function markAsLost(string $notes = null): void
    {
        $this->update([
            'status' => 'lost',
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Get the status label in Indonesian.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available' => 'Tersedia',
            'borrowed' => 'Dipinjam',
            'damaged' => 'Rusak',
            'lost' => 'Hilang',
            'maintenance' => 'Pemeliharaan',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Get the full copy identifier (e.g., "BK-ABC123 #001").
     */
    public function getFullIdentifierAttribute(): string
    {
        return "{$this->book->title} #{$this->copy_number}";
    }
}

// Made with Bob

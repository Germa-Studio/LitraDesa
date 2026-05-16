<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'author',
        'isbn',
        'book_category_id',
        'description',
        'publisher',
        'publication_year',
        'language',
        'total_copies',
        'available_copies',
        'location',
        'cover_image',
        'qr_code',
        'is_available',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'publication_year' => 'integer',
        'total_copies' => 'integer',
        'available_copies' => 'integer',
        'is_available' => 'boolean',
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

        static::creating(function ($book) {
            if (empty($book->qr_code)) {
                $book->qr_code = self::generateUniqueQrCode();
            }
            
            // Set available copies equal to total copies on creation
            if ($book->available_copies === null) {
                $book->available_copies = $book->total_copies;
            }
        });

        static::updating(function ($book) {
            // Update availability status based on available copies
            $book->is_available = $book->available_copies > 0;
        });
    }

    /**
     * Generate a unique QR code for the book.
     */
    protected static function generateUniqueQrCode(): string
    {
        do {
            $qrCode = 'BK-' . strtoupper(Str::random(10));
        } while (self::where('qr_code', $qrCode)->exists());

        return $qrCode;
    }

    /**
     * Get the category that owns the book.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'book_category_id');
    }

    /**
     * Get all copies of this book.
     */
    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    /**
     * Get available copies of this book.
     */
    public function availableCopies(): HasMany
    {
        return $this->hasMany(BookCopy::class)->where('status', 'available');
    }

    /**
     * Get borrowed copies of this book.
     */
    public function borrowedCopies(): HasMany
    {
        return $this->hasMany(BookCopy::class)->where('status', 'borrowed');
    }

    /**
     * Scope a query to only include available books.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('available_copies', '>', 0);
    }

    /**
     * Scope a query to search books by title, author, or ISBN.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'ILIKE', "%{$search}%")
              ->orWhere('author', 'ILIKE', "%{$search}%")
              ->orWhere('isbn', 'ILIKE', "%{$search}%")
              ->orWhere('description', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('book_category_id', $categoryId);
    }

    /**
     * Get the availability status text.
     */
    public function getAvailabilityStatusAttribute(): string
    {
        if ($this->available_copies === 0) {
            return 'Tidak Tersedia';
        }
        
        if ($this->available_copies === $this->total_copies) {
            return 'Tersedia';
        }
        
        return "Tersedia ({$this->available_copies}/{$this->total_copies})";
    }

    /**
     * Sync available_copies count with actual BookCopy records.
     */
    public function syncAvailableCopies(): void
    {
        $availableCount = $this->copies()->where('status', 'available')->count();
        $totalCount = $this->copies()->count();
        
        $this->update([
            'available_copies' => $availableCount,
            'total_copies' => $totalCount,
            'is_available' => $availableCount > 0,
        ]);
    }

    /**
     * Create multiple copies of this book.
     */
    public function createCopies(int $quantity, array $attributes = []): void
    {
        for ($i = 0; $i < $quantity; $i++) {
            $this->copies()->create($attributes);
        }
        
        $this->syncAvailableCopies();
    }

    /**
     * Get the count of copies by status.
     */
    public function getCopiesCountByStatus(string $status): int
    {
        return $this->copies()->where('status', $status)->count();
    }
}

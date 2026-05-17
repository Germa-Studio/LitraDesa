<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingProgress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reading_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'softbook_id',
        'current_page',
        'total_pages',
        'progress_percentage',
        'last_position',
        'last_read_at',
        'total_reading_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_page' => 'integer',
        'total_pages' => 'integer',
        'progress_percentage' => 'decimal:2',
        'last_read_at' => 'datetime',
        'total_reading_time' => 'integer',
    ];

    /**
     * Get the user that owns the reading progress.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the softbook being read.
     */
    public function softbook(): BelongsTo
    {
        return $this->belongsTo(Softbook::class);
    }

    /**
     * Update reading progress.
     */
    public function updateProgress(int $currentPage, ?int $totalPages = null, ?string $position = null): void
    {
        $data = [
            'current_page' => $currentPage,
            'last_read_at' => now(),
        ];

        if ($totalPages !== null) {
            $data['total_pages'] = $totalPages;
            $data['progress_percentage'] = $totalPages > 0 
                ? round(($currentPage / $totalPages) * 100, 2) 
                : 0;
        }

        if ($position !== null) {
            $data['last_position'] = $position;
        }

        $this->update($data);
    }

    /**
     * Add reading time.
     */
    public function addReadingTime(int $seconds): void
    {
        $this->increment('total_reading_time', $seconds);
        $this->update(['last_read_at' => now()]);
    }

    /**
     * Check if book is completed.
     */
    public function isCompleted(): bool
    {
        return $this->progress_percentage >= 100;
    }

    /**
     * Get formatted reading time.
     */
    public function getFormattedReadingTimeAttribute(): string
    {
        $seconds = $this->total_reading_time;
        
        if ($seconds < 60) {
            return $seconds . ' detik';
        }
        
        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            return $minutes . ' menit';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . ' jam ' . $remainingMinutes . ' menit';
    }

    /**
     * Scope to get progress for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get progress for a specific softbook.
     */
    public function scopeForSoftbook($query, int $softbookId)
    {
        return $query->where('softbook_id', $softbookId);
    }

    /**
     * Scope to get recently read books.
     */
    public function scopeRecentlyRead($query)
    {
        return $query->orderBy('last_read_at', 'desc');
    }

    /**
     * Scope to get completed books.
     */
    public function scopeCompleted($query)
    {
        return $query->where('progress_percentage', '>=', 100);
    }

    /**
     * Scope to get in-progress books.
     */
    public function scopeInProgress($query)
    {
        return $query->where('progress_percentage', '>', 0)
                     ->where('progress_percentage', '<', 100);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ReadingProgress $progress) {
            if (empty($progress->last_read_at)) {
                $progress->last_read_at = now();
            }
        });
    }
}

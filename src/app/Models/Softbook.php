<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Softbook extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'book_id',
        'file_path',
        'original_filename',
        'format',
        'file_size',
        'pages',
        'encryption_key',
        'is_encrypted',
        'is_active',
        'download_limit',
        'total_downloads',
        'description',
        'uploaded_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'pages' => 'integer',
        'is_encrypted' => 'boolean',
        'is_active' => 'boolean',
        'download_limit' => 'integer',
        'total_downloads' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'encryption_key',
        'file_path',
    ];

    /**
     * Get the book that owns the softbook.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the user who uploaded the softbook.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get all downloads for this softbook.
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(SoftbookDownload::class);
    }

    /**
     * Get completed downloads for this softbook.
     */
    public function completedDownloads(): HasMany
    {
        return $this->hasMany(SoftbookDownload::class)->where('is_completed', true);
    }

    /**
     * Check if user can download this softbook.
     */
    public function canBeDownloadedBy(User $user): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($user->status !== 'active') {
            return false;
        }

        // Check download limit
        $userDownloads = $this->downloads()
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->count();

        return $userDownloads < $this->download_limit;
    }

    /**
     * Get remaining downloads for a user.
     */
    public function getRemainingDownloadsFor(User $user): int
    {
        $userDownloads = $this->downloads()
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->count();

        return max(0, $this->download_limit - $userDownloads);
    }

    /**
     * Increment total downloads counter.
     */
    public function incrementDownloads(): void
    {
        $this->increment('total_downloads');
    }

    /**
     * Get file size in human-readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get format label.
     */
    public function getFormatLabelAttribute(): string
    {
        return strtoupper($this->format);
    }

    /**
     * Check if file exists in storage.
     */
    public function fileExists(): bool
    {
        return Storage::disk('private')->exists($this->file_path);
    }

    /**
     * Get full file path.
     */
    public function getFullPath(): string
    {
        return Storage::disk('private')->path($this->file_path);
    }

    /**
     * Delete file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::disk('private')->delete($this->file_path);
        }
        
        return true;
    }

    /**
     * Scope to get only active softbooks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get softbooks by format.
     */
    public function scopeByFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    /**
     * Scope to get softbooks for a specific book.
     */
    public function scopeForBook($query, int $bookId)
    {
        return $query->where('book_id', $bookId);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Softbook $softbook) {
            // Delete file when softbook is deleted
            if (!$softbook->isForceDeleting()) {
                // Soft delete - keep file
                return;
            }
            
            // Hard delete - remove file
            $softbook->deleteFile();
        });
    }
}

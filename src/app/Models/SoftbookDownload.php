<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SoftbookDownload extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'softbook_id',
        'user_id',
        'ip_address',
        'user_agent',
        'download_token',
        'token_expires_at',
        'downloaded_at',
        'is_completed',
        'file_size_downloaded',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token_expires_at' => 'datetime',
        'downloaded_at' => 'datetime',
        'is_completed' => 'boolean',
        'file_size_downloaded' => 'integer',
    ];

    /**
     * Get the softbook that was downloaded.
     */
    public function softbook(): BelongsTo
    {
        return $this->belongsTo(Softbook::class);
    }

    /**
     * Get the user who downloaded the softbook.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique download token.
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Check if token is valid (not expired).
     */
    public function isTokenValid(): bool
    {
        return $this->token_expires_at > now();
    }

    /**
     * Mark download as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'downloaded_at' => now(),
        ]);

        // Increment softbook total downloads
        $this->softbook->incrementDownloads();
    }

    /**
     * Check if download is expired.
     */
    public function isExpired(): bool
    {
        return $this->token_expires_at < now();
    }

    /**
     * Scope to get only completed downloads.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope to get only pending downloads.
     */
    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope to get valid (non-expired) downloads.
     */
    public function scopeValid($query)
    {
        return $query->where('token_expires_at', '>', now());
    }

    /**
     * Scope to get expired downloads.
     */
    public function scopeExpired($query)
    {
        return $query->where('token_expires_at', '<=', now());
    }

    /**
     * Scope to get downloads for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get downloads for a specific softbook.
     */
    public function scopeForSoftbook($query, int $softbookId)
    {
        return $query->where('softbook_id', $softbookId);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SoftbookDownload $download) {
            if (empty($download->download_token)) {
                $download->download_token = self::generateToken();
            }

            if (empty($download->token_expires_at)) {
                // Token expires in 1 hour
                $download->token_expires_at = now()->addHour();
            }
        });
    }
}

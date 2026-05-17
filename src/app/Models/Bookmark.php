<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'softbook_id',
        'page_number',
        'position',
        'title',
        'note',
        'highlighted_text',
        'color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'page_number' => 'integer',
    ];

    /**
     * Get the user that owns the bookmark.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the softbook that was bookmarked.
     */
    public function softbook(): BelongsTo
    {
        return $this->belongsTo(Softbook::class);
    }

    /**
     * Get bookmark display title.
     */
    public function getDisplayTitleAttribute(): string
    {
        if ($this->title) {
            return $this->title;
        }

        if ($this->page_number) {
            return 'Halaman ' . $this->page_number;
        }

        return 'Bookmark';
    }

    /**
     * Check if bookmark has a note.
     */
    public function hasNote(): bool
    {
        return !empty($this->note);
    }

    /**
     * Check if bookmark has highlighted text.
     */
    public function hasHighlight(): bool
    {
        return !empty($this->highlighted_text);
    }

    /**
     * Scope to get bookmarks for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get bookmarks for a specific softbook.
     */
    public function scopeForSoftbook($query, int $softbookId)
    {
        return $query->where('softbook_id', $softbookId);
    }

    /**
     * Scope to get bookmarks with notes.
     */
    public function scopeWithNotes($query)
    {
        return $query->whereNotNull('note')->where('note', '!=', '');
    }

    /**
     * Scope to get bookmarks with highlights.
     */
    public function scopeWithHighlights($query)
    {
        return $query->whereNotNull('highlighted_text')->where('highlighted_text', '!=', '');
    }

    /**
     * Scope to order by page number.
     */
    public function scopeOrderByPage($query)
    {
        return $query->orderBy('page_number');
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Bookmark $bookmark) {
            if (empty($bookmark->color)) {
                $bookmark->color = '#ffeb3b';
            }
        });
    }
}

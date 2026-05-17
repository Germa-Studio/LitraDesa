<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'phone',
        'address',
        'ktp_number',
        'ktp_photo_path',
        'qr_code',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the admin who approved this member.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get members approved by this admin.
     */
    public function approvedMembers(): HasMany
    {
        return $this->hasMany(User::class, 'approved_by');
    }

    /**
     * Get all loans for this user.
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get active loans for this user.
     */
    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class)->whereIn('status', ['active', 'overdue']);
    }

    /**
     * Get all reservations for this user.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get active reservations for this user.
     */
    public function activeReservations(): HasMany
    {
        return $this->hasMany(Reservation::class)->whereIn('status', ['pending', 'ready']);
    }

    /**
     * Get loans processed by this admin.
     */
    public function processedLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'processed_by');
    }

    /**
     * Get returns processed by this admin.
     */
    public function processedReturns(): HasMany
    {
        return $this->hasMany(Loan::class, 'returned_by');
    }

    /**
     * Check if user can borrow more books (max 3 active loans).
     */
    public function canBorrowMoreBooks(): bool
    {
        return $this->activeLoans()->count() < 3;
    }

    /**
     * Get count of active loans.
     */
    public function getActiveLoansCountAttribute(): int
    {
        return $this->activeLoans()->count();
    }

    /**
     * Check if user has overdue loans.
     */
    public function hasOverdueLoans(): bool
    {
        return $this->loans()->where('status', 'overdue')->exists();
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a member.
     */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * Check if member is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if member is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if member is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if member is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Scope query to only include admins.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope query to only include members.
     */
    public function scopeMembers($query)
    {
        return $query->where('role', 'member');
    }

    /**
     * Scope query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope query to only include pending users.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

<?php

namespace App\Models;

use App\Services\PhoneFormatter;
use App\Services\WhatsAppLinkBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'batch_id',
        'name',
        'photo',
        'phone',
        'alt_phone',
        'email',
        'fee_type',
        'total_fee',
        'discount',
        'net_fee',
        'join_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_fee' => 'decimal:2',
            'discount' => 'decimal:2',
            'net_fee' => 'decimal:2',
            'join_date' => 'date',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_on');
    }

    public function notesList(): HasMany
    {
        return $this->hasMany(StudentNote::class)->latest();
    }

    // Phone normalizer on set
    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = PhoneFormatter::normalize($value);
    }

    public function setAltPhoneAttribute($value): void
    {
        $this->attributes['alt_phone'] = $value ? PhoneFormatter::normalize($value) : null;
    }

    // Accessors
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        return Storage::disk('public')->url($this->photo);
    }

    public function getTotalPaidAttribute(): float
    {
        // Use eager-loaded relation when available to avoid N+1
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }

        return (float) $this->payments()->sum('amount');
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0, (float) $this->net_fee - $this->total_paid);
    }

    public function getIsClearedAttribute(): bool
    {
        return $this->remaining_balance <= 0 && $this->total_paid > 0;
    }

    public function getFeeStatusAttribute(): string
    {
        if ($this->is_cleared) {
            return 'cleared';
        }

        // Check if has any overdue installment
        $hasOverdue = $this->installments->where('status', 'overdue')->count() > 0;
        if ($hasOverdue) {
            return 'overdue';
        }

        if ($this->total_paid > 0) {
            return 'partial';
        }

        return 'pending';
    }

    public function getOverdueAmountAttribute(): float
    {
        return (float) $this->installments
            ->where('status', 'overdue')
            ->sum(function ($inst) {
                return (float) $inst->amount - (float) $inst->paid_amount;
            });
    }

    public function getDaysOverdueAttribute(): int
    {
        $oldestOverdue = $this->installments
            ->where('status', 'overdue')
            ->sortBy('due_date')
            ->first();

        if (! $oldestOverdue || ! $oldestOverdue->due_date) {
            return 0;
        }

        $dueDay = $oldestOverdue->due_date->copy()->startOfDay();
        $today = now()->startOfDay();

        // Only return positive days when overdue (due_date is in the past)
        return $dueDay->lt($today) ? (int) $dueDay->diffInDays($today) : 0;
    }

    public function getNextDueDateAttribute(): ?string
    {
        $next = $this->installments
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sortBy('due_date')
            ->first();

        return $next?->due_date?->format('Y-m-d');
    }

    public function getWhatsAppUrl(?string $template = null): string
    {
        return WhatsAppLinkBuilder::build($this, $template);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDefaulters(Builder $query): Builder
    {
        return $query->whereHas('installments', function ($q) {
            $q->where('status', 'overdue');
        });
    }
}

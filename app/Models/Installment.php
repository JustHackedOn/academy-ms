<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'number',
        'due_date',
        'amount',
        'paid_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function getRemainingAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }

    /**
     * Compute and refresh installment status.
     * Rule: paid = fully covered; partial = some paid; overdue = due_date < today and not fully paid; pending = otherwise.
     */
    public function refreshStatus(bool $save = true): string
    {
        $paid = (float) $this->paid_amount;
        $amount = (float) $this->amount;
        $isPast = $this->due_date && $this->due_date->copy()->startOfDay()->lt(now()->startOfDay());

        if ($paid >= $amount && $amount > 0) {
            $status = 'paid';
        } elseif ($paid > 0 && $paid < $amount) {
            $status = $isPast ? 'overdue' : 'partial';
        } elseif ($isPast) {
            $status = 'overdue';
        } else {
            $status = 'pending';
        }

        $this->status = $status;
        if ($save) {
            $this->saveQuietly();
        }

        return $status;
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 'overdue')
              ->orWhere(function ($sub) {
                  $sub->whereColumn('paid_amount', '<', 'amount')
                      ->where('due_date', '<', now()->toDateString());
              });
        });
    }

    public function scopeDueInMonth(Builder $query, string $yearMonth): Builder
    {
        $start = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $yearMonth)->endOfMonth();

        return $query->whereBetween('due_date', [$start, $end]);
    }
}

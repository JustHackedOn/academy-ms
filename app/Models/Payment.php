<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'installment_id',
        'amount',
        'paid_on',
        'for_month',
        'method',
        'reference',
        'note',
        'received_by',
        'receipt_no',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public static function generateReceiptNo(): string
    {
        $year = date('Y');
        $prefix = "RCPT-{$year}-";
        $last = self::withTrashed()
            ->where('receipt_no', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($last && preg_match('/RCPT-\d{4}-(\d+)/', $last->receipt_no, $matches)) {
            $next = intval($matches[1]) + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }
}

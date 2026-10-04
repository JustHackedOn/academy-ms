<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'default_fee',
        'duration_months',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'default_fee' => 'decimal:2',
            'duration_months' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getTotalExpectedAttribute(): float
    {
        if (array_key_exists('total_expected_fee', $this->attributes)) {
            return (float) $this->attributes['total_expected_fee'];
        }

        if ($this->relationLoaded('students')) {
            return (float) $this->students->sum('net_fee');
        }

        return (float) $this->students()->sum('net_fee');
    }

    public function getTotalCollectedAttribute(): float
    {
        if (array_key_exists('total_collected_amount', $this->attributes)) {
            return (float) $this->attributes['total_collected_amount'];
        }

        if ($this->relationLoaded('students')) {
            return (float) $this->students->sum(function ($s) {
                return $s->relationLoaded('payments') ? (float) $s->payments->sum('amount') : (float) $s->total_paid;
            });
        }

        return (float) Payment::whereIn('student_id', $this->students()->pluck('id'))->sum('amount');
    }

    public function getCollectionRateAttribute(): float
    {
        $expected = $this->total_expected;
        if ($expected <= 0) return 0;
        return round(($this->total_collected / $expected) * 100, 1);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'user_id',
        'body',
        'promise_date',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'promise_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

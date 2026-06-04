<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    // id is a UUID matching auth.users.id — Supabase 1-to-1 pattern.
    protected $table = 'students';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // students table has no created_at / updated_at

    protected $fillable = [
        'id',
        'student_number',
        'last_name',
        'first_name',
        'anonymous_name',
        'status',
        'year_level',
        'section',
        'daily_result',
    ];

    protected function casts(): array
    {
        return [
            'daily_result' => 'array',
            'year_level' => 'integer',
        ];
    }

    // students.id → auth.users.id
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }
}

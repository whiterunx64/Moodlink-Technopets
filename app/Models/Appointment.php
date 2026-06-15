<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
  protected $table = 'appointments';

  public $timestamps = false;

  protected $fillable = [
    'student_id',
    'context',
    'note',
    'status',
    'datetime',
  ];

  protected $casts = [
    'datetime' => 'datetime',
    'status' => AppointmentStatus::class,
  ];

  public function student(): BelongsTo
  {
    return $this->belongsTo(Student::class, 'student_id');
  }
}
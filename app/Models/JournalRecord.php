<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalRecord extends Model
{
    protected $fillable = ['lesson_id', 'student_id', 'attendance', 'grade', 'comment'];
    protected $casts = ['grade' => 'integer'];

    public function lesson(): BelongsTo { return $this->belongsTo(Lesson::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}

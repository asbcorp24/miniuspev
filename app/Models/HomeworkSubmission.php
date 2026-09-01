<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeworkSubmission extends Model
{
    protected $fillable = ['homework_id','student_id','student_comment','grade','teacher_comment','submitted_at','graded_at','status'];
    protected $casts = ['submitted_at' => 'datetime', 'graded_at' => 'datetime'];

    public function homework(): BelongsTo { return $this->belongsTo(Homework::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function files(): HasMany { return $this->hasMany(HomeworkFile::class, 'submission_id'); }
}

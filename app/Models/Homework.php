<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homework extends Model
{
    protected $fillable = ['group_id','subject_id','academic_period_id','work_type_id','grade_weight','teacher_id','title','description','due_at','max_grade'];
    protected $casts = ['due_at' => 'datetime', 'grade_weight' => 'float'];

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function academicPeriod(): BelongsTo { return $this->belongsTo(AcademicPeriod::class); }
    public function workType(): BelongsTo { return $this->belongsTo(WorkType::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
    public function submissions(): HasMany { return $this->hasMany(HomeworkSubmission::class); }
}

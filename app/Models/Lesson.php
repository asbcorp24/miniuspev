<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = ['group_id','subject_id','academic_period_id','work_type_id','grade_weight','lesson_date','topic'];
    protected $casts = ['lesson_date' => 'date', 'grade_weight' => 'float'];

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function academicPeriod(): BelongsTo { return $this->belongsTo(AcademicPeriod::class); }
    public function workType(): BelongsTo { return $this->belongsTo(WorkType::class); }
    public function records(): HasMany { return $this->hasMany(JournalRecord::class); }
}

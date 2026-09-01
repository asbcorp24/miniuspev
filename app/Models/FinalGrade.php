<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalGrade extends Model
{
    protected $fillable = ['student_id','subject_id','academic_period_id','calculated_grade','final_grade','comment','set_by'];
    protected $casts = ['calculated_grade'=>'float','final_grade'=>'integer'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function academicPeriod(): BelongsTo { return $this->belongsTo(AcademicPeriod::class); }
    public function setter(): BelongsTo { return $this->belongsTo(User::class, 'set_by'); }
}

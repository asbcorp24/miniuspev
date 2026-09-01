<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleEntry extends Model
{
    protected $fillable = ['group_id','subject_id','teacher_id','academic_period_id','weekday','starts_at','ends_at','room','lesson_type','note','active'];
    protected $casts = ['active'=>'boolean'];

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
    public function academicPeriod(): BelongsTo { return $this->belongsTo(AcademicPeriod::class); }

    public function getWeekdayNameAttribute(): string
    {
        return [1=>'Понедельник',2=>'Вторник',3=>'Среда',4=>'Четверг',5=>'Пятница',6=>'Суббота',7=>'Воскресенье'][$this->weekday] ?? '—';
    }
}

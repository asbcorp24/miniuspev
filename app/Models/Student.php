<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = ['group_id', 'last_name', 'first_name', 'middle_name', 'student_number', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function records(): HasMany { return $this->hasMany(JournalRecord::class); }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
    }

    public function averageGrade(?int $subjectId = null): ?float
    {
        $query = $this->records()->whereNotNull('grade');
        if ($subjectId) {
            $query->whereHas('lesson', fn ($q) => $q->where('subject_id', $subjectId));
        }
        $avg = $query->avg('grade');
        return $avg === null ? null : round((float) $avg, 2);
    }

    public function attendancePercent(?int $subjectId = null): ?float
    {
        $query = $this->records()->where('attendance', '!=', 'unmarked');
        if ($subjectId) {
            $query->whereHas('lesson', fn ($q) => $q->where('subject_id', $subjectId));
        }
        $total = (clone $query)->count();
        if (!$total) return null;
        $present = (clone $query)->whereIn('attendance', ['present', 'late'])->count();
        return round($present * 100 / $total, 1);
    }
}

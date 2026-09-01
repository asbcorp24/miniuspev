<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicPeriod extends Model
{
    protected $fillable = ['academic_year','semester','starts_at','ends_at','active'];
    protected $casts = ['starts_at'=>'date','ends_at'=>'date','active'=>'boolean'];

    public function lessons(): HasMany { return $this->hasMany(Lesson::class); }
    public function homeworks(): HasMany { return $this->hasMany(Homework::class); }
    public function finalGrades(): HasMany { return $this->hasMany(FinalGrade::class); }

    public function getLabelAttribute(): string
    {
        return $this->academic_year.' · '.$this->semester.' семестр';
    }
}

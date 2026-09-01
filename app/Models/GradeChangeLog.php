<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeChangeLog extends Model
{
    protected $fillable = [
        'student_id','changed_by','source_type','source_id','old_grade','new_grade','reason','comment'
    ];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}

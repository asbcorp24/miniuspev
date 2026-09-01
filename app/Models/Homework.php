<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homework extends Model
{
    protected $fillable = ['group_id','subject_id','teacher_id','title','description','due_at','max_grade'];
    protected $casts = ['due_at' => 'datetime'];

    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
    public function submissions(): HasMany { return $this->hasMany(HomeworkSubmission::class); }
}

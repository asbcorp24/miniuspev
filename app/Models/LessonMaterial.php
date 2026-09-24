<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonMaterial extends Model
{
    protected $fillable = ['lesson_id','type','title','url','path','original_name','mime_type','size'];

    public function lesson(): BelongsTo { return $this->belongsTo(Lesson::class); }

    public function isPreviewable(): bool
    {
        return str_starts_with((string)$this->mime_type, 'image/') || $this->mime_type === 'application/pdf';
    }
}

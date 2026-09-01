<?php

namespace App\Services;

use App\Models\GradeChangeLog;
use Illuminate\Contracts\Auth\Authenticatable;

class GradeAuditService
{
    public static function log(
        int $studentId,
        string $sourceType,
        int $sourceId,
        ?int $oldGrade,
        ?int $newGrade,
        ?Authenticatable $user = null,
        ?string $reason = null,
        ?string $comment = null
    ): void {
        if ($oldGrade === $newGrade) return;

        GradeChangeLog::create([
            'student_id' => $studentId,
            'changed_by' => $user?->getAuthIdentifier(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'old_grade' => $oldGrade,
            'new_grade' => $newGrade,
            'reason' => $reason,
            'comment' => $comment,
        ]);
    }
}

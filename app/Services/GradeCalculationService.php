<?php

namespace App\Services;

use App\Models\HomeworkSubmission;
use App\Models\JournalRecord;

class GradeCalculationService
{
    public static function weightedAverage(int $studentId, int $subjectId, int $periodId): ?float
    {
        $points = 0.0;
        $weights = 0.0;

        $records = JournalRecord::with('lesson')
            ->where('student_id', $studentId)
            ->whereNotNull('grade')
            ->whereHas('lesson', fn($q) => $q->where('subject_id', $subjectId)->where('academic_period_id', $periodId))
            ->get();

        foreach ($records as $record) {
            $weight = max(0.01, (float)($record->lesson->grade_weight ?? 1));
            $points += ((float)$record->grade) * $weight;
            $weights += $weight;
        }

        $submissions = HomeworkSubmission::with('homework')
            ->where('student_id', $studentId)
            ->whereNotNull('grade')
            ->whereHas('homework', fn($q) => $q->where('subject_id', $subjectId)->where('academic_period_id', $periodId))
            ->get();

        foreach ($submissions as $submission) {
            $weight = max(0.01, (float)($submission->homework->grade_weight ?? 1));
            $points += ((float)$submission->grade) * $weight;
            $weights += $weight;
        }

        return $weights > 0 ? round($points / $weights, 2) : null;
    }
}

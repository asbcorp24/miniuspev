<?php

namespace App\Observers;

use App\Models\JournalRecord;
use App\Models\User;
use App\Services\StudentNotificationService;

class JournalRecordObserver
{
    public function updated(JournalRecord $record): void
    {
        $changes = $record->getChanges();
        if (!array_key_exists('grade', $changes) && !array_key_exists('attendance', $changes)) return;

        $record->loadMissing('lesson.subject');
        $user = User::where('role', 'student')->where('student_id', $record->student_id)->first();
        if (!$user) return;

        if (array_key_exists('grade', $changes) && $record->grade !== null) {
            StudentNotificationService::createForUser(
                $user,
                'grade',
                'Новая оценка',
                $record->lesson->subject->name.' — оценка '.$record->grade.($record->lesson->topic ? ' за «'.$record->lesson->topic.'»' : ''),
                route('student.dashboard'),
                'journal-grade:'.$record->id.':'.($record->updated_at?->timestamp ?? time()),
                ['record_id' => $record->id, 'grade' => $record->grade]
            );
        }

        if (array_key_exists('attendance', $changes) && in_array($record->attendance, ['absent', 'late', 'excused'], true)) {
            $labels = ['absent' => 'Прогул', 'late' => 'Опоздание', 'excused' => 'Уважительная причина'];
            StudentNotificationService::createForUser(
                $user,
                'attendance',
                $labels[$record->attendance],
                $record->lesson->subject->name.' · '.$record->lesson->lesson_date->format('d.m.Y').($record->lesson->topic ? ' · '.$record->lesson->topic : ''),
                route('student.dashboard'),
                'journal-attendance:'.$record->id.':'.($record->updated_at?->timestamp ?? time()),
                ['record_id' => $record->id, 'attendance' => $record->attendance]
            );
        }
    }
}

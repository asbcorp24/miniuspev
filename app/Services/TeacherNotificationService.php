<?php

namespace App\Services;

use App\Models\Homework;
use App\Models\JournalRecord;
use App\Models\Student;
use App\Models\StudentNotification;
use App\Models\User;

class TeacherNotificationService
{
    public static function createForTeacher(User $teacher, string $type, string $title, ?string $message = null, ?string $url = null, ?string $uniqueKey = null, array $data = []): void
    {
        if (!$teacher->isTeacher()) return;

        $payload = [
            'user_id' => $teacher->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'unique_key' => $uniqueKey,
            'data' => $data ?: null,
        ];

        if ($uniqueKey) {
            StudentNotification::firstOrCreate(['unique_key' => $uniqueKey], $payload);
        } else {
            StudentNotification::create($payload);
        }
    }

    public static function syncRiskAlerts(User $teacher): void
    {
        if (!$teacher->isTeacher()) return;

        $homeworks = Homework::with(['group.students' => fn($q) => $q->where('active', true), 'subject', 'submissions'])
            ->where('teacher_id', $teacher->id)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get();

        foreach ($homeworks as $homework) {
            $submittedIds = $homework->submissions->pluck('student_id')->all();
            foreach ($homework->group->students as $student) {
                if (in_array($student->id, $submittedIds, true)) continue;

                self::createForTeacher(
                    $teacher,
                    'homework_overdue',
                    'Домашнее задание просрочено',
                    $student->full_name.' не сдал «'.$homework->title.'» по дисциплине '.$homework->subject->name.'.',
                    route('homeworks.show', $homework),
                    'teacher-overdue:'.$homework->id.':'.$student->id,
                    ['homework_id' => $homework->id, 'student_id' => $student->id]
                );
            }
        }

        $assignments = $teacher->groups()->get();
        foreach ($assignments as $group) {
            $subjectId = (int) $group->pivot->subject_id;
            $students = Student::where('group_id', $group->id)->where('active', true)->get();

            foreach ($students as $student) {
                $records = JournalRecord::where('student_id', $student->id)
                    ->whereHas('lesson', fn($q) => $q->where('group_id', $group->id)->where('subject_id', $subjectId));

                $absenceCount = (clone $records)->where('attendance', 'absent')->count();
                if ($absenceCount >= 3) {
                    self::createForTeacher(
                        $teacher,
                        'attendance_risk',
                        'Много прогулов',
                        $student->full_name.' накопил прогулов: '.$absenceCount.'.',
                        route('students.show', $student),
                        'teacher-absence-risk:'.$teacher->id.':'.$student->id.':'.$subjectId.':'.$absenceCount,
                        ['student_id' => $student->id, 'subject_id' => $subjectId, 'count' => $absenceCount]
                    );
                }

                $debtCount = (clone $records)->where('grade', 2)->count();
                if ($debtCount >= 3) {
                    self::createForTeacher(
                        $teacher,
                        'grade_risk',
                        'Накопились задолженности',
                        $student->full_name.' имеет оценок «2»: '.$debtCount.'.',
                        route('students.show', $student),
                        'teacher-grade-risk:'.$teacher->id.':'.$student->id.':'.$subjectId.':'.$debtCount,
                        ['student_id' => $student->id, 'subject_id' => $subjectId, 'count' => $debtCount]
                    );
                }
            }
        }
    }
}

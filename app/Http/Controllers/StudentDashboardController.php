<?php

namespace App\Http\Controllers;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\JournalRecord;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isStudent() && $user->student_id, 403);

        $student = Student::with('group')->findOrFail($user->student_id);

        $records = JournalRecord::with('lesson.subject')
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->get();

        $submissions = HomeworkSubmission::with(['homework.subject','files'])
            ->where('student_id', $student->id)
            ->orderByDesc('submitted_at')
            ->get();

        $journalGrades = $records->whereNotNull('grade')->pluck('grade');
        $homeworkGrades = $submissions->whereNotNull('grade')->pluck('grade');
        $allGrades = $journalGrades->concat($homeworkGrades);

        $totalAttendance = $records->count();
        $presentAttendance = $records->whereIn('attendance', ['present','late'])->count();

        $upcomingHomeworks = Homework::with('subject')
            ->where('group_id', $student->group_id)
            ->where(function ($q) {
                $q->whereNull('due_at')->orWhere('due_at', '>=', now());
            })
            ->orderByRaw('due_at IS NULL, due_at ASC')
            ->limit(8)
            ->get()
            ->map(function ($homework) use ($submissions) {
                $homework->my_submission = $submissions->firstWhere('homework_id', $homework->id);
                return $homework;
            });

        $subjects = $records->pluck('lesson.subject')->filter()->merge(
            $submissions->pluck('homework.subject')->filter()
        )->unique('id')->sortBy('name')->values();

        $subjectStats = $subjects->map(function ($subject) use ($records, $submissions) {
            $subjectRecords = $records->filter(fn ($record) => optional($record->lesson)->subject_id === $subject->id);
            $subjectSubmissions = $submissions->filter(fn ($submission) => optional($submission->homework)->subject_id === $subject->id);

            $journalGrades = $subjectRecords->whereNotNull('grade')->pluck('grade');
            $homeworkGrades = $subjectSubmissions->whereNotNull('grade')->pluck('grade');
            $grades = $journalGrades->concat($homeworkGrades);

            $total = $subjectRecords->count();
            $present = $subjectRecords->whereIn('attendance', ['present','late'])->count();

            return [
                'subject' => $subject,
                'average' => $grades->count() ? round($grades->avg(), 2) : null,
                'journal_average' => $journalGrades->count() ? round($journalGrades->avg(), 2) : null,
                'homework_average' => $homeworkGrades->count() ? round($homeworkGrades->avg(), 2) : null,
                'attendance' => $total ? round($present * 100 / $total, 1) : null,
                'absences' => $subjectRecords->where('attendance', 'absent')->count(),
                'debts' => $grades->filter(fn ($grade) => (int) $grade === 2)->count(),
            ];
        });

        $debts = collect();
        foreach ($records->where('grade', 2) as $record) {
            $debts->push([
                'type' => 'Оценка',
                'title' => optional($record->lesson)->topic ?: 'Занятие',
                'subject' => optional(optional($record->lesson)->subject)->name ?: '—',
                'date' => optional(optional($record->lesson)->lesson_date)?->format('d.m.Y'),
            ]);
        }
        foreach ($submissions->where('grade', 2) as $submission) {
            $debts->push([
                'type' => 'Домашнее задание',
                'title' => optional($submission->homework)->title ?: 'ДЗ',
                'subject' => optional(optional($submission->homework)->subject)->name ?: '—',
                'date' => optional($submission->graded_at)?->format('d.m.Y'),
            ]);
        }

        return view('student.dashboard', [
            'student' => $student,
            'averageGrade' => $allGrades->count() ? round($allGrades->avg(), 2) : null,
            'attendancePercent' => $totalAttendance ? round($presentAttendance * 100 / $totalAttendance, 1) : null,
            'absenceCount' => $records->where('attendance', 'absent')->count(),
            'debtCount' => $debts->count(),
            'subjectStats' => $subjectStats,
            'debts' => $debts,
            'upcomingHomeworks' => $upcomingHomeworks,
            'submissions' => $submissions->take(10),
            'recentRecords' => $records->take(12),
        ]);
    }
}

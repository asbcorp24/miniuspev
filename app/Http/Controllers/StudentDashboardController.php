<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\FinalGrade;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\JournalRecord;
use App\Models\ScheduleEntry;
use App\Models\Student;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isStudent() && $user->student_id, 403);

        $student = Student::with('group')->findOrFail($user->student_id);
        $periods = AcademicPeriod::orderByDesc('academic_year')->orderBy('semester')->get();
        $period = $request->integer('period_id')
            ? $periods->firstWhere('id', $request->integer('period_id'))
            : ($periods->firstWhere('active', true) ?: $periods->first());

        $recordsQuery = JournalRecord::with(['lesson.subject','lesson.workType'])->where('student_id', $student->id);
        if ($period) $recordsQuery->whereHas('lesson', fn($q) => $q->where('academic_period_id', $period->id));
        $records = $recordsQuery->orderByDesc('id')->get();

        $submissionsQuery = HomeworkSubmission::with(['homework.subject','homework.workType','files'])->where('student_id', $student->id);
        if ($period) $submissionsQuery->whereHas('homework', fn($q) => $q->where('academic_period_id', $period->id));
        $submissions = $submissionsQuery->orderByDesc('submitted_at')->get();

        $markedRecords = $records->where('attendance', '!=', 'unmarked');
        $totalAttendance = $markedRecords->count();
        $presentAttendance = $markedRecords->whereIn('attendance', ['present','late'])->count();

        $todaySchedule = ScheduleEntry::with(['subject','teacher'])
            ->where('active',true)
            ->where('group_id',$student->group_id)
            ->where('weekday',now()->dayOfWeekIso)
            ->when($period,fn($q)=>$q->where('academic_period_id',$period->id))
            ->orderBy('starts_at')->get();

        $upcomingHomeworksQuery = Homework::with(['subject','workType'])
            ->where('group_id', $student->group_id)
            ->where(function ($q) { $q->whereNull('due_at')->orWhere('due_at', '>=', now()); });
        if ($period) $upcomingHomeworksQuery->where('academic_period_id', $period->id);
        $upcomingHomeworks = $upcomingHomeworksQuery->orderByRaw('due_at IS NULL, due_at ASC')->limit(8)->get()->map(function ($homework) use ($submissions) {
            $homework->my_submission = $submissions->firstWhere('homework_id', $homework->id);
            return $homework;
        });

        $subjects = $records->pluck('lesson.subject')->filter()->merge($submissions->pluck('homework.subject')->filter())->unique('id')->sortBy('name')->values();
        $finals = $period ? FinalGrade::where('student_id', $student->id)->where('academic_period_id', $period->id)->get()->keyBy('subject_id') : collect();

        $subjectStats = $subjects->map(function ($subject) use ($records, $submissions, $period, $student, $finals) {
            $subjectRecords = $records->filter(fn ($record) => optional($record->lesson)->subject_id === $subject->id);
            $subjectSubmissions = $submissions->filter(fn ($submission) => optional($submission->homework)->subject_id === $subject->id);
            $journalGrades = $subjectRecords->whereNotNull('grade')->pluck('grade');
            $homeworkGrades = $subjectSubmissions->whereNotNull('grade')->pluck('grade');
            $marked = $subjectRecords->where('attendance', '!=', 'unmarked');
            $total = $marked->count();
            $present = $marked->whereIn('attendance', ['present','late'])->count();
            $weighted = $period ? GradeCalculationService::weightedAverage($student->id, $subject->id, $period->id) : null;
            $final = $finals->get($subject->id);

            return [
                'subject' => $subject,
                'average' => $weighted ?? ($journalGrades->concat($homeworkGrades)->count() ? round($journalGrades->concat($homeworkGrades)->avg(), 2) : null),
                'journal_average' => $journalGrades->count() ? round($journalGrades->avg(), 2) : null,
                'homework_average' => $homeworkGrades->count() ? round($homeworkGrades->avg(), 2) : null,
                'attendance' => $total ? round($present * 100 / $total, 1) : null,
                'absences' => $subjectRecords->where('attendance', 'absent')->count(),
                'debts' => $journalGrades->concat($homeworkGrades)->filter(fn ($grade) => (int)$grade === 2)->count(),
                'final_grade' => $final?->final_grade,
                'final_comment' => $final?->comment,
            ];
        });

        $weightedValues = $subjectStats->pluck('average')->filter(fn($v) => $v !== null);
        $debts = collect();
        foreach ($records->where('grade', 2) as $record) $debts->push(['type'=>'Оценка','title'=>$record->lesson?->topic ?: 'Занятие','subject'=>$record->lesson?->subject?->name ?: '—','date'=>$record->lesson?->lesson_date?->format('d.m.Y')]);
        foreach ($submissions->where('grade', 2) as $submission) $debts->push(['type'=>'Домашнее задание','title'=>$submission->homework?->title ?: 'ДЗ','subject'=>$submission->homework?->subject?->name ?: '—','date'=>$submission->graded_at?->format('d.m.Y')]);

        return view('student.dashboard', [
            'student' => $student,
            'periods' => $periods,
            'period' => $period,
            'todaySchedule' => $todaySchedule,
            'averageGrade' => $weightedValues->count() ? round($weightedValues->avg(), 2) : null,
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

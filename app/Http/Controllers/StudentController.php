<?php

namespace App\Http\Controllers;

use App\Models\GradeChangeLog;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function show(Student $student): View
    {
        $user = auth()->user();
        if ($user->isStudent()) abort_unless($user->student_id === $student->id, 403);
        if ($user->isTeacher()) abort_unless($user->groups()->where('groups.id',$student->group_id)->exists(), 403);

        $student->load(['group','records.lesson.subject']);
        $subjects = Subject::whereHas('lessons', fn($q) => $q->where('group_id', $student->group_id))->orderBy('name')->get();
        $stats = $subjects->map(function ($subject) use ($student) {
            $records = $student->records->filter(fn($r) => optional($r->lesson)->subject_id === $subject->id);
            $grades = $records->whereNotNull('grade');
            return [
                'subject' => $subject,
                'average' => $grades->count() ? round($grades->avg('grade'), 2) : null,
                'absences' => $records->where('attendance', 'absent')->count(),
                'attendance' => $student->attendancePercent($subject->id),
                'debts' => $grades->where('grade', 2)->count(),
            ];
        });

        $gradeHistory = GradeChangeLog::with('changedBy')
            ->where('student_id',$student->id)
            ->latest()
            ->limit(100)
            ->get();

        return view('students.show', compact('student','stats','gradeHistory'));
    }
}

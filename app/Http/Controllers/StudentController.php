<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function show(Student $student): View
    {
        $student->load(['group','records.lesson.subject']);

        $subjects = Subject::whereHas('lessons', function ($q) use ($student) {
            $q->where('group_id', $student->group_id);
        })->orderBy('name')->get();

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

        return view('students.show', compact('student','stats'));
    }
}

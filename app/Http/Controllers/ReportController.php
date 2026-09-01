<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\JournalRecord;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    private function allowedGroups(): array
    {
        $user = auth()->user();
        return $user->isAdmin() ? Group::pluck('id')->all() : $user->groups()->pluck('groups.id')->unique()->all();
    }

    public function index(Request $request): View
    {
        $groups = Group::whereIn('id', $this->allowedGroups())->orderBy('name')->get();
        $groupId = (int)($request->integer('group_id') ?: optional($groups->first())->id);
        abort_if($groupId && !in_array($groupId, $this->allowedGroups()), 403);

        $group = $groupId ? Group::with('students')->find($groupId) : null;
        $rows = collect();
        if ($group) {
            $rows = $group->students->map(function(Student $student) {
                return [
                    'student' => $student,
                    'average' => $student->averageGrade(),
                    'attendance' => $student->attendancePercent(),
                    'absences' => $student->records()->where('attendance','absent')->count(),
                    'debts' => $student->records()->where('grade',2)->count(),
                ];
            });
        }

        return view('reports.index', compact('groups','group','rows'));
    }

    public function csv(Request $request): Response
    {
        $groupId = (int)$request->integer('group_id');
        abort_unless(in_array($groupId, $this->allowedGroups()), 403);
        $group = Group::with('students')->findOrFail($groupId);

        $lines = ["ФИО;Группа;Средний балл;Посещаемость %;Прогулы;Двойки"];
        foreach ($group->students as $student) {
            $lines[] = implode(';', [
                $student->full_name,
                $group->name,
                $student->averageGrade() ?? '',
                $student->attendancePercent() ?? '',
                $student->records()->where('attendance','absent')->count(),
                $student->records()->where('grade',2)->count(),
            ]);
        }

        $content = "\xEF\xBB\xBF".implode("\r\n", $lines);
        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="report-'.$group->name.'.csv"',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Group;
use App\Models\Homework;
use App\Models\Lesson;
use App\Models\ScheduleEntry;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $periods = AcademicPeriod::orderByDesc('active')->orderByDesc('academic_year')->orderBy('semester')->get();
        $period = $request->integer('period_id')
            ? $periods->firstWhere('id', (int) $request->integer('period_id'))
            : ($periods->firstWhere('active', true) ?: $periods->first());

        $query = ScheduleEntry::with(['group','subject','teacher','academicPeriod'])->where('active', true);
        if ($period) $query->where('academic_period_id', (int) $period->id);

        if ($user->isStudent()) {
            abort_unless($user->student_id && $user->student, 403);
            $query->where('group_id', (int) $user->student->group_id);
        } elseif ($user->isTeacher()) {
            $query->where('teacher_id', (int) $user->id);
        }

        $entries = $query
            ->orderBy('weekday')
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (ScheduleEntry $entry) => (int) $entry->weekday);

        $groups = $user->isAdmin() ? Group::orderBy('name')->get() : collect();
        $subjects = $user->isAdmin() ? Subject::orderBy('name')->get() : collect();
        $teachers = $user->isAdmin() ? User::where('role', 'teacher')->orderBy('name')->get() : collect();

        $weekday = (int) now()->dayOfWeekIso;
        $todayQuery = ScheduleEntry::with(['group','subject','teacher'])
            ->where('active', true)
            ->where('weekday', $weekday);

        if ($period) $todayQuery->where('academic_period_id', (int) $period->id);
        if ($user->isStudent()) $todayQuery->where('group_id', (int) $user->student->group_id);
        if ($user->isTeacher()) $todayQuery->where('teacher_id', (int) $user->id);

        $todayEntries = $todayQuery->orderBy('starts_at')->get();

        return view('schedule.index', compact('periods','period','entries','groups','subjects','teachers','todayEntries'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'group_id' => ['required','exists:groups,id'],
            'subject_id' => ['required','exists:subjects,id'],
            'teacher_id' => ['nullable','exists:users,id'],
            'academic_period_id' => ['nullable','exists:academic_periods,id'],
            'weekday' => ['required','integer','between:1,7'],
            'starts_at' => ['required','date_format:H:i'],
            'ends_at' => ['required','date_format:H:i','after:starts_at'],
            'room' => ['nullable','string','max:100'],
            'lesson_type' => ['nullable','string','max:100'],
            'note' => ['nullable','string','max:255'],
        ]);

        if (empty($data['academic_period_id'])) {
            $data['academic_period_id'] = AcademicPeriod::where('active', true)->value('id');
        }

        ScheduleEntry::create($data);
        return back()->with('success', 'Пара добавлена в расписание.');
    }

    public function destroy(Request $request, ScheduleEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $entry->delete();
        return back()->with('success', 'Пара удалена из расписания.');
    }

    public function events(Request $request): JsonResponse
    {
        $user = $request->user();
        $start = $request->date('start') ?? now()->startOfMonth();
        $end = $request->date('end') ?? now()->endOfMonth();

        if ($user->isStudent()) {
            $groupIds = array_values(array_filter([(int) optional($user->student)->group_id]));
        } elseif ($user->isTeacher()) {
            $groupIds = $user->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        } else {
            $groupIds = Group::pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $events = collect();

        Lesson::with(['group','subject','workType'])
            ->whereIn('group_id', $groupIds)
            ->whereBetween('lesson_date', [$start, $end])
            ->get()
            ->each(function ($lesson) use ($events, $user) {
                if ($user->isTeacher() && !$user->groups()->where('groups.id', (int) $lesson->group_id)->wherePivot('subject_id', (int) $lesson->subject_id)->exists()) return;
                $events->push([
                    'type' => 'lesson',
                    'date' => $lesson->lesson_date->format('Y-m-d'),
                    'title' => $lesson->subject->name.($lesson->topic ? ' · '.$lesson->topic : ''),
                    'meta' => $lesson->group->name.' · '.($lesson->workType?->name ?? 'Занятие'),
                ]);
            });

        Homework::with(['group','subject','workType'])
            ->whereIn('group_id', $groupIds)
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get()
            ->each(function ($hw) use ($events, $user) {
                if ($user->isTeacher() && (int) $hw->teacher_id !== (int) $user->id) return;
                $events->push([
                    'type' => 'homework',
                    'date' => $hw->due_at->format('Y-m-d'),
                    'time' => $hw->due_at->format('H:i'),
                    'title' => 'ДЗ: '.$hw->title,
                    'meta' => $hw->subject->name.' · '.$hw->group->name.' · ×'.number_format((float) ($hw->grade_weight ?? 1), 1),
                ]);
            });

        return response()->json($events->sortBy(fn ($event) => (string) $event['date'].(string) ($event['time'] ?? ''))->values());
    }
}

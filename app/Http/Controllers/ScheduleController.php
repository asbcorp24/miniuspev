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
        $period = $request->integer('period_id') ? $periods->firstWhere('id',$request->integer('period_id')) : ($periods->firstWhere('active',true) ?: $periods->first());

        $query = ScheduleEntry::with(['group','subject','teacher','academicPeriod'])->where('active',true);
        if ($period) $query->where('academic_period_id',$period->id);

        if ($user->isStudent()) {
            abort_unless($user->student_id && $user->student,403);
            $query->where('group_id',$user->student->group_id);
        } elseif ($user->isTeacher()) {
            $query->where('teacher_id',$user->id);
        }

        $entries = $query->orderBy('weekday')->orderBy('starts_at')->get()->groupBy('weekday');
        $groups = $user->isAdmin() ? Group::orderBy('name')->get() : collect();
        $subjects = $user->isAdmin() ? Subject::orderBy('name')->get() : collect();
        $teachers = $user->isAdmin() ? User::where('role','teacher')->orderBy('name')->get() : collect();

        $todayEntries = ScheduleEntry::with(['group','subject','teacher'])
            ->where('active',true)
            ->where('weekday',now()->dayOfWeekIso)
            ->when($period,fn($q)=>$q->where('academic_period_id',$period->id))
            ->when($user->isStudent(),fn($q)=>$q->where('group_id',$user->student->group_id))
            ->when($user->isTeacher(),fn($q)=>$q->where('teacher_id',$user->id))
            ->orderBy('starts_at')->get();

        return view('schedule.index',compact('periods','period','entries','groups','subjects','teachers','todayEntries'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(),403);
        $data = $request->validate([
            'group_id'=>['required','exists:groups,id'],
            'subject_id'=>['required','exists:subjects,id'],
            'teacher_id'=>['nullable','exists:users,id'],
            'academic_period_id'=>['nullable','exists:academic_periods,id'],
            'weekday'=>['required','integer','between:1,7'],
            'starts_at'=>['required','date_format:H:i'],
            'ends_at'=>['required','date_format:H:i','after:starts_at'],
            'room'=>['nullable','string','max:100'],
            'lesson_type'=>['nullable','string','max:100'],
            'note'=>['nullable','string','max:255'],
        ]);
        if (empty($data['academic_period_id'])) $data['academic_period_id']=AcademicPeriod::where('active',true)->value('id');
        ScheduleEntry::create($data);
        return back()->with('success','Пара добавлена в расписание.');
    }

    public function destroy(Request $request, ScheduleEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(),403);
        $entry->delete();
        return back()->with('success','Пара удалена из расписания.');
    }

    public function events(Request $request): JsonResponse
    {
        $user=$request->user();
        $start=$request->date('start') ?? now()->startOfMonth();
        $end=$request->date('end') ?? now()->endOfMonth();
        $groupIds=[];
        if($user->isStudent()) $groupIds=[$user->student?->group_id];
        elseif($user->isTeacher()) $groupIds=$user->groups()->pluck('groups.id')->unique()->all();
        else $groupIds=Group::pluck('id')->all();

        $events=collect();
        Lesson::with(['group','subject','workType'])->whereIn('group_id',$groupIds)->whereBetween('lesson_date',[$start,$end])->get()->each(function($lesson)use($events,$user){
            if($user->isTeacher() && !$user->groups()->where('groups.id',$lesson->group_id)->wherePivot('subject_id',$lesson->subject_id)->exists()) return;
            $events->push(['type'=>'lesson','date'=>$lesson->lesson_date->format('Y-m-d'),'title'=>$lesson->subject->name.($lesson->topic?' · '.$lesson->topic:''),'meta'=>$lesson->group->name.' · '.($lesson->workType?->name ?? 'Занятие')]);
        });
        Homework::with(['group','subject','workType'])->whereIn('group_id',$groupIds)->whereNotNull('due_at')->whereBetween('due_at',[$start->copy()->startOfDay(),$end->copy()->endOfDay()])->get()->each(function($hw)use($events,$user){
            if($user->isTeacher() && $hw->teacher_id!==$user->id) return;
            $events->push(['type'=>'homework','date'=>$hw->due_at->format('Y-m-d'),'time'=>$hw->due_at->format('H:i'),'title'=>'ДЗ: '.$hw->title,'meta'=>$hw->subject->name.' · '.$hw->group->name.' · ×'.number_format($hw->grade_weight ?? 1,1)]);
        });
        return response()->json($events->sortBy(fn($e)=>$e['date'].($e['time']??''))->values());
    }
}

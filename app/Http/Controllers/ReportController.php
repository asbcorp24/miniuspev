<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\FinalGrade;
use App\Models\Group;
use App\Models\JournalRecord;
use App\Models\Student;
use App\Models\Subject;
use App\Services\GradeCalculationService;
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

    private function allowedSubjects(): array
    {
        $user = auth()->user();
        return $user->isAdmin() ? Subject::pluck('id')->all() : $user->subjects()->pluck('subjects.id')->unique()->all();
    }

    public function index(Request $request): View
    {
        abort_if(auth()->user()->isStudent(), 403);
        $periods = AcademicPeriod::orderByDesc('active')->orderByDesc('academic_year')->orderBy('semester')->get();
        $period = $request->integer('period_id') ? $periods->firstWhere('id',$request->integer('period_id')) : ($periods->firstWhere('active',true) ?: $periods->first());
        $groups = Group::whereIn('id',$this->allowedGroups())->orderBy('name')->get();
        $subjects = Subject::whereIn('id',$this->allowedSubjects())->orderBy('name')->get();
        $groupId = (int)($request->integer('group_id') ?: optional($groups->first())->id);
        $subjectId = (int)($request->integer('subject_id') ?: optional($subjects->first())->id);
        abort_if($groupId && !in_array($groupId,$this->allowedGroups()),403);
        abort_if($subjectId && !in_array($subjectId,$this->allowedSubjects()),403);

        $group = $groupId ? Group::with('students')->find($groupId) : null;
        $subject = $subjectId ? Subject::find($subjectId) : null;
        $rows = collect();

        if ($group && $subject && $period) {
            $rows = $group->students->map(function(Student $student) use ($subject,$period) {
                $recordQuery = JournalRecord::where('student_id',$student->id)
                    ->whereHas('lesson',fn($q)=>$q->where('subject_id',$subject->id)->where('academic_period_id',$period->id));
                $total = (clone $recordQuery)->count();
                $present = (clone $recordQuery)->whereIn('attendance',['present','late'])->count();
                $final = FinalGrade::where('student_id',$student->id)->where('subject_id',$subject->id)->where('academic_period_id',$period->id)->first();
                return [
                    'student'=>$student,
                    'average'=>GradeCalculationService::weightedAverage($student->id,$subject->id,$period->id),
                    'final'=>$final?->final_grade,
                    'attendance'=>$total ? round($present*100/$total,1) : null,
                    'absences'=>(clone $recordQuery)->where('attendance','absent')->count(),
                    'debts'=>(clone $recordQuery)->where('grade',2)->count() + $student->records()->where('grade',2)->whereHas('lesson',fn($q)=>$q->where('subject_id',$subject->id)->where('academic_period_id',$period->id))->count()*0,
                ];
            });
        }

        return view('reports.index',compact('periods','period','groups','subjects','group','subject','rows'));
    }

    public function csv(Request $request): Response
    {
        abort_if(auth()->user()->isStudent(),403);
        $periodId = (int)$request->integer('period_id');
        $groupId = (int)$request->integer('group_id');
        $subjectId = (int)$request->integer('subject_id');
        abort_unless(in_array($groupId,$this->allowedGroups()) && in_array($subjectId,$this->allowedSubjects()),403);
        $period = AcademicPeriod::findOrFail($periodId);
        $group = Group::with('students')->findOrFail($groupId);
        $subject = Subject::findOrFail($subjectId);

        $lines = ["ФИО;Группа;Дисциплина;Период;Взвешенный балл;Итоговая оценка;Посещаемость %;Прогулы;Двойки"];
        foreach ($group->students as $student) {
            $recordQuery = JournalRecord::where('student_id',$student->id)->whereHas('lesson',fn($q)=>$q->where('subject_id',$subjectId)->where('academic_period_id',$periodId));
            $total=(clone $recordQuery)->count(); $present=(clone $recordQuery)->whereIn('attendance',['present','late'])->count();
            $final=FinalGrade::where('student_id',$student->id)->where('subject_id',$subjectId)->where('academic_period_id',$periodId)->value('final_grade');
            $lines[] = implode(';',[
                $student->full_name,$group->name,$subject->name,$period->label,
                GradeCalculationService::weightedAverage($student->id,$subjectId,$periodId) ?? '',
                $final ?? '',$total ? round($present*100/$total,1) : '',
                (clone $recordQuery)->where('attendance','absent')->count(),
                (clone $recordQuery)->where('grade',2)->count(),
            ]);
        }
        $content="\xEF\xBB\xBF".implode("\r\n",$lines);
        return response($content,200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="semester-report-'.$group->name.'.csv"']);
    }
}

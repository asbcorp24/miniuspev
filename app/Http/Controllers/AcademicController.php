<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\FinalGrade;
use App\Models\Group;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Models\WorkType;
use App\Services\GradeAuditService;
use App\Services\GradeCalculationService;
use App\Services\StudentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicController extends Controller
{
    private function ensureStaff(): void { abort_if(auth()->user()->isStudent(), 403); }

    public function settings(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        return view('academic.settings', [
            'periods' => AcademicPeriod::orderByDesc('academic_year')->orderBy('semester')->get(),
            'workTypes' => WorkType::orderBy('name')->get(),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $data = $request->validate([
            'academic_year' => ['required','string','max:20'], 'semester' => ['required','integer','between:1,2'],
            'starts_at' => ['nullable','date'], 'ends_at' => ['nullable','date','after_or_equal:starts_at'], 'active' => ['nullable','boolean'],
        ]);
        $data['active'] = $request->boolean('active');
        if ($data['active']) AcademicPeriod::query()->update(['active' => false]);
        AcademicPeriod::create($data);
        return back()->with('success','Учебный период создан.');
    }

    public function activatePeriod(AcademicPeriod $period): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        AcademicPeriod::query()->update(['active' => false]);
        $period->update(['active' => true]);
        return back()->with('success','Активный семестр изменён.');
    }

    public function storeWorkType(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        WorkType::create($request->validate([
            'name' => ['required','string','max:100'], 'code' => ['required','string','max:50','unique:work_types,code'],
            'default_weight' => ['required','numeric','min:0.1','max:10'],
        ]));
        return back()->with('success','Тип работы добавлен.');
    }

    public function finals(Request $request): View
    {
        $this->ensureStaff();
        $user = auth()->user();
        $periods = AcademicPeriod::orderByDesc('active')->orderByDesc('academic_year')->orderBy('semester')->get();
        $periodId = (int)($request->integer('period_id') ?: optional($periods->firstWhere('active', true) ?: $periods->first())->id);
        $groups = $user->isAdmin() ? Group::orderBy('name')->get() : $user->groups()->distinct()->orderBy('name')->get();
        $subjects = $user->isAdmin() ? Subject::orderBy('name')->get() : $user->subjects()->distinct()->orderBy('name')->get();
        $groupId = (int)($request->integer('group_id') ?: optional($groups->first())->id);
        $subjectId = (int)($request->integer('subject_id') ?: optional($subjects->first())->id);
        if (!$user->isAdmin() && $groupId && $subjectId) abort_unless($user->groups()->where('groups.id',$groupId)->wherePivot('subject_id',$subjectId)->exists(), 403);
        $students = $groupId ? Student::where('group_id',$groupId)->where('active',true)->orderBy('last_name')->orderBy('first_name')->get() : collect();
        $rows = $students->map(function(Student $student) use ($subjectId,$periodId) {
            $calculated = ($subjectId && $periodId) ? GradeCalculationService::weightedAverage($student->id,$subjectId,$periodId) : null;
            $final = ($subjectId && $periodId) ? FinalGrade::where('student_id',$student->id)->where('subject_id',$subjectId)->where('academic_period_id',$periodId)->first() : null;
            return compact('student','calculated','final');
        });
        return view('academic.finals', compact('periods','groups','subjects','periodId','groupId','subjectId','rows'));
    }

    public function setFinal(Request $request, Student $student): RedirectResponse
    {
        $this->ensureStaff();
        $data = $request->validate([
            'subject_id' => ['required','exists:subjects,id'], 'academic_period_id' => ['required','exists:academic_periods,id'],
            'final_grade' => ['nullable','integer','between:2,5'], 'comment' => ['nullable','string','max:1000'],
            'reason' => ['nullable','string','max:255'],
        ]);
        $user = auth()->user();
        if (!$user->isAdmin()) abort_unless($user->groups()->where('groups.id',$student->group_id)->wherePivot('subject_id',$data['subject_id'])->exists(),403);

        $existing = FinalGrade::where('student_id',$student->id)->where('subject_id',$data['subject_id'])->where('academic_period_id',$data['academic_period_id'])->first();
        $oldGrade = $existing?->final_grade !== null ? (int)$existing->final_grade : null;
        $newGrade = isset($data['final_grade']) ? (int)$data['final_grade'] : null;
        $calculated = GradeCalculationService::weightedAverage($student->id,(int)$data['subject_id'],(int)$data['academic_period_id']);
        $final = FinalGrade::updateOrCreate(
            ['student_id'=>$student->id,'subject_id'=>$data['subject_id'],'academic_period_id'=>$data['academic_period_id']],
            ['calculated_grade'=>$calculated,'final_grade'=>$data['final_grade'] ?? null,'comment'=>$data['comment'] ?? null,'set_by'=>$user->id]
        );

        GradeAuditService::log($student->id,'final',$final->id,$oldGrade,$newGrade,$user,$data['reason'] ?? ($oldGrade===null?'Первичная итоговая оценка':'Корректировка итоговой оценки'),$data['comment'] ?? null);

        if ($final->final_grade) {
            $studentUser = User::where('role','student')->where('student_id',$student->id)->first();
            $subject = Subject::find($data['subject_id']); $period = AcademicPeriod::find($data['academic_period_id']);
            if ($studentUser) StudentNotificationService::createForUser($studentUser,'final_grade','Выставлена итоговая оценка',($subject?->name ?? 'Дисциплина').' · '.($period?->label ?? 'семестр').' — итоговая оценка '.$final->final_grade.($final->comment ? '. '.$final->comment : ''),route('student.dashboard',['period_id'=>$data['academic_period_id']]),'final-grade:'.$final->id.':'.($final->updated_at?->timestamp ?? time()),['final_grade_id'=>$final->id,'grade'=>$final->final_grade]);
        }
        return back()->with('success','Итоговая оценка сохранена.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Group;
use App\Models\JournalRecord;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Subject;
use App\Models\WorkType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    private function allowedGroupIds(): array
    {
        $user = auth()->user();
        if ($user->isAdmin()) return Group::pluck('id')->all();
        return $user->groups()->pluck('groups.id')->unique()->values()->all();
    }

    private function allowedSubjectIds(): array
    {
        $user = auth()->user();
        if ($user->isAdmin()) return Subject::pluck('id')->all();
        return $user->subjects()->pluck('subjects.id')->unique()->values()->all();
    }

    private function canTeach(int $groupId, int $subjectId): bool
    {
        $user = auth()->user();
        if ($user->isAdmin()) return true;
        return $user->groups()->where('groups.id', $groupId)->wherePivot('subject_id', $subjectId)->exists();
    }

    public function dashboard(): View
    {
        $groupIds = $this->allowedGroupIds();
        $subjectIds = $this->allowedSubjectIds();
        $students = Student::with('group')->whereIn('group_id', $groupIds)->where('active', true)->get();
        $recordQuery = JournalRecord::whereHas('lesson', fn($q) => $q->whereIn('group_id', $groupIds)->whereIn('subject_id', $subjectIds));
        $avg = (clone $recordQuery)->whereNotNull('grade')->avg('grade');
        $total = (clone $recordQuery)->count();
        $absent = (clone $recordQuery)->where('attendance', 'absent')->count();

        return view('dashboard', [
            'groups' => Group::whereIn('id', $groupIds)->withCount('students')->orderBy('name')->get(),
            'subjects' => Subject::whereIn('id', $subjectIds)->orderBy('name')->get(),
            'studentsCount' => $students->count(),
            'averageGrade' => $avg ? round((float) $avg, 2) : null,
            'absencePercent' => $total ? round($absent * 100 / $total, 1) : 0,
            'recentLessons' => Lesson::with(['group', 'subject'])->whereIn('group_id', $groupIds)->whereIn('subject_id', $subjectIds)->latest('lesson_date')->limit(10)->get(),
        ]);
    }

    public function journal(Request $request): View
    {
        $groups = Group::whereIn('id', $this->allowedGroupIds())->orderBy('name')->get();
        $subjects = Subject::whereIn('id', $this->allowedSubjectIds())->orderBy('name')->get();
        $periods = AcademicPeriod::orderByDesc('active')->orderByDesc('academic_year')->orderBy('semester')->get();
        $workTypes = WorkType::where('active', true)->orderBy('name')->get();
        $activePeriod = $periods->firstWhere('active', true);
        $groupId = (int) ($request->integer('group_id') ?: optional($groups->first())->id);
        $subjectId = (int) ($request->integer('subject_id') ?: optional($subjects->first())->id);
        $periodId = (int) ($request->integer('period_id') ?: optional($activePeriod)->id);

        if ($groupId && $subjectId && !$this->canTeach($groupId, $subjectId)) $subjectId = 0;

        $group = $groupId ? Group::with('students')->find($groupId) : null;
        $subject = $subjectId ? Subject::find($subjectId) : null;
        $lessons = collect();

        if ($group && $subject) {
            $lessons = Lesson::where('group_id', $group->id)
                ->where('subject_id', $subject->id)
                ->when($periodId, fn($q) => $q->where('academic_period_id', $periodId))
                ->with(['records','workType','academicPeriod'])
                ->orderBy('lesson_date')
                ->get();
        }

        return view('journal', compact('groups','subjects','periods','workTypes','activePeriod','periodId','group','subject','lessons'));
    }

    public function createLesson(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'group_id' => ['required','exists:groups,id'],
            'subject_id' => ['required','exists:subjects,id'],
            'academic_period_id' => ['nullable','exists:academic_periods,id'],
            'work_type_id' => ['nullable','exists:work_types,id'],
            'grade_weight' => ['nullable','numeric','min:0.1','max:10'],
            'lesson_date' => ['required','date'],
            'topic' => ['nullable','string','max:255'],
        ]);
        abort_unless($this->canTeach((int)$data['group_id'], (int)$data['subject_id']), 403);

        if (empty($data['academic_period_id'])) $data['academic_period_id'] = AcademicPeriod::where('active',true)->value('id');
        if (!empty($data['work_type_id']) && empty($data['grade_weight'])) $data['grade_weight'] = WorkType::find($data['work_type_id'])?->default_weight ?? 1;
        $data['grade_weight'] = $data['grade_weight'] ?? 1;

        $lesson = Lesson::create($data);
        $studentIds = Student::where('group_id', $lesson->group_id)->where('active', true)->pluck('id');
        foreach ($studentIds as $studentId) {
            JournalRecord::firstOrCreate(['lesson_id' => $lesson->id, 'student_id' => $studentId], ['attendance' => 'present']);
        }

        return redirect()->route('journal', ['group_id'=>$lesson->group_id,'subject_id'=>$lesson->subject_id,'period_id'=>$lesson->academic_period_id])
            ->with('success', 'Занятие добавлено в журнал.');
    }

    public function updateRecord(Request $request, JournalRecord $record): JsonResponse
    {
        $record->loadMissing('lesson');
        abort_unless($this->canTeach($record->lesson->group_id, $record->lesson->subject_id), 403);
        $data = $request->validate([
            'attendance' => ['sometimes','required','in:present,absent,late,excused'],
            'grade' => ['sometimes','nullable','integer','between:2,5'],
            'comment' => ['sometimes','nullable','string','max:255'],
        ]);
        $record->update($data);
        $record->load('student');
        return response()->json([
            'ok'=>true,'record'=>$record,
            'average'=>$record->student->averageGrade($record->lesson->subject_id),
            'attendance_percent'=>$record->student->attendancePercent($record->lesson->subject_id),
        ]);
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        Group::create($request->validate(['name'=>['required','string','max:100','unique:groups,name'],'course'=>['nullable','integer','between:1,6'],'speciality'=>['nullable','string','max:255']]));
        return back()->with('success','Группа добавлена.');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        Student::create($request->validate(['group_id'=>['required','exists:groups,id'],'last_name'=>['required','string','max:100'],'first_name'=>['required','string','max:100'],'middle_name'=>['nullable','string','max:100'],'student_number'=>['nullable','string','max:100','unique:students,student_number']]));
        return back()->with('success','Студент добавлен.');
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        Subject::create($request->validate(['name'=>['required','string','max:255'],'code'=>['nullable','string','max:50','unique:subjects,code']]));
        return back()->with('success','Дисциплина добавлена.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Homework;
use App\Models\HomeworkFile;
use App\Models\HomeworkSubmission;
use App\Models\Student;
use App\Models\Group;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HomeworkController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->role === 'student') {
            abort_unless($user->student_id, 403);
            StudentNotificationService::syncDeadlineReminders($user);
            $student = Student::with('group')->findOrFail($user->student_id);
            $homeworks = Homework::with(['subject','group','submissions' => fn($q) => $q->where('student_id', $student->id)->with('files')])
                ->where('group_id', $student->group_id)
                ->latest('due_at')
                ->get();
            return view('homeworks.student', compact('student','homeworks'));
        }

        $query = Homework::with(['group','subject','teacher'])->withCount(['submissions','submissions as graded_count' => fn($q) => $q->whereNotNull('grade')]);
        if (!$user->isAdmin()) {
            $query->where('teacher_id', $user->id);
        }

        $groups = $user->isAdmin() ? Group::orderBy('name')->get() : $user->groups()->distinct()->orderBy('name')->get();
        $subjects = $user->isAdmin() ? Subject::orderBy('name')->get() : $user->subjects()->distinct()->orderBy('name')->get();
        $homeworks = $query->latest('created_at')->get();

        return view('homeworks.index', compact('homeworks','groups','subjects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->role === 'student', 403);

        $data = $request->validate([
            'group_id' => ['required','exists:groups,id'],
            'subject_id' => ['required','exists:subjects,id'],
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'due_at' => ['nullable','date'],
        ]);

        if (!$user->isAdmin()) {
            $allowed = $user->groups()->where('groups.id', $data['group_id'])->wherePivot('subject_id', $data['subject_id'])->exists();
            abort_unless($allowed, 403);
        }

        $data['teacher_id'] = $user->id;
        $homework = Homework::create($data);
        $homework->load('subject');

        StudentNotificationService::createForGroup(
            $homework->group_id,
            'homework',
            'Новое домашнее задание',
            $homework->subject->name.': '.$homework->title.($homework->due_at ? ' · до '.$homework->due_at->format('d.m.Y H:i') : ''),
            route('homeworks.index'),
            'homework:'.$homework->id,
            ['homework_id' => $homework->id]
        );

        return back()->with('success', 'Домашнее задание создано.');
    }

    public function show(Request $request, Homework $homework): View
    {
        $user = $request->user();
        abort_if($user->role === 'student', 403);
        if (!$user->isAdmin()) abort_unless($homework->teacher_id === $user->id, 403);

        $homework->load(['group.students','subject','submissions.student','submissions.files']);
        return view('homeworks.show', compact('homework'));
    }

    public function submit(Request $request, Homework $homework): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'student' && $user->student_id, 403);
        $student = Student::findOrFail($user->student_id);
        abort_unless($student->group_id === $homework->group_id, 403);

        $data = $request->validate([
            'student_comment' => ['nullable','string','max:2000'],
            'files' => ['required','array','min:1','max:10'],
            'files.*' => ['file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
        ]);

        $submission = HomeworkSubmission::updateOrCreate(
            ['homework_id' => $homework->id, 'student_id' => $student->id],
            ['student_comment' => $data['student_comment'] ?? null, 'submitted_at' => now(), 'status' => 'submitted', 'grade' => null, 'graded_at' => null]
        );

        foreach ($request->file('files', []) as $file) {
            $path = $file->store("homeworks/{$homework->id}/{$student->id}", 'local');
            HomeworkFile::create([
                'submission_id' => $submission->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return back()->with('success', 'Домашнее задание отправлено преподавателю.');
    }

    public function grade(Request $request, HomeworkSubmission $submission): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->role === 'student', 403);
        $submission->load('homework.subject');
        if (!$user->isAdmin()) abort_unless($submission->homework->teacher_id === $user->id, 403);

        $data = $request->validate([
            'grade' => ['required','integer','between:2,5'],
            'teacher_comment' => ['nullable','string','max:2000'],
        ]);

        $submission->update([
            'grade' => $data['grade'],
            'teacher_comment' => $data['teacher_comment'] ?? null,
            'graded_at' => now(),
            'status' => 'graded',
        ]);

        $studentUser = User::where('role','student')->where('student_id', $submission->student_id)->first();
        if ($studentUser) {
            StudentNotificationService::createForUser(
                $studentUser,
                'homework_grade',
                'Домашняя работа проверена',
                $submission->homework->subject->name.': '.$submission->homework->title.' — оценка '.$data['grade'].($data['teacher_comment'] ? '. '.$data['teacher_comment'] : ''),
                route('homeworks.index'),
                'homework-grade:'.$submission->id.':'.$submission->updated_at?->timestamp,
                ['submission_id' => $submission->id, 'grade' => $data['grade']]
            );
        }

        return back()->with('success', 'Оценка за домашнее задание сохранена.');
    }

    public function returnForRevision(Request $request, HomeworkSubmission $submission): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->role === 'student', 403);
        $submission->load('homework.subject');
        if (!$user->isAdmin()) abort_unless($submission->homework->teacher_id === $user->id, 403);

        $data = $request->validate([
            'teacher_comment' => ['required','string','max:2000'],
        ]);

        $submission->update([
            'grade' => null,
            'teacher_comment' => $data['teacher_comment'],
            'graded_at' => null,
            'status' => 'returned',
        ]);

        $studentUser = User::where('role','student')->where('student_id', $submission->student_id)->first();
        if ($studentUser) {
            StudentNotificationService::createForUser(
                $studentUser,
                'homework_returned',
                'Работа возвращена на доработку',
                $submission->homework->subject->name.': '.$submission->homework->title.'. '.$data['teacher_comment'],
                route('homeworks.index'),
                'homework-returned:'.$submission->id.':'.$submission->updated_at?->timestamp,
                ['submission_id' => $submission->id]
            );
        }

        return back()->with('success', 'Работа возвращена студенту на доработку.');
    }

    public function download(Request $request, HomeworkFile $file): BinaryFileResponse
    {
        $file->load('submission.homework');
        $user = $request->user();
        $allowed = $user->isAdmin()
            || ($user->role === 'teacher' && $file->submission->homework->teacher_id === $user->id)
            || ($user->role === 'student' && $file->submission->student_id === $user->student_id);
        abort_unless($allowed, 403);
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        return response()->download(Storage::disk('local')->path($file->path), $file->original_name);
    }
}

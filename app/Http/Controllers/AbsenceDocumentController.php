<?php

namespace App\Http\Controllers;

use App\Models\AbsenceDocument;
use App\Models\JournalRecord;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AbsenceDocumentController extends Controller
{
    private function canReview(Request $request, Student $student): bool
    {
        $user = $request->user();
        if ($user->isAdmin()) return true;
        if (!$user->isTeacher()) return false;
        return $user->groups()->where('groups.id', $student->group_id)->exists();
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isStudent()) {
            abort_unless($user->student_id, 403);
            $documents = AbsenceDocument::with('reviewer')
                ->where('student_id', $user->student_id)
                ->latest()
                ->get();
            return view('absence-documents.student', compact('documents'));
        }

        $query = AbsenceDocument::with(['student.group','reviewer'])->latest();
        if ($user->isTeacher()) {
            $groupIds = $user->groups()->pluck('groups.id')->unique()->all();
            $query->whereHas('student', fn($q) => $q->whereIn('group_id', $groupIds));
        } elseif (!$user->isAdmin()) {
            abort(403);
        }

        $documents = $query->get();
        return view('absence-documents.staff', compact('documents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStudent() && $user->student_id, 403);

        $data = $request->validate([
            'date_from' => ['required','date'],
            'date_to' => ['required','date','after_or_equal:date_from'],
            'student_comment' => ['nullable','string','max:2000'],
            'file' => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('absence-documents/'.$user->student_id, 'local');

        AbsenceDocument::create([
            'student_id' => $user->student_id,
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'student_comment' => $data['student_comment'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'pending',
        ]);

        return back()->with('success','Справка отправлена на проверку.');
    }

    public function review(Request $request, AbsenceDocument $document): RedirectResponse
    {
        $document->load('student');
        abort_unless($this->canReview($request, $document->student), 403);

        $data = $request->validate([
            'status' => ['required','in:approved,rejected'],
            'review_comment' => ['nullable','string','max:2000'],
        ]);

        $document->update([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'review_comment' => $data['review_comment'] ?? null,
            'reviewed_at' => now(),
        ]);

        if ($data['status'] === 'approved') {
            $query = JournalRecord::where('student_id', $document->student_id)
                ->where('attendance', 'absent')
                ->whereHas('lesson', function ($q) use ($document) {
                    $q->whereBetween('lesson_date', [$document->date_from->format('Y-m-d'), $document->date_to->format('Y-m-d')]);
                });

            if ($request->user()->isTeacher()) {
                $subjectIds = $request->user()->subjects()->pluck('subjects.id')->unique()->all();
                $query->whereHas('lesson', fn($q) => $q->whereIn('subject_id', $subjectIds));
            }

            $query->update(['attendance' => 'excused']);
        }

        $studentUser = User::where('role','student')->where('student_id',$document->student_id)->first();
        if ($studentUser) {
            StudentNotificationService::createForUser(
                $studentUser,
                'absence_document',
                $data['status'] === 'approved' ? 'Справка подтверждена' : 'Справка отклонена',
                'Период '.$document->date_from->format('d.m.Y').'–'.$document->date_to->format('d.m.Y').($data['review_comment'] ? '. '.$data['review_comment'] : ''),
                route('absence-documents.index'),
                'absence-document:'.$document->id.':'.$data['status'].':'.now()->timestamp,
                ['document_id'=>$document->id,'status'=>$data['status']]
            );
        }

        return back()->with('success', $data['status'] === 'approved' ? 'Справка подтверждена.' : 'Справка отклонена.');
    }

    public function download(Request $request, AbsenceDocument $document): BinaryFileResponse
    {
        $document->load('student');
        $user = $request->user();
        $allowed = $user->isAdmin()
            || ($user->isStudent() && $user->student_id === $document->student_id)
            || ($user->isTeacher() && $this->canReview($request, $document->student));
        abort_unless($allowed, 403);
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        return response()->download(Storage::disk('local')->path($document->path), $document->original_name);
    }
}

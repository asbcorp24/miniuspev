<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Student;
use App\Services\StudentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonContentController extends Controller
{
    private function canView(Request $request, Lesson $lesson): bool
    {
        $user = $request->user();
        if ($user->isAdmin()) return true;
        if ($user->isTeacher()) {
            return $user->groups()->where('groups.id',$lesson->group_id)->wherePivot('subject_id',$lesson->subject_id)->exists();
        }
        if ($user->isStudent() && $user->student_id) {
            return Student::whereKey($user->student_id)->where('group_id',$lesson->group_id)->exists();
        }
        return false;
    }

    private function canEdit(Request $request, Lesson $lesson): bool
    {
        $user = $request->user();
        if ($user->isAdmin()) return true;
        if (!$user->isTeacher()) return false;
        return $user->groups()->where('groups.id',$lesson->group_id)->wherePivot('subject_id',$lesson->subject_id)->exists();
    }

    public function index(Request $request): View
    {
        $user=$request->user();
        $query=Lesson::with(['group','subject','workType','materials'])->orderByDesc('lesson_date')->orderByDesc('id');

        if ($user->isStudent()) {
            abort_unless($user->student_id && $user->student,403);
            $query->where('group_id',$user->student->group_id);
        } elseif ($user->isTeacher()) {
            $pairs=$user->groups()->get()->map(fn($g)=>[$g->id,(int)$g->pivot->subject_id]);
            if ($pairs->isEmpty()) {
                $query->whereRaw('1=0');
            } else {
                $query->where(function($q) use($pairs){
                    foreach($pairs as [$groupId,$subjectId]) {
                        $q->orWhere(fn($x)=>$x->where('group_id',$groupId)->where('subject_id',$subjectId));
                    }
                });
            }
        }

        $lessons=$query->paginate(30);
        return view('lessons.index',compact('lessons'));
    }

    public function show(Request $request, Lesson $lesson): View
    {
        abort_unless($this->canView($request,$lesson),403);
        $lesson->load(['group','subject','workType','academicPeriod','materials']);
        return view('lessons.show',compact('lesson'));
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        abort_unless($this->canEdit($request,$lesson),403);
        $data=$request->validate([
            'lesson_kind'=>['nullable','string','max:100'],
            'topic'=>['nullable','string','max:255'],
            'description'=>['nullable','string','max:20000'],
            'materials'=>['nullable','array','max:10'],
            'materials.*'=>['file','mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip','max:20480'],
            'links'=>['nullable','string','max:12000'],
            'video_links'=>['nullable','string','max:12000'],
        ]);
        $lesson->update([
            'lesson_kind'=>$data['lesson_kind']??null,
            'topic'=>$data['topic']??null,
            'description'=>$data['description']??null,
        ]);

        foreach($request->file('materials',[]) as $file){
            $path=$file->store("lesson-materials/{$lesson->id}",'local');
            LessonMaterial::create([
                'lesson_id'=>$lesson->id,'type'=>'file','title'=>$file->getClientOriginalName(),
                'path'=>$path,'original_name'=>$file->getClientOriginalName(),
                'mime_type'=>$file->getMimeType(),'size'=>$file->getSize()
            ]);
        }
        foreach($this->parseLinks($request->input('links')) as $url)
            LessonMaterial::create(['lesson_id'=>$lesson->id,'type'=>'link','title'=>$url,'url'=>$url]);
        foreach($this->parseLinks($request->input('video_links')) as $url)
            LessonMaterial::create(['lesson_id'=>$lesson->id,'type'=>'video','title'=>$url,'url'=>$url]);

        $lesson->loadMissing('subject');
        StudentNotificationService::createForGroup(
            $lesson->group_id,
            'lesson_material',
            'Материалы урока опубликованы',
            ($lesson->subject?->name ? $lesson->subject->name.': ' : '').($lesson->topic ?: 'новый материал урока'),
            route('lessons.content.show',$lesson),
            'lesson-material:'.$lesson->id.':'.$lesson->updated_at?->timestamp,
            ['lesson_id'=>$lesson->id]
        );

        return back()->with('success','Материалы урока сохранены и доступны студентам.');
    }

    public function destroyMaterial(Request $request, LessonMaterial $material): RedirectResponse
    {
        $material->load('lesson');
        abort_unless($this->canEdit($request,$material->lesson),403);
        if($material->type==='file' && $material->path) Storage::disk('local')->delete($material->path);
        $material->delete();
        return back()->with('success','Материал урока удалён.');
    }

    public function viewMaterial(Request $request, LessonMaterial $material): BinaryFileResponse
    {
        $material->load('lesson');
        abort_unless($this->canView($request,$material->lesson),403);
        abort_unless($material->type==='file' && $material->isPreviewable(),404);
        abort_unless(Storage::disk('local')->exists($material->path),404);
        return response()->file(Storage::disk('local')->path($material->path),['Content-Type'=>$material->mime_type]);
    }

    public function downloadMaterial(Request $request, LessonMaterial $material): BinaryFileResponse
    {
        $material->load('lesson');
        abort_unless($this->canView($request,$material->lesson),403);
        abort_unless($material->type==='file' && Storage::disk('local')->exists($material->path),404);
        return response()->download(Storage::disk('local')->path($material->path),$material->original_name ?: 'material');
    }

    private function parseLinks(?string $value): array
    {
        if(!$value) return [];
        return collect(preg_split('/\r\n|\r|\n/',$value))->map(fn($v)=>trim($v))->filter(fn($v)=>filter_var($v,FILTER_VALIDATE_URL))->unique()->values()->all();
    }
}

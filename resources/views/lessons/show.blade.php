@extends('layout')
@section('title',$lesson->topic ?: 'Урок')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <a href="{{ route('lessons.content.index') }}" class="text-decoration-none">← Уроки и лекции</a>
        <h1 class="h3 mt-2 mb-1">{{ $lesson->topic ?: 'Без темы' }}</h1>
        <div class="text-muted">{{ $lesson->subject?->name }} · {{ $lesson->group?->name }} · {{ $lesson->lesson_date?->format('d.m.Y') }}</div>
        <div class="mt-2"><span class="badge text-bg-primary">{{ $lesson->lesson_kind ?: ($lesson->workType?->name ?? 'Занятие') }}</span></div>
    </div>
</div>

@if($lesson->description)
<div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white"><strong>Материал урока</strong></div><div class="card-body" style="white-space:pre-wrap">{{ $lesson->description }}</div></div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white"><strong>Материалы</strong></div>
    <div class="card-body">
        @forelse($lesson->materials as $m)
            <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center gap-3">
                <div>
                    <strong>{{ $m->title ?: ($m->original_name ?: $m->url) }}</strong>
                    <div class="small text-muted">{{ $m->type==='file' ? 'Файл' : ($m->type==='video' ? 'Видео' : 'Ссылка') }}</div>
                </div>
                <div class="d-flex gap-2">
                    @if($m->type==='file')
                        @if($m->isPreviewable())<a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('lessons.materials.view',$m) }}">Просмотр</a>@endif
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('lessons.materials.download',$m) }}">Скачать</a>
                    @else
                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ $m->url }}">Открыть</a>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
                    <form method="POST" action="{{ route('lessons.materials.destroy',$m) }}" onsubmit="return confirm('Удалить материал?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Удалить</button></form>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-muted">К этому уроку пока нет дополнительных файлов или ссылок.</div>
        @endforelse
    </div>
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white"><strong>Редактировать материал урока</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('lessons.content.update',$lesson) }}" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Тип занятия</label><select name="lesson_kind" class="form-select"><option value="">Занятие</option>@foreach(['Лекция','Практика','Лабораторная работа','Семинар','Контрольная работа','Консультация'] as $kind)<option value="{{ $kind }}" @selected($lesson->lesson_kind===$kind)>{{ $kind }}</option>@endforeach</select></div>
                <div class="col-md-9"><label class="form-label">Тема</label><input name="topic" class="form-control" value="{{ $lesson->topic }}"></div>
                <div class="col-12"><label class="form-label">Описание / конспект урока</label><textarea name="description" class="form-control" rows="10">{{ $lesson->description }}</textarea></div>
                <div class="col-md-4"><label class="form-label">Файлы и изображения</label><input type="file" name="materials[]" class="form-control" multiple><div class="form-text">PDF, Office, JPG/PNG/WEBP, TXT, ZIP. До 20 МБ.</div></div>
                <div class="col-md-4"><label class="form-label">Ссылки</label><textarea name="links" class="form-control" rows="3" placeholder="Одна ссылка на строку"></textarea></div>
                <div class="col-md-4"><label class="form-label">Видео</label><textarea name="video_links" class="form-control" rows="3" placeholder="YouTube / RuTube / VK Видео"></textarea></div>
                <div class="col-12"><button class="btn btn-primary">Сохранить материалы урока</button></div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

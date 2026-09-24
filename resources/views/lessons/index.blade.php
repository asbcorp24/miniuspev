@extends('layout')
@section('title','Уроки и лекции')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1">Уроки и лекции</h1><div class="text-muted">Темы занятий, конспекты, методички, файлы, ссылки и видео</div></div>
</div>

<div class="row g-3">
@forelse($lessons as $lesson)
<div class="col-lg-6 col-xl-4">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between gap-3 mb-2">
                <div>
                    <div class="small text-muted">{{ $lesson->lesson_date?->format('d.m.Y') }} · {{ $lesson->group?->name }}</div>
                    <h5 class="mb-1">{{ $lesson->topic ?: 'Без темы' }}</h5>
                    <div class="text-muted">{{ $lesson->subject?->name }}</div>
                </div>
                <span class="badge text-bg-light border align-self-start">{{ $lesson->lesson_kind ?: ($lesson->workType?->name ?? 'Занятие') }}</span>
            </div>
            @if($lesson->description)<p class="small">{{ IlluminateSupportStr::limit($lesson->description,180) }}</p>@endif
            <div class="small text-muted mb-3">Материалов: {{ $lesson->materials->count() }}</div>
            <a href="{{ route('lessons.content.show',$lesson) }}" class="btn btn-outline-primary w-100">Открыть урок</a>
        </div>
    </div>
</div>
@empty
<div class="col-12"><div class="alert alert-info">Материалы уроков пока не опубликованы.</div></div>
@endforelse
</div>
<div class="mt-4">{{ $lessons->links() }}</div>
@endsection

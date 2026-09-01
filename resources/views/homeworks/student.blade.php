@extends('layout')
@section('title','Мои домашние задания')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div><h1 class="h3 mb-1">Мои домашние задания</h1><div class="text-muted">{{ $student->full_name }} · {{ $student->group->name }}</div></div>
    @if($periods->count())<form method="GET"><label class="form-label small mb-1">Семестр</label><select name="period_id" class="form-select" onchange="this.form.submit()">@foreach($periods as $p)<option value="{{ $p->id }}" @selected($period?->id===$p->id)>{{ $p->label }}{{ $p->active?' · активный':'' }}</option>@endforeach</select></form>@endif
</div>
<div class="row g-4">
@forelse($homeworks as $h)
@php($submission=$h->submissions->first())
<div class="col-lg-6"><div class="card stat-card h-100"><div class="card-body">
    <div class="d-flex justify-content-between gap-3 mb-2"><div><h5 class="mb-1">{{ $h->title }}</h5><div class="text-muted small">{{ $h->subject->name }} · {{ $h->workType?->name ?? 'Домашняя работа' }} · вес ×{{ $h->grade_weight }}</div></div>@if($submission?->grade)<span class="badge text-bg-success fs-6">Оценка: {{ $submission->grade }}</span>@elseif($submission)<span class="badge text-bg-warning">На проверке</span>@else<span class="badge text-bg-secondary">Не сдано</span>@endif</div>
    <p class="mb-2">{{ $h->description ?: 'Описание отсутствует.' }}</p>
    <div class="small mb-3"><strong>Срок:</strong> {{ $h->due_at?$h->due_at->format('d.m.Y H:i'):'без ограничения' }} @if($h->academicPeriod)<span class="ms-2 text-muted">{{ $h->academicPeriod->label }}</span>@endif</div>
    @if($submission)<div class="border rounded p-3 mb-3 bg-light"><div class="fw-semibold mb-2">Отправленные файлы</div>@foreach($submission->files as $file)<a class="d-block" href="{{ route('homeworks.files.download',$file) }}">{{ $file->original_name }}</a>@endforeach @if($submission->teacher_comment)<div class="mt-2"><strong>Комментарий преподавателя:</strong> {{ $submission->teacher_comment }}</div>@endif</div>@endif
    <form method="POST" action="{{ route('homeworks.submit',$h) }}" enctype="multipart/form-data">@csrf
        <div class="mb-2"><label class="form-label">Комментарий к работе</label><textarea name="student_comment" class="form-control" rows="2">{{ $submission?->student_comment }}</textarea></div>
        <div class="mb-3"><label class="form-label">Сканы / PDF</label><input type="file" name="files[]" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" multiple required><div class="form-text">До 10 файлов, каждый до 10 МБ. JPG, PNG, WEBP или PDF.</div></div>
        <button class="btn btn-primary">{{ $submission?'Отправить новую версию':'Отправить работу' }}</button>
    </form>
</div></div></div>
@empty<div class="col-12"><div class="alert alert-info">За выбранный семестр домашних заданий пока нет.</div></div>@endforelse
</div>
@endsection

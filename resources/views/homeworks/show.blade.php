@extends('layout')
@section('title',$homework->title)
@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <a href="{{ route('homeworks.index') }}" class="text-decoration-none">← Домашние задания</a>
        <h1 class="h3 mt-2 mb-1">{{ $homework->title }}</h1>
        <div class="text-muted">{{ $homework->group->name }} · {{ $homework->subject->name }} · срок {{ $homework->due_at ? $homework->due_at->format('d.m.Y H:i') : 'не задан' }}</div>
        @if($homework->description)<div class="mt-3">{{ $homework->description }}</div>@endif
    </div>
</div>

<div class="card stat-card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead><tr><th class="ps-3">Студент</th><th>Статус</th><th>Файлы</th><th>Комментарий студента</th><th style="min-width:380px">Проверка</th></tr></thead>
<tbody>
@foreach($homework->group->students as $student)
@php($s = $homework->submissions->firstWhere('student_id',$student->id))
<tr>
    <td class="ps-3"><a href="{{ route('students.show',$student) }}">{{ $student->full_name }}</a></td>
    <td>
        @if(!$s)<span class="badge text-bg-secondary">Не сдано</span>
        @elseif($s->status === 'returned')<span class="badge text-bg-danger">На доработке</span>
        @elseif($s->grade)<span class="badge text-bg-success">Проверено: {{ $s->grade }}</span>
        @else<span class="badge text-bg-warning">На проверке</span>@endif
        @if($s?->submitted_at && $homework->due_at && $s->submitted_at->gt($homework->due_at))<span class="badge text-bg-danger">После срока</span>@endif
    </td>
    <td>
        @if($s)
            @foreach($s->files as $file)<a class="d-block" href="{{ route('homeworks.files.download',$file) }}">{{ $file->original_name }}</a>@endforeach
        @else — @endif
    </td>
    <td>{{ $s?->student_comment ?: '—' }}</td>
    <td>
        @if($s)
        <form method="POST" action="{{ route('homeworks.grade',$s) }}" class="row g-2 mb-2">@csrf
            <div class="col-3"><select name="grade" class="form-select" required><option value="">Оценка</option>@foreach([5,4,3,2] as $g)<option value="{{ $g }}" @selected($s->grade==$g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-6"><input name="teacher_comment" class="form-control" value="{{ $s->teacher_comment }}" placeholder="Комментарий"></div>
            <div class="col-3"><button class="btn btn-success w-100">Оценить</button></div>
        </form>
        <form method="POST" action="{{ route('homeworks.return',$s) }}" class="row g-2">@csrf
            <div class="col-9"><input name="teacher_comment" class="form-control" required placeholder="Что нужно исправить"></div>
            <div class="col-3"><button class="btn btn-outline-danger w-100">На доработку</button></div>
        </form>
        @else <span class="text-muted">Работа не отправлена</span> @endif
    </td>
</tr>
@endforeach
</tbody></table>
</div></div></div>
@endsection

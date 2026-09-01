@extends('layout')
@section('title','Домашние задания')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Домашние задания</h1><div class="text-muted">Создание заданий и проверка работ студентов</div></div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card stat-card"><div class="card-body">
            <h5 class="card-title">Новое задание</h5>
            <form method="POST" action="{{ route('homeworks.store') }}">@csrf
                <div class="mb-3"><label class="form-label">Группа</label><select name="group_id" class="form-select" required>@foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">Дисциплина</label><select name="subject_id" class="form-select" required>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">Название</label><input name="title" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Описание</label><textarea name="description" class="form-control" rows="5"></textarea></div>
                <div class="mb-3"><label class="form-label">Срок сдачи</label><input type="datetime-local" name="due_at" class="form-control"></div>
                <button class="btn btn-primary w-100">Создать задание</button>
            </form>
        </div></div>
    </div>
    <div class="col-xl-8">
        <div class="card stat-card"><div class="card-body p-0">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th class="ps-3">Задание</th><th>Группа</th><th>Предмет</th><th>Дедлайн</th><th>Сдано</th><th>Проверено</th><th></th></tr></thead>
                <tbody>
                @forelse($homeworks as $h)
                <tr>
                    <td class="ps-3"><strong>{{ $h->title }}</strong></td><td>{{ $h->group->name }}</td><td>{{ $h->subject->name }}</td>
                    <td>{{ $h->due_at ? $h->due_at->format('d.m.Y H:i') : '—' }}</td>
                    <td>{{ $h->submissions_count }}</td><td>{{ $h->graded_count }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('homeworks.show',$h) }}">Открыть</a></td>
                </tr>
                @empty<tr><td colspan="7" class="text-center text-muted py-5">Домашних заданий пока нет</td></tr>@endforelse
                </tbody>
            </table></div>
        </div></div>
    </div>
</div>
@endsection

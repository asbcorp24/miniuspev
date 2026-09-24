@extends('layout')
@section('title','Преподаватели и нагрузка')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1">Преподаватели и нагрузка</h1><div class="text-muted">Назначение предметов преподавателям по группам</div></div>
    <a class="btn btn-outline-primary" href="{{ route('admin.subjects') }}">Управление предметами</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Новый преподаватель</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.teachers.store') }}">@csrf
                    <div class="mb-3"><label class="form-label">ФИО</label><input class="form-control" name="name" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
                    <div class="mb-3"><label class="form-label">Пароль</label><input class="form-control" type="password" name="password" required></div>
                    <button class="btn btn-primary w-100">Создать преподавателя</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        @forelse($teachers as $teacher)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div><strong>{{ $teacher->name }}</strong><div class="text-muted small">{{ $teacher->email }}</div></div>
                    <span class="badge text-bg-secondary align-self-start">Преподаватель</span>
                </div>

                <div class="mb-3">
                    <strong class="d-block mb-2">Назначенные предметы:</strong>
                    @forelse($assignments->get($teacher->id, collect()) as $assignment)
                        <div class="d-inline-flex align-items-center border rounded px-2 py-1 me-2 mb-2 bg-light">
                            <span>{{ $assignment->group?->name }} · {{ $assignment->subject?->name }}</span>
                            <form method="POST" action="{{ route('admin.teachers.unassign',[$teacher,$assignment]) }}" class="ms-2" onsubmit="return confirm('Удалить это назначение?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-link text-danger p-0 text-decoration-none" title="Удалить">×</button>
                            </form>
                        </div>
                    @empty
                        <span class="text-muted">Предметы и группы пока не назначены.</span>
                    @endforelse
                </div>

                <form class="row g-2" method="POST" action="{{ route('admin.teachers.assign',$teacher) }}">@csrf
                    <div class="col-md-5"><select class="form-select" name="group_id" required><option value="">Группа</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></div>
                    <div class="col-md-5"><select class="form-select" name="subject_id" required><option value="">Предмет</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Назначить</button></div>
                </form>
            </div>
        </div>
        @empty
        <div class="alert alert-light border">Преподавателей пока нет.</div>
        @endforelse
    </div>
</div>
@endsection

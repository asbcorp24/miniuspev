@extends('layout')
@section('title','Преподаватели')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Преподаватели</h1><div class="text-muted">Учетные записи и назначение групп/дисциплин</div></div>
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
                    <button class="btn btn-primary w-100">Создать</button>
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
                <div class="small mb-3">
                    <strong>Назначения:</strong>
                    @forelse($teacher->groups as $group)
                        @php($subject = $subjects->firstWhere('id', $group->pivot->subject_id))
                        <span class="badge text-bg-light border me-1">{{ $group->name }} · {{ $subject?->name ?? 'предмет' }}</span>
                    @empty
                        <span class="text-muted">нет</span>
                    @endforelse
                </div>
                <form class="row g-2" method="POST" action="{{ route('admin.teachers.assign',$teacher) }}">@csrf
                    <div class="col-md-5"><select class="form-select" name="group_id" required><option value="">Группа</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></div>
                    <div class="col-md-5"><select class="form-select" name="subject_id" required><option value="">Дисциплина</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></div>
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

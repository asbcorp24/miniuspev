@extends('layout')
@section('title', 'Сводка — MiniUspev')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Успеваемость студентов</h1>
        <div class="text-muted">Сводная информация преподавателя</div>
    </div>
    <a href="{{ route('journal') }}" class="btn btn-primary">Открыть журнал</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Студентов</div><div class="display-6 fw-semibold">{{ $studentsCount }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Групп</div><div class="display-6 fw-semibold">{{ $groups->count() }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Средний балл</div><div class="display-6 fw-semibold">{{ $averageGrade ?? '—' }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Прогулы</div><div class="display-6 fw-semibold">{{ $absencePercent }}%</div></div></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card stat-card">
            <div class="card-header bg-white fw-semibold">Последние занятия</div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Дата</th><th>Группа</th><th>Дисциплина</th><th>Тема</th></tr></thead>
                    <tbody>
                    @forelse($recentLessons as $lesson)
                        <tr>
                            <td>{{ $lesson->lesson_date->format('d.m.Y') }}</td>
                            <td>{{ $lesson->group->name }}</td>
                            <td>{{ $lesson->subject->name }}</td>
                            <td>{{ $lesson->topic ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Занятий пока нет.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card stat-card mb-4">
            <div class="card-header bg-white fw-semibold">Группы</div>
            <div class="list-group list-group-flush">
                @forelse($groups as $group)
                    <a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('journal', ['group_id'=>$group->id]) }}">
                        <span>{{ $group->name }}</span><span class="badge text-bg-secondary">{{ $group->students_count }}</span>
                    </a>
                @empty
                    <div class="p-3 text-muted">Добавьте первую группу.</div>
                @endforelse
            </div>
        </div>
        <div class="card stat-card">
            <div class="card-header bg-white fw-semibold">Дисциплины</div>
            <div class="card-body">
                @forelse($subjects as $subject)<span class="badge text-bg-light border me-1 mb-1">{{ $subject->name }}</span>@empty<span class="text-muted">Нет дисциплин</span>@endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-4">
        <div class="card stat-card"><div class="card-header bg-white fw-semibold">Добавить группу</div><div class="card-body">
            <form method="post" action="{{ route('groups.store') }}">@csrf
                <input class="form-control mb-2" name="name" placeholder="Например, ССА-21" required>
                <input class="form-control mb-2" name="course" type="number" min="1" max="6" placeholder="Курс">
                <input class="form-control mb-3" name="speciality" placeholder="Специальность">
                <button class="btn btn-outline-primary w-100">Добавить</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card stat-card"><div class="card-header bg-white fw-semibold">Добавить студента</div><div class="card-body">
            <form method="post" action="{{ route('students.store') }}">@csrf
                <select class="form-select mb-2" name="group_id" required><option value="">Группа</option>@foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select>
                <div class="row g-2"><div class="col-6"><input class="form-control" name="last_name" placeholder="Фамилия" required></div><div class="col-6"><input class="form-control" name="first_name" placeholder="Имя" required></div></div>
                <input class="form-control mt-2" name="middle_name" placeholder="Отчество">
                <input class="form-control my-2" name="student_number" placeholder="№ студенческого">
                <button class="btn btn-outline-primary w-100">Добавить</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card stat-card"><div class="card-header bg-white fw-semibold">Добавить дисциплину</div><div class="card-body">
            <form method="post" action="{{ route('subjects.store') }}">@csrf
                <input class="form-control mb-2" name="name" placeholder="Название дисциплины" required>
                <input class="form-control mb-3" name="code" placeholder="Код (необязательно)">
                <button class="btn btn-outline-primary w-100">Добавить</button>
            </form>
        </div></div>
    </div>
</div>
@endsection

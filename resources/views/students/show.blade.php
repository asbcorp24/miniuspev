@extends('layout')
@section('title', $student->full_name)
@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $student->full_name }}</h1>
        <div class="text-muted">Группа: {{ $student->group->name }} @if($student->student_number) · № {{ $student->student_number }} @endif</div>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Назад</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="text-muted">Средний балл</div><div class="display-6">{{ $student->averageGrade() ?? '—' }}</div></div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="text-muted">Посещаемость</div><div class="display-6">{{ $student->attendancePercent() ?? '—' }}@if($student->attendancePercent() !== null)%@endif</div></div></div></div>
    <div class="col-md-4"><div class="card stat-card"><div class="card-body"><div class="text-muted">Двоек</div><div class="display-6">{{ $student->records->where('grade',2)->count() }}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white"><strong>Успеваемость по дисциплинам</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Дисциплина</th><th>Средний балл</th><th>Посещаемость</th><th>Прогулы</th><th>Задолженности</th></tr></thead>
            <tbody>
            @forelse($stats as $row)
                <tr class="{{ $row['debts'] > 0 ? 'table-warning' : '' }}">
                    <td>{{ $row['subject']->name }}</td>
                    <td>{{ $row['average'] ?? '—' }}</td>
                    <td>{{ $row['attendance'] !== null ? $row['attendance'].'%' : '—' }}</td>
                    <td>{{ $row['absences'] }}</td>
                    <td>{{ $row['debts'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Пока нет данных по занятиям.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

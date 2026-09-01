@extends('layout')
@section('title','Мой кабинет')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Личный кабинет студента</h1>
        <div class="text-muted">{{ $student->full_name }} · {{ $student->group?->name }}</div>
    </div>
    <div class="d-flex gap-2 align-items-end">
        @if($periods->count())
        <form method="GET" action="{{ route('student.dashboard') }}">
            <label class="form-label small mb-1">Учебный период</label>
            <select name="period_id" class="form-select" onchange="this.form.submit()">
                @foreach($periods as $p)<option value="{{ $p->id }}" @selected($period?->id === $p->id)>{{ $p->label }}{{ $p->active ? ' · активный' : '' }}</option>@endforeach
            </select>
        </form>
        @endif
        <a href="{{ route('schedule.index') }}" class="btn btn-outline-primary">Расписание</a>
        <a href="{{ route('homeworks.index') }}" class="btn btn-primary">Домашние задания</a>
    </div>
</div>

@if($period)<div class="alert alert-light border py-2 mb-4"><strong>{{ $period->label }}</strong> · показатели ниже рассчитаны только за выбранный семестр.</div>@endif

<div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Сегодня</strong><a href="{{ route('schedule.index',['period_id'=>$period?->id]) }}" class="small">Открыть календарь</a></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Время</th><th>Дисциплина</th><th>Преподаватель</th><th>Аудитория</th><th>Тип</th></tr></thead><tbody>@forelse($todaySchedule as $e)<tr><td><strong>{{ substr($e->starts_at,0,5) }}–{{ substr($e->ends_at,0,5) }}</strong></td><td>{{ $e->subject?->name }}</td><td>{{ $e->teacher?->name ?: '—' }}</td><td>{{ $e->room ?: '—' }}</td><td>{{ $e->lesson_type ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-3">Сегодня занятий по расписанию нет.</td></tr>@endforelse</tbody></table></div></div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Взвешенный средний</div><div class="display-6 fw-semibold">{{ $averageGrade ?? '—' }}</div><div class="small text-muted">С учетом веса работ</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Посещаемость</div><div class="display-6 fw-semibold">{{ $attendancePercent !== null ? $attendancePercent.'%' : '—' }}</div><div class="small text-muted">За выбранный семестр</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Прогулы</div><div class="display-6 fw-semibold">{{ $absenceCount }}</div><div class="small text-muted">Неуважительные пропуски</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Задолженности</div><div class="display-6 fw-semibold {{ $debtCount ? 'text-danger' : 'text-success' }}">{{ $debtCount }}</div><div class="small text-muted">Оценки «2»</div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><strong>Успеваемость по дисциплинам</strong></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Дисциплина</th><th>Взвешенный</th><th>Журнал</th><th>ДЗ</th><th>Итог</th><th>Посещ.</th><th>Долги</th></tr></thead>
                    <tbody>
                    @forelse($subjectStats as $stat)
                        <tr>
                            <td class="fw-semibold">{{ $stat['subject']->name }}</td>
                            <td><span class="badge {{ $stat['average'] !== null && $stat['average'] < 3 ? 'text-bg-danger' : 'text-bg-primary' }}">{{ $stat['average'] ?? '—' }}</span></td>
                            <td>{{ $stat['journal_average'] ?? '—' }}</td>
                            <td>{{ $stat['homework_average'] ?? '—' }}</td>
                            <td>@if($stat['final_grade'])<span class="badge text-bg-success fs-6">{{ $stat['final_grade'] }}</span>@else<span class="text-muted">не выставлена</span>@endif @if($stat['final_comment'])<div class="small text-muted mt-1">{{ $stat['final_comment'] }}</div>@endif</td>
                            <td>{{ $stat['attendance'] !== null ? $stat['attendance'].'%' : '—' }}</td>
                            <td class="{{ $stat['debts'] ? 'text-danger fw-semibold' : '' }}">{{ $stat['debts'] }}</td>
                        </tr>
                    @empty<tr><td colspan="7" class="text-center text-muted py-4">Пока нет данных по дисциплинам.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-white"><strong>Задолженности</strong></div><div class="card-body p-0">@forelse($debts as $debt)<div class="p-3 border-bottom"><div class="d-flex justify-content-between gap-2"><strong>{{ $debt['title'] }}</strong><span class="badge text-bg-danger">2</span></div><div class="small text-muted">{{ $debt['subject'] }} · {{ $debt['type'] }} @if($debt['date'])· {{ $debt['date'] }}@endif</div></div>@empty<div class="p-4 text-center text-success">Задолженностей нет.</div>@endforelse</div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-white d-flex justify-content-between"><strong>Ближайшие домашние задания</strong><a class="small" href="{{ route('homeworks.index') }}">Все</a></div><div class="card-body p-0">@forelse($upcomingHomeworks as $homework)@php($submission = $homework->my_submission)<div class="p-3 border-bottom"><div class="d-flex justify-content-between gap-3"><div><strong>{{ $homework->title }}</strong><div class="small text-muted">{{ $homework->subject?->name }} @if($homework->workType)· {{ $homework->workType->name }} ×{{ $homework->grade_weight }}@endif</div></div><div class="text-end small">@if($homework->due_at)<div>до {{ $homework->due_at->format('d.m.Y H:i') }}</div>@endif @if($submission)<span class="badge {{ $submission->grade ? 'text-bg-success' : 'text-bg-info' }}">{{ $submission->grade ? 'Оценка '.$submission->grade : 'Сдано' }}</span>@else<span class="badge text-bg-warning">Не сдано</span>@endif</div></div></div>@empty<div class="p-4 text-center text-muted">Ближайших заданий нет.</div>@endforelse</div></div></div>
    <div class="col-xl-6"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-white"><strong>История домашних работ</strong></div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Работа</th><th>Тип/вес</th><th>Статус</th><th>Оценка</th></tr></thead><tbody>@forelse($submissions as $submission)<tr><td><strong>{{ $submission->homework?->title }}</strong><div class="small text-muted">{{ $submission->homework?->subject?->name }}</div></td><td>{{ $submission->homework?->workType?->name ?? 'ДЗ' }} ×{{ $submission->homework?->grade_weight ?? 1 }}</td><td>@if($submission->status==='graded')<span class="badge text-bg-success">Проверено</span>@elseif($submission->status==='returned')<span class="badge text-bg-warning">Доработка</span>@else<span class="badge text-bg-info">На проверке</span>@endif</td><td class="fw-bold">{{ $submission->grade ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">Сданных работ пока нет.</td></tr>@endforelse</tbody></table></div></div></div>
</div>

<div class="card border-0 shadow-sm"><div class="card-header bg-white"><strong>Последние отметки в журнале</strong></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Дата</th><th>Дисциплина</th><th>Тип</th><th>Тема</th><th>Посещение</th><th>Оценка</th></tr></thead><tbody>@forelse($recentRecords as $record)<tr><td>{{ $record->lesson?->lesson_date?->format('d.m.Y') }}</td><td>{{ $record->lesson?->subject?->name }}</td><td>{{ $record->lesson?->workType?->name ?? 'Занятие' }} @if($record->grade)<span class="text-muted small">×{{ $record->lesson?->grade_weight ?? 1 }}</span>@endif</td><td>{{ $record->lesson?->topic ?: '—' }}</td><td>@if($record->attendance==='unmarked')<span class="badge text-bg-secondary">Не отмечено</span>@elseif($record->attendance==='present')<span class="badge text-bg-success">Присутствовал</span>@elseif($record->attendance==='late')<span class="badge text-bg-warning">Опоздал</span>@elseif($record->attendance==='excused')<span class="badge text-bg-info">Уважительно</span>@else<span class="badge text-bg-danger">Прогул</span>@endif</td><td class="fw-bold">{{ $record->grade ?? '—' }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Записей пока нет.</td></tr>@endforelse</tbody></table></div></div>
@endsection

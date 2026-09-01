@extends('layout')
@section('title','Отчеты')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1">Семестровая ведомость</h1><div class="text-muted">Взвешенная успеваемость, итоговые оценки и посещаемость</div></div>
    @if($group && $subject && $period)<a class="btn btn-success" href="{{ route('reports.csv',['group_id'=>$group->id,'subject_id'=>$subject->id,'period_id'=>$period->id]) }}">Выгрузить CSV для Excel</a>@endif
</div>

<form class="row g-2 mb-4" method="GET">
    <div class="col-md-4"><label class="form-label">Семестр</label><select class="form-select" name="period_id">@foreach($periods as $p)<option value="{{ $p->id }}" @selected($period?->id===$p->id)>{{ $p->label }}{{ $p->active?' · активный':'' }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Группа</label><select class="form-select" name="group_id">@foreach($groups as $g)<option value="{{ $g->id }}" @selected($group?->id===$g->id)>{{ $g->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Дисциплина</label><select class="form-select" name="subject_id">@foreach($subjects as $s)<option value="{{ $s->id }}" @selected($subject?->id===$s->id)>{{ $s->name }}</option>@endforeach</select></div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Показать</button></div>
</form>

@if($group && $subject && $period)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between gap-2"><strong>{{ $group->name }} · {{ $subject->name }}</strong><span class="text-muted">{{ $period->label }} · студентов: {{ $rows->count() }}</span></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Студент</th><th>Взвешенный балл</th><th>Итог</th><th>Посещаемость</th><th>Прогулы</th><th>Задолженности</th><th></th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr class="{{ $row['debts'] > 0 ? 'table-warning' : '' }}">
                <td>{{ $row['student']->full_name }}</td>
                <td><strong>{{ $row['average'] ?? '—' }}</strong></td>
                <td>@if($row['final'])<span class="badge text-bg-success fs-6">{{ $row['final'] }}</span>@else<span class="text-muted">не выставлена</span>@endif</td>
                <td>{{ $row['attendance'] !== null ? $row['attendance'].'%' : '—' }}</td>
                <td><span class="badge {{ $row['absences'] ? 'text-bg-danger' : 'text-bg-light' }}">{{ $row['absences'] }}</span></td>
                <td><span class="badge {{ $row['debts'] ? 'text-bg-warning' : 'text-bg-success' }}">{{ $row['debts'] }}</span></td>
                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('students.show',$row['student']) }}">Карточка</a></td>
            </tr>
        @empty<tr><td colspan="7" class="text-center text-muted py-5">Нет данных за выбранный период.</td></tr>@endforelse
        </tbody>
    </table></div>
</div>
@endif
@endsection

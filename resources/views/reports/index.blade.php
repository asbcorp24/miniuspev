@extends('layout')
@section('title','Отчеты')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Сводная ведомость</h1><div class="text-muted">Успеваемость, посещаемость и задолженности по группе</div></div>
    @if($group)<a class="btn btn-success" href="{{ route('reports.csv',['group_id'=>$group->id]) }}">Выгрузить CSV для Excel</a>@endif
</div>

<form class="row g-2 mb-4" method="GET">
    <div class="col-md-5"><select class="form-select" name="group_id" onchange="this.form.submit()">@foreach($groups as $g)<option value="{{ $g->id }}" @selected($group && $group->id===$g->id)>{{ $g->name }}</option>@endforeach</select></div>
</form>

@if($group)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between"><strong>{{ $group->name }}</strong><span class="text-muted">Студентов: {{ $rows->count() }}</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Студент</th><th>Средний балл</th><th>Посещаемость</th><th>Прогулы</th><th>Задолженности</th><th></th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr class="{{ $row['debts'] > 0 ? 'table-warning' : '' }}">
                    <td>{{ $row['student']->full_name }}</td>
                    <td><strong>{{ $row['average'] ?? '—' }}</strong></td>
                    <td>{{ $row['attendance'] !== null ? $row['attendance'].'%' : '—' }}</td>
                    <td><span class="badge {{ $row['absences'] ? 'text-bg-danger' : 'text-bg-light' }}">{{ $row['absences'] }}</span></td>
                    <td><span class="badge {{ $row['debts'] ? 'text-bg-warning' : 'text-bg-success' }}">{{ $row['debts'] }}</span></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('students.show',$row['student']) }}">Карточка</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

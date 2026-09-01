@extends('layout')
@section('title','Учебные периоды')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Учебные периоды и типы работ</h1><div class="text-muted">Семестры, веса оценок и базовые типы контрольных мероприятий</div></div>
</div>
<div class="row g-4">
<div class="col-lg-6"><div class="card border-0 shadow-sm"><div class="card-header bg-white"><strong>Новый семестр</strong></div><div class="card-body">
<form method="POST" action="{{ route('academic.periods.store') }}">@csrf
<div class="row g-2">
<div class="col-md-4"><input class="form-control" name="academic_year" placeholder="2026/2027" required></div>
<div class="col-md-3"><select class="form-select" name="semester"><option value="1">1 семестр</option><option value="2">2 семестр</option></select></div>
<div class="col-md-5 form-check d-flex align-items-center ps-5"><input class="form-check-input me-2" type="checkbox" name="active" value="1" id="active"><label class="form-check-label" for="active">Сделать активным</label></div>
<div class="col-md-6"><input class="form-control" type="date" name="starts_at"></div><div class="col-md-6"><input class="form-control" type="date" name="ends_at"></div>
<div class="col-12"><button class="btn btn-primary w-100">Создать семестр</button></div>
</div></form></div></div>
<div class="card border-0 shadow-sm mt-4"><div class="card-header bg-white"><strong>Периоды</strong></div><div class="list-group list-group-flush">
@foreach($periods as $period)<div class="list-group-item d-flex justify-content-between align-items-center"><div><strong>{{ $period->label }}</strong><div class="small text-muted">{{ $period->starts_at?->format('d.m.Y') ?? '—' }} — {{ $period->ends_at?->format('d.m.Y') ?? '—' }}</div></div>@if($period->active)<span class="badge text-bg-success">Активный</span>@else<form method="POST" action="{{ route('academic.periods.activate',$period) }}">@csrf<button class="btn btn-sm btn-outline-primary">Активировать</button></form>@endif</div>@endforeach
</div></div></div>
<div class="col-lg-6"><div class="card border-0 shadow-sm"><div class="card-header bg-white"><strong>Тип работы</strong></div><div class="card-body">
<form method="POST" action="{{ route('academic.work-types.store') }}">@csrf<div class="row g-2"><div class="col-md-5"><input class="form-control" name="name" placeholder="Контрольная работа" required></div><div class="col-md-3"><input class="form-control" name="code" placeholder="TEST" required></div><div class="col-md-2"><input class="form-control" type="number" min="0.1" max="10" step="0.1" name="default_weight" value="1" required></div><div class="col-md-2"><button class="btn btn-primary w-100">+</button></div></div></form></div></div>
<div class="card border-0 shadow-sm mt-4"><div class="card-header bg-white"><strong>Типы и веса</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Тип</th><th>Код</th><th>Вес</th></tr></thead><tbody>@foreach($workTypes as $type)<tr><td>{{ $type->name }}</td><td><code>{{ $type->code }}</code></td><td>{{ number_format($type->default_weight,1,',','') }}</td></tr>@endforeach</tbody></table></div></div></div>
</div>
@endsection

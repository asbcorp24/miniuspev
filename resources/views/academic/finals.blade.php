@extends('layout')
@section('title','Итоговые оценки')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Итоговые оценки за семестр</h1><div class="text-muted">Взвешенный расчёт + ручное утверждение преподавателем. Все изменения сохраняются в истории.</div></div></div>
<form class="card border-0 shadow-sm mb-4" method="GET"><div class="card-body row g-2">
<div class="col-md-4"><select class="form-select" name="period_id">@foreach($periods as $p)<option value="{{ $p->id }}" @selected($periodId==$p->id)>{{ $p->label }}{{ $p->active?' · активный':'' }}</option>@endforeach</select></div>
<div class="col-md-3"><select class="form-select" name="group_id">@foreach($groups as $g)<option value="{{ $g->id }}" @selected($groupId==$g->id)>{{ $g->name }}</option>@endforeach</select></div>
<div class="col-md-3"><select class="form-select" name="subject_id">@foreach($subjects as $s)<option value="{{ $s->id }}" @selected($subjectId==$s->id)>{{ $s->name }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Показать</button></div>
</div></form>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th class="ps-3">Студент</th><th>Расчётный</th><th>Итог</th><th>Комментарий</th><th>Причина изменения</th><th></th></tr></thead><tbody>
@foreach($rows as $row)<tr><td class="ps-3"><strong>{{ $row['student']->full_name }}</strong></td><td><span class="badge text-bg-light border fs-6">{{ $row['calculated'] ?? '—' }}</span></td><form method="POST" action="{{ route('academic.finals.set',$row['student']) }}">@csrf<input type="hidden" name="subject_id" value="{{ $subjectId }}"><input type="hidden" name="academic_period_id" value="{{ $periodId }}"><td style="width:110px"><select class="form-select" name="final_grade"><option value="">—</option>@foreach([5,4,3,2] as $grade)<option value="{{ $grade }}" @selected($row['final']?->final_grade==$grade)>{{ $grade }}</option>@endforeach</select></td><td><input class="form-control" name="comment" value="{{ $row['final']?->comment }}" placeholder="Комментарий"></td><td><input class="form-control" name="reason" placeholder="Например: пересдача"></td><td><button class="btn btn-success">Сохранить</button></td></form></tr>@endforeach
</tbody></table></div></div>
@endsection

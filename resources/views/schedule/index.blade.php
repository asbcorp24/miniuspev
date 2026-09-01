@extends('layout')
@section('title','Расписание и календарь')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1">Расписание и календарь</h1><div class="text-muted">Пары по дням недели, занятия, контрольные и дедлайны</div></div>
</div>

<form method="GET" class="card border-0 shadow-sm mb-4"><div class="card-body row g-2 align-items-end">
    <div class="col-md-5"><label class="form-label">Семестр</label><select name="period_id" class="form-select" onchange="this.form.submit()">@foreach($periods as $p)<option value="{{ $p->id }}" @selected($period && $period->id===$p->id)>{{ $p->label }}{{ $p->active?' · активный':'' }}</option>@endforeach</select></div>
</div></form>

@if(auth()->user()->isAdmin())
<div class="card border-0 shadow-sm mb-4"><div class="card-header bg-white"><strong>Добавить пару в недельное расписание</strong></div><div class="card-body">
<form method="POST" action="{{ route('schedule.store') }}" class="row g-2">@csrf
<input type="hidden" name="academic_period_id" value="{{ $period?->id }}">
<div class="col-md-3"><select name="group_id" class="form-select" required><option value="">Группа</option>@foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select></div>
<div class="col-md-3"><select name="subject_id" class="form-select" required><option value="">Дисциплина</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
<div class="col-md-3"><select name="teacher_id" class="form-select"><option value="">Преподаватель</option>@foreach($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
<div class="col-md-3"><select name="weekday" class="form-select" required>@foreach([1=>'Понедельник',2=>'Вторник',3=>'Среда',4=>'Четверг',5=>'Пятница',6=>'Суббота',7=>'Воскресенье'] as $n=>$d)<option value="{{ $n }}">{{ $d }}</option>@endforeach</select></div>
<div class="col-md-2"><input type="time" name="starts_at" class="form-control" required></div>
<div class="col-md-2"><input type="time" name="ends_at" class="form-control" required></div>
<div class="col-md-2"><input name="room" class="form-control" placeholder="Аудитория"></div>
<div class="col-md-3"><input name="lesson_type" class="form-control" placeholder="Лекция / практика / лаб."></div>
<div class="col-md-2"><input name="note" class="form-control" placeholder="Примечание"></div>
<div class="col-md-1"><button class="btn btn-success w-100">+</button></div>
</form></div></div>
@endif

<div class="row g-4 mb-4">
<div class="col-xl-4"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-white"><strong>Сегодня</strong> · {{ now()->format('d.m.Y') }}</div><div class="card-body p-0">
@forelse($todayEntries as $e)<div class="p-3 border-bottom"><div class="d-flex justify-content-between"><strong>{{ substr($e->starts_at,0,5) }}–{{ substr($e->ends_at,0,5) }}</strong><span class="badge text-bg-light border">{{ $e->room ?: 'ауд. —' }}</span></div><div>{{ $e->subject?->name }}</div><div class="small text-muted">{{ $e->group?->name }} @if($e->teacher)· {{ $e->teacher->name }}@endif @if($e->lesson_type)· {{ $e->lesson_type }}@endif</div></div>@empty<div class="p-4 text-muted text-center">На сегодня пар нет.</div>@endforelse
</div></div></div>
<div class="col-xl-8"><div class="card border-0 shadow-sm h-100"><div class="card-header bg-white"><strong>Календарь событий</strong></div><div class="card-body"><div class="d-flex gap-2 align-items-center mb-3"><input type="month" id="calendarMonth" class="form-control" style="max-width:220px" value="{{ now()->format('Y-m') }}"><button class="btn btn-outline-primary" id="loadCalendar">Показать</button></div><div id="calendarEvents" class="list-group"><div class="text-muted">Загрузка…</div></div></div></div></div>
</div>

<div class="card border-0 shadow-sm"><div class="card-header bg-white"><strong>Недельное расписание</strong></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>День</th><th>Время</th><th>Группа</th><th>Дисциплина</th><th>Преподаватель</th><th>Аудитория</th><th>Тип</th>@if(auth()->user()->isAdmin())<th></th>@endif</tr></thead><tbody>
@foreach([1=>'Понедельник',2=>'Вторник',3=>'Среда',4=>'Четверг',5=>'Пятница',6=>'Суббота',7=>'Воскресенье'] as $n=>$day)
@forelse($entries->get($n,collect()) as $e)<tr><td>{{ $day }}</td><td><strong>{{ substr($e->starts_at,0,5) }}–{{ substr($e->ends_at,0,5) }}</strong></td><td>{{ $e->group?->name }}</td><td>{{ $e->subject?->name }}</td><td>{{ $e->teacher?->name ?: '—' }}</td><td>{{ $e->room ?: '—' }}</td><td>{{ $e->lesson_type ?: '—' }}</td>@if(auth()->user()->isAdmin())<td><form method="POST" action="{{ route('schedule.destroy',$e) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Удалить</button></form></td>@endif</tr>@empty
@if($loop->first)<tr><td>{{ $day }}</td><td colspan="7" class="text-muted">Нет пар</td></tr>@endif
@endforelse
@endforeach
</tbody></table></div></div>
@endsection
@push('scripts')
<script>
async function loadCalendar(){
 const month=document.getElementById('calendarMonth').value; if(!month)return;
 const [y,m]=month.split('-').map(Number); const start=`${month}-01`; const last=new Date(y,m,0).getDate(); const end=`${month}-${String(last).padStart(2,'0')}`;
 const r=await fetch(`{{ route('schedule.events') }}?start=${start}&end=${end}`,{headers:{'Accept':'application/json'}}); const data=await r.json(); const box=document.getElementById('calendarEvents');
 if(!data.length){box.innerHTML='<div class="text-muted">Событий за месяц нет.</div>';return;}
 box.innerHTML=data.map(e=>`<div class="list-group-item"><div class="d-flex justify-content-between gap-3"><div><strong>${e.title}</strong><div class="small text-muted">${e.meta||''}</div></div><div class="text-nowrap"><span class="badge ${e.type==='homework'?'text-bg-warning':'text-bg-primary'}">${e.type==='homework'?'ДЗ':'Занятие'}</span> ${e.date}${e.time?' '+e.time:''}</div></div></div>`).join('');
}
document.getElementById('loadCalendar').addEventListener('click',loadCalendar); loadCalendar();
</script>
@endpush

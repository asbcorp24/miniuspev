@extends('layout')
@section('title','Домашние задания')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="h3 mb-1">Домашние задания</h1><div class="text-muted">Создание, проверка и вес работ в семестровом рейтинге</div></div>
    @if($periods->count())<form method="GET"><select name="period_id" class="form-select" onchange="this.form.submit()">@foreach($periods as $p)<option value="{{ $p->id }}" @selected($period?->id===$p->id)>{{ $p->label }}{{ $p->active?' · активный':'' }}</option>@endforeach</select></form>@endif
</div>
<div class="row g-4">
    <div class="col-xl-4"><div class="card stat-card"><div class="card-body"><h5 class="card-title">Новое задание</h5>
        <form method="POST" action="{{ route('homeworks.store') }}">@csrf
            <input type="hidden" name="academic_period_id" value="{{ $period?->id }}">
            <div class="mb-3"><label class="form-label">Группа</label><select name="group_id" class="form-select" required>@foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Дисциплина</label><select name="subject_id" class="form-select" required>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
            <div class="row g-2 mb-3"><div class="col-8"><label class="form-label">Тип работы</label><select name="work_type_id" class="form-select" id="hwWorkType"><option value="">Домашняя работа</option>@foreach($workTypes as $type)<option value="{{ $type->id }}" data-weight="{{ $type->default_weight }}">{{ $type->name }}</option>@endforeach</select></div><div class="col-4"><label class="form-label">Вес</label><input type="number" step="0.1" min="0.1" max="10" name="grade_weight" id="hwWeight" class="form-control" value="1"></div></div>
            <div class="mb-3"><label class="form-label">Название</label><input name="title" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Описание</label><textarea name="description" class="form-control" rows="4"></textarea></div>
            <div class="mb-3"><label class="form-label">Срок сдачи</label><input type="datetime-local" name="due_at" class="form-control"></div>
            <button class="btn btn-primary w-100">Создать задание</button>
        </form>
    </div></div></div>
    <div class="col-xl-8"><div class="card stat-card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th class="ps-3">Задание</th><th>Группа</th><th>Предмет</th><th>Тип / вес</th><th>Дедлайн</th><th>Сдано</th><th>Проверено</th><th></th></tr></thead><tbody>
        @forelse($homeworks as $h)<tr><td class="ps-3"><strong>{{ $h->title }}</strong></td><td>{{ $h->group->name }}</td><td>{{ $h->subject->name }}</td><td>{{ $h->workType?->name ?? 'ДЗ' }} <span class="badge text-bg-light border">×{{ $h->grade_weight }}</span></td><td>{{ $h->due_at?$h->due_at->format('d.m.Y H:i'):'—' }}</td><td>{{ $h->submissions_count }}</td><td>{{ $h->graded_count }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('homeworks.show',$h) }}">Открыть</a></td></tr>
        @empty<tr><td colspan="8" class="text-center text-muted py-5">Домашних заданий за выбранный семестр пока нет.</td></tr>@endforelse
        </tbody></table></div></div></div></div>
</div>
@endsection
@push('scripts')<script>
const hwType=document.getElementById('hwWorkType'),hwWeight=document.getElementById('hwWeight');
if(hwType&&hwWeight)hwType.addEventListener('change',()=>{const o=hwType.options[hwType.selectedIndex];if(o.dataset.weight)hwWeight.value=o.dataset.weight;});
</script>@endpush

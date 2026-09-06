@extends('layout')
@section('title','Редактирование ДЗ')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><a href="{{ route('homeworks.show',$homework) }}" class="text-decoration-none">← Назад к заданию</a><h1 class="h3 mt-2 mb-1">Редактирование задания</h1><div class="text-muted">{{ $homework->title }}</div></div>
</div>
<div class="card stat-card"><div class="card-body">
<form method="POST" action="{{ route('homeworks.update',$homework) }}" enctype="multipart/form-data" class="row g-3">@csrf @method('PUT')
<div class="col-md-6"><label class="form-label">Группа</label><select name="group_id" class="form-select" required>@foreach($groups as $g)<option value="{{ $g->id }}" @selected($homework->group_id==$g->id)>{{ $g->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Дисциплина</label><select name="subject_id" class="form-select" required>@foreach($subjects as $s)<option value="{{ $s->id }}" @selected($homework->subject_id==$s->id)>{{ $s->name }}</option>@endforeach</select></div>
<div class="col-md-8"><label class="form-label">Тип работы</label><select name="work_type_id" class="form-select" id="editWorkType"><option value="">Домашняя работа</option>@foreach($workTypes as $type)<option value="{{ $type->id }}" data-weight="{{ $type->default_weight }}" @selected($homework->work_type_id==$type->id)>{{ $type->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Вес</label><input type="number" step="0.1" min="0.1" max="10" name="grade_weight" id="editWeight" class="form-control" value="{{ $homework->grade_weight }}"></div>
<div class="col-12"><label class="form-label">Название</label><input name="title" class="form-control" value="{{ $homework->title }}" required></div>
<div class="col-12"><label class="form-label">Описание</label><textarea name="description" class="form-control" rows="5">{{ $homework->description }}</textarea></div>
<div class="col-md-6"><label class="form-label">Срок сдачи</label><input type="datetime-local" name="due_at" class="form-control" value="{{ $homework->due_at?->format('Y-m-d\\TH:i') }}"></div>
<div class="col-12"><hr><h5>Добавить материалы</h5></div>
<div class="col-12"><label class="form-label">Новые файлы</label><input type="file" name="materials[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"><div class="form-text">До 10 файлов по 20 МБ.</div></div>
<div class="col-md-6"><label class="form-label">Новые ссылки</label><textarea name="links" class="form-control" rows="4" placeholder="Одна ссылка на строку"></textarea></div>
<div class="col-md-6"><label class="form-label">Новые ссылки на видео</label><textarea name="video_links" class="form-control" rows="4" placeholder="Одна ссылка на строку"></textarea></div>
<div class="col-12"><button class="btn btn-primary">Сохранить изменения</button></div>
</form>

@if($homework->materials->count())
<hr><h5 class="mb-3">Текущие материалы</h5>
<div class="list-group">
@foreach($homework->materials as $material)
<div class="list-group-item d-flex justify-content-between align-items-center gap-3"><div><strong>{{ $material->title }}</strong><div class="small text-muted">{{ $material->type }}</div></div><form method="POST" action="{{ route('homeworks.materials.destroy',$material) }}" onsubmit="return confirm('Удалить этот материал?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Удалить</button></form></div>
@endforeach
</div>
@endif
</div></div>
@endsection
@push('scripts')<script>
const t=document.getElementById('editWorkType'),w=document.getElementById('editWeight'); if(t&&w)t.addEventListener('change',()=>{const o=t.options[t.selectedIndex];if(o.dataset.weight)w.value=o.dataset.weight;});
</script>@endpush

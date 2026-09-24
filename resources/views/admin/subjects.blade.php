@extends('layout')
@section('title','Предметы')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Предметы</h1><div class="text-muted">Отдельный справочник дисциплин системы</div></div>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm"><div class="card-header bg-white"><strong>Новый предмет</strong></div><div class="card-body">
            <form method="POST" action="{{ route('admin.subjects.store') }}">@csrf
                <div class="mb-3"><label class="form-label">Название</label><input name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Код</label><input name="code" class="form-control" placeholder="Например WEB-01"></div>
                <button class="btn btn-primary w-100">Добавить предмет</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Название</th><th>Код</th><th></th></tr></thead><tbody>
            @forelse($subjects as $subject)
            <tr><td><strong>{{ $subject->name }}</strong></td><td>{{ $subject->code ?: '—' }}</td><td class="text-end"><form method="POST" action="{{ route('admin.subjects.destroy',$subject) }}" onsubmit="return confirm('Удалить предмет? Если он уже используется, удаление будет запрещено.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Удалить</button></form></td></tr>
            @empty<tr><td colspan="3" class="text-center text-muted py-4">Предметов пока нет.</td></tr>@endforelse
            </tbody>
        </table></div></div>
    </div>
</div>
@endsection

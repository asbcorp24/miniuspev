@extends('layout')
@section('title','Мои справки')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Справки об отсутствии</h1><div class="text-muted">Загрузите подтверждающий документ и укажите период отсутствия</div></div></div>
<div class="row g-4">
<div class="col-xl-4"><div class="card border-0 shadow-sm"><div class="card-body"><h5>Новая справка</h5><form method="POST" action="{{ route('absence-documents.store') }}" enctype="multipart/form-data">@csrf
<div class="mb-3"><label class="form-label">С</label><input type="date" name="date_from" class="form-control" required></div>
<div class="mb-3"><label class="form-label">По</label><input type="date" name="date_to" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Комментарий</label><textarea name="student_comment" class="form-control" rows="3" placeholder="Причина отсутствия"></textarea></div>
<div class="mb-3"><label class="form-label">Документ</label><input type="file" name="file" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" required><div class="form-text">JPG, PNG, WEBP или PDF, до 10 МБ.</div></div>
<button class="btn btn-primary w-100">Отправить на проверку</button></form></div></div></div>
<div class="col-xl-8"><div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Период</th><th>Файл</th><th>Статус</th><th>Комментарий</th><th>Проверено</th></tr></thead><tbody>
@forelse($documents as $d)<tr><td>{{ $d->date_from->format('d.m.Y') }}–{{ $d->date_to->format('d.m.Y') }}</td><td><a href="{{ route('absence-documents.download',$d) }}">{{ $d->original_name }}</a></td><td>@if($d->status==='approved')<span class="badge text-bg-success">Подтверждена</span>@elseif($d->status==='rejected')<span class="badge text-bg-danger">Отклонена</span>@else<span class="badge text-bg-warning">На проверке</span>@endif</td><td>{{ $d->review_comment ?: $d->student_comment ?: '—' }}</td><td>{{ $d->reviewer?->name ?: '—' }} @if($d->reviewed_at)<div class="small text-muted">{{ $d->reviewed_at->format('d.m.Y H:i') }}</div>@endif</td></tr>
@empty<tr><td colspan="5" class="text-center text-muted py-4">Справок пока нет.</td></tr>@endforelse
</tbody></table></div></div></div>
</div>
@endsection

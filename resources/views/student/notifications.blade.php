@extends('layout')
@section('title','Уведомления')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Уведомления</h1>
        <div class="text-muted">Новые задания, дедлайны, оценки и изменения в журнале</div>
    </div>
    <form method="POST" action="{{ route('student.notifications.read-all') }}">@csrf
        <button class="btn btn-outline-secondary">Отметить все прочитанными</button>
    </form>
</div>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @forelse($notifications as $notification)
            <form method="POST" action="{{ route('student.notifications.read', $notification) }}" class="list-group-item list-group-item-action p-0 border-0">
                @csrf
                <button class="w-100 text-start border-0 bg-transparent p-3 {{ $notification->read_at ? '' : 'fw-semibold' }}">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="mb-1">
                                @if(!$notification->read_at)<span class="badge text-bg-primary me-2">Новое</span>@endif
                                {{ $notification->title }}
                            </div>
                            @if($notification->message)<div class="text-muted {{ $notification->read_at ? '' : 'fw-normal' }}">{{ $notification->message }}</div>@endif
                        </div>
                        <div class="text-muted small text-nowrap">{{ $notification->created_at->format('d.m.Y H:i') }}</div>
                    </div>
                </button>
            </form>
        @empty
            <div class="p-5 text-center text-muted">Уведомлений пока нет.</div>
        @endforelse
    </div>
</div>

<div class="mt-3">{{ $notifications->links() }}</div>
@endsection

@extends('layout')
@section('title','Уведомления преподавателя')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Уведомления преподавателя</h1>
        <div class="text-muted">Сдачи, пересдачи, просрочки и студенты группы риска</div>
    </div>
    <form method="POST" action="{{ route('teacher.notifications.readAll') }}">@csrf<button class="btn btn-outline-secondary">Отметить все прочитанными</button></form>
</div>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @forelse($notifications as $notification)
            <form method="POST" action="{{ route('teacher.notifications.read',$notification) }}" class="m-0">@csrf
                <button type="submit" class="list-group-item list-group-item-action py-3 text-start w-100 border-0 border-bottom {{ $notification->read_at ? '' : 'bg-light' }}">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                @if(!$notification->read_at)<span class="badge text-bg-primary">Новое</span>@endif
                                <strong>{{ $notification->title }}</strong>
                            </div>
                            @if($notification->message)<div class="text-muted">{{ $notification->message }}</div>@endif
                        </div>
                        <div class="text-muted small text-nowrap">{{ $notification->created_at->format('d.m.Y H:i') }}</div>
                    </div>
                </button>
            </form>
        @empty
            <div class="p-4 text-muted">Уведомлений пока нет.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection

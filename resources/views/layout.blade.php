<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#212529">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MiniUspev">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/app-icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/app-icon.svg">
    <title>@yield('title', 'MiniUspev')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --mobile-nav-h: 72px; }
        body { background:#f4f6f9; }
        .navbar-brand { font-weight:700; }
        .stat-card { border:0; box-shadow:0 2px 12px rgba(0,0,0,.06); }
        .journal-table th, .journal-table td { vertical-align:middle; white-space:nowrap; }
        .student-col {
            min-width:240px;
            max-width:240px;
            width:240px;
            position:sticky;
            left:0;
            background:#fff !important;
            z-index:6;
            box-shadow:6px 0 10px -8px rgba(0,0,0,.45);
        }
        .journal-table thead .student-col {
            z-index:8;
            background:#f8f9fa !important;
        }
        .journal-table tbody tr:hover .student-col {
            background:#f8f9fa !important;
        }
        .journal-table .student-col {
            white-space:normal;
            line-height:1.2;
        }
        .lesson-col { min-width:170px; }
        .save-ok { outline:2px solid #198754 !important; }
        .mobile-bottom-nav { display:none; }
        .mobile-nav-icon { font-size:1.2rem; line-height:1; }
        .mobile-nav-label { font-size:.68rem; line-height:1.1; }
        .mobile-nav-link { color:#6c757d; text-decoration:none; min-width:0; }
        .mobile-nav-link.active { color:#0d6efd; }
        .mobile-nav-link .badge { position:absolute; top:1px; right:12px; font-size:.55rem; }
        @media (max-width: 991.98px) {
            body { padding-bottom:calc(var(--mobile-nav-h) + env(safe-area-inset-bottom)); }
            .desktop-navbar { min-height:56px; margin-bottom:1rem !important; }
            .desktop-navbar .navbar-toggler, .desktop-navbar #mainNav { display:none !important; }
            main.container-fluid { padding-left:12px !important; padding-right:12px !important; padding-bottom:1rem !important; }
            .mobile-bottom-nav {
                display:flex;
                position:fixed;
                left:0; right:0; bottom:0;
                height:calc(var(--mobile-nav-h) + env(safe-area-inset-bottom));
                padding:6px 6px env(safe-area-inset-bottom);
                background:rgba(255,255,255,.97);
                backdrop-filter:blur(14px);
                border-top:1px solid rgba(0,0,0,.08);
                box-shadow:0 -4px 18px rgba(0,0,0,.08);
                z-index:1040;
            }
            .mobile-bottom-nav > * { flex:1 1 20%; }
            .mobile-nav-link, .mobile-more-btn {
                height:58px;
                display:flex;
                flex-direction:column;
                align-items:center;
                justify-content:center;
                gap:4px;
                position:relative;
                border:0;
                background:transparent;
            }
            .table-responsive { border-radius:.5rem; }
            .journal-table .student-col {
                min-width:180px;
                width:180px;
                max-width:180px;
                font-size:.82rem;
            }
        }
    </style>
</head>
<body>
@php
    $studentUnread = 0;
    $teacherUnread = 0;
    if (auth()->check() && auth()->user()->isStudent()) {
        \App\Services\StudentNotificationService::syncDeadlineReminders(auth()->user());
        $studentUnread = \App\Models\StudentNotification::where('user_id', auth()->id())->whereNull('read_at')->count();
    }
    if (auth()->check() && auth()->user()->isTeacher()) {
        \App\Services\TeacherNotificationService::syncRiskAlerts(auth()->user());
        $teacherUnread = \App\Models\StudentNotification::where('user_id', auth()->id())->whereNull('read_at')->count();
    }
    $isStudent = auth()->check() && auth()->user()->isStudent();
    $homeRoute = $isStudent ? 'student.dashboard' : 'dashboard';
@endphp

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 desktop-navbar">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand" href="{{ route($homeRoute) }}">MiniUspev</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <div class="navbar-nav me-auto">
                @if($isStudent)
                    <a class="nav-link" href="{{ route('student.dashboard') }}">Мой кабинет</a>
                    <a class="nav-link" href="{{ route('schedule.index') }}">Расписание</a>
                    <a class="nav-link" href="{{ route('lessons.content.index') }}">Уроки и лекции</a>
                    <a class="nav-link" href="{{ route('homeworks.index') }}">Домашние задания</a>
                    <a class="nav-link" href="{{ route('absence-documents.index') }}">Справки</a>
                    @if(auth()->user()->isGroupLeader())
                        <a class="nav-link" href="{{ route('journal') }}">Посещаемость группы</a>
                        <a class="nav-link" href="{{ route('admin.students') }}">Студенты и доступ</a>
                    @endif
                    <a class="nav-link" href="{{ route('student.notifications') }}">Уведомления @if($studentUnread)<span class="badge rounded-pill text-bg-danger">{{ $studentUnread }}</span>@endif</a>
                @else
                    <a class="nav-link" href="{{ route('dashboard') }}">Сводка</a>
                    <a class="nav-link" href="{{ route('schedule.index') }}">Расписание</a>
                    <a class="nav-link" href="{{ route('journal') }}">Журнал</a>
                    <a class="nav-link" href="{{ route('lessons.content.index') }}">Уроки и лекции</a>
                    <a class="nav-link" href="{{ route('homeworks.index') }}">Домашние задания</a>
                    <a class="nav-link" href="{{ route('absence-documents.index') }}">Справки</a>
                    <a class="nav-link" href="{{ route('academic.finals') }}">Итоги</a>
                    <a class="nav-link" href="{{ route('reports') }}">Отчеты</a>
                    @if(auth()->user()?->isTeacher())
                        <a class="nav-link" href="{{ route('teacher.notifications') }}">Уведомления @if($teacherUnread)<span class="badge rounded-pill text-bg-danger">{{ $teacherUnread }}</span>@endif</a>
                    @endif
                    @if(auth()->user()?->isAdmin())
                        <a class="nav-link" href="{{ route('academic.settings') }}">Семестры</a>
                        <a class="nav-link" href="{{ route('admin.teachers') }}">Преподаватели</a>
                        <a class="nav-link" href="{{ route('admin.subjects') }}">Предметы</a>
                        <a class="nav-link" href="{{ route('admin.students') }}">Доступ студентов</a>
                    @endif
                @endif
            </div>
            @auth
            <div class="d-flex align-items-center gap-3 text-white">
                <div class="small text-end"><div>{{ auth()->user()->name }}</div><div class="text-white-50">{{ auth()->user()->isAdmin() ? 'Администратор' : (auth()->user()->isGroupLeader() ? 'Староста группы' : (auth()->user()->isStudent() ? 'Студент' : 'Преподаватель')) }}</div></div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-light btn-sm">Выйти</button></form>
            </div>
            @endauth
        </div>
    </div>
</nav>

<main class="container-fluid px-4 pb-5">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ implode(' ', $errors->all()) }}</div>@endif
    @yield('content')
</main>

@auth
<nav class="mobile-bottom-nav" aria-label="Мобильная навигация">
    @if($isStudent)
        <a class="mobile-nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" href="{{ route('student.dashboard') }}"><span class="mobile-nav-icon">⌂</span><span class="mobile-nav-label">Главная</span></a>
        <a class="mobile-nav-link {{ request()->routeIs('schedule.*') ? 'active' : '' }}" href="{{ route('schedule.index') }}"><span class="mobile-nav-icon">▦</span><span class="mobile-nav-label">Расписание</span></a>
        <a class="mobile-nav-link {{ request()->routeIs('lessons.content.*') ? 'active' : '' }}" href="{{ route('lessons.content.index') }}"><span class="mobile-nav-icon">▤</span><span class="mobile-nav-label">Уроки</span></a>
        <a class="mobile-nav-link {{ request()->routeIs('homeworks.*') ? 'active' : '' }}" href="{{ route('homeworks.index') }}"><span class="mobile-nav-icon">✓</span><span class="mobile-nav-label">ДЗ</span></a>
    @else
        <a class="mobile-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="mobile-nav-icon">⌂</span><span class="mobile-nav-label">Главная</span></a>
        <a class="mobile-nav-link {{ request()->routeIs('journal') ? 'active' : '' }}" href="{{ route('journal') }}"><span class="mobile-nav-icon">▦</span><span class="mobile-nav-label">Журнал</span></a>
        <a class="mobile-nav-link {{ request()->routeIs('lessons.content.*') ? 'active' : '' }}" href="{{ route('lessons.content.index') }}"><span class="mobile-nav-icon">▤</span><span class="mobile-nav-label">Уроки</span></a>
        <a class="mobile-nav-link {{ request()->routeIs('homeworks.*') ? 'active' : '' }}" href="{{ route('homeworks.index') }}"><span class="mobile-nav-icon">✓</span><span class="mobile-nav-label">ДЗ</span></a>
    @endif
    <button class="mobile-more-btn mobile-nav-link" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMoreMenu"><span class="mobile-nav-icon">•••</span><span class="mobile-nav-label">Ещё</span>@if(($isStudent && $studentUnread) || (!$isStudent && $teacherUnread))<span class="badge rounded-pill text-bg-danger">{{ $isStudent ? $studentUnread : $teacherUnread }}</span>@endif</button>
</nav>

<div class="offcanvas offcanvas-bottom h-auto rounded-top-4" tabindex="-1" id="mobileMoreMenu">
    <div class="offcanvas-header border-bottom">
        <div><h5 class="offcanvas-title">{{ auth()->user()->name }}</h5><div class="small text-muted">{{ auth()->user()->isAdmin() ? 'Администратор' : (auth()->user()->isGroupLeader() ? 'Староста группы' : (auth()->user()->isStudent() ? 'Студент' : 'Преподаватель')) }}</div></div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group list-group-flush">
            @if($isStudent)
                <a class="list-group-item list-group-item-action" href="{{ route('absence-documents.index') }}">Справки</a>
                @if(auth()->user()->isGroupLeader())
                    <a class="list-group-item list-group-item-action" href="{{ route('journal') }}">Посещаемость группы</a>
                    <a class="list-group-item list-group-item-action" href="{{ route('admin.students') }}">Студенты и доступ</a>
                @endif
                <a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('student.notifications') }}"><span>Уведомления</span>@if($studentUnread)<span class="badge rounded-pill text-bg-danger">{{ $studentUnread }}</span>@endif</a>
            @else
                <a class="list-group-item list-group-item-action" href="{{ route('schedule.index') }}">Расписание</a>
                <a class="list-group-item list-group-item-action" href="{{ route('absence-documents.index') }}">Справки</a>
                <a class="list-group-item list-group-item-action" href="{{ route('academic.finals') }}">Итоги</a>
                <a class="list-group-item list-group-item-action" href="{{ route('reports') }}">Отчёты</a>
                @if(auth()->user()->isTeacher())
                    <a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('teacher.notifications') }}"><span>Уведомления</span>@if($teacherUnread)<span class="badge rounded-pill text-bg-danger">{{ $teacherUnread }}</span>@endif</a>
                @endif
                @if(auth()->user()->isAdmin())
                    <a class="list-group-item list-group-item-action" href="{{ route('academic.settings') }}">Семестры</a>
                    <a class="list-group-item list-group-item-action" href="{{ route('admin.teachers') }}">Преподаватели</a>
                    <a class="list-group-item list-group-item-action" href="{{ route('admin.subjects') }}">Предметы</a>
                    <a class="list-group-item list-group-item-action" href="{{ route('admin.students') }}">Доступ студентов</a>
                @endif
            @endif
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="btn btn-outline-danger w-100">Выйти</button></form>
    </div>
</div>
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
</script>
@stack('scripts')
</body>
</html>

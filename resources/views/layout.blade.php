<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MiniUspev')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f4f6f9; }
        .navbar-brand { font-weight:700; }
        .stat-card { border:0; box-shadow:0 2px 12px rgba(0,0,0,.06); }
        .journal-table th, .journal-table td { vertical-align:middle; white-space:nowrap; }
        .student-col { min-width:240px; position:sticky; left:0; background:#fff; z-index:2; }
        .lesson-col { min-width:170px; }
        .save-ok { outline:2px solid #198754 !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="{{ route('dashboard') }}">MiniUspev</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="{{ route('dashboard') }}">Сводка</a>
                <a class="nav-link" href="{{ route('journal') }}">Журнал</a>
                <a class="nav-link" href="{{ route('reports') }}">Отчеты</a>
                @if(auth()->user()?->isAdmin())
                    <a class="nav-link" href="{{ route('admin.teachers') }}">Преподаватели</a>
                @endif
            </div>
            @auth
            <div class="d-flex align-items-center gap-3 text-white">
                <div class="small text-end">
                    <div>{{ auth()->user()->name }}</div>
                    <div class="text-white-50">{{ auth()->user()->isAdmin() ? 'Администратор' : 'Преподаватель' }}</div>
                </div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>

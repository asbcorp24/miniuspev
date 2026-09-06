@extends('layout')
@section('title','Доступ студентов')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ auth()->user()->isGroupLeader() ? 'Студенты моей группы' : 'Доступ студентов' }}</h1>
        <div class="text-muted">{{ auth()->user()->isGroupLeader() ? 'Посещаемость, состав группы, логины и пароли' : 'Создание логинов, массовая выдача доступа, старосты и смена паролей' }}</div>
    </div>
    @if(auth()->user()->isAdmin())
    <form method="GET" class="d-flex gap-2">
        <select class="form-select" name="group_id" onchange="this.form.submit()">
            @foreach($groups as $group)
                <option value="{{ $group->id }}" @selected((int)$groupId === $group->id)>{{ $group->name }}</option>
            @endforeach
        </select>
    </form>
    @else
        <span class="badge text-bg-primary fs-6">{{ $groups->first()?->name }}</span>
    @endif
</div>

@if(session('generated_accounts'))
<div class="alert alert-warning border-warning shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Сохраните временные пароли сейчас</strong>
        <button class="btn btn-sm btn-outline-dark" type="button" onclick="copyAccounts()">Копировать всё</button>
    </div>
    <div class="small mb-2">После закрытия страницы пароли в открытом виде больше не показываются.</div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered bg-white mb-0" id="generatedAccounts">
            <thead><tr><th>Студент</th><th>Логин</th><th>Временный пароль</th></tr></thead>
            <tbody>
            @foreach(session('generated_accounts') as $item)
                <tr><td>{{ $item['name'] }}</td><td class="account-email">{{ $item['email'] }}</td><td class="account-password"><code>{{ $item['password'] }}</code></td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><strong>Добавить студента</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('students.store') }}" class="row g-2">@csrf
                    <input type="hidden" name="group_id" value="{{ $groupId }}">
                    <div class="col-md-3"><input class="form-control" name="last_name" placeholder="Фамилия" required></div>
                    <div class="col-md-3"><input class="form-control" name="first_name" placeholder="Имя" required></div>
                    <div class="col-md-3"><input class="form-control" name="middle_name" placeholder="Отчество"></div>
                    <div class="col-md-2"><input class="form-control" name="student_number" placeholder="№ студента"></div>
                    <div class="col-md-1"><button class="btn btn-success w-100">+</button></div>
                </form>
                <div class="form-text mt-2">Староста может добавлять студентов только в свою группу.</div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <strong>Массовое создание доступа</strong>
                    <div class="text-muted small">Логины и временные пароли для студентов без аккаунта.</div>
                </div>
                <form method="POST" action="{{ route('admin.students.bulk') }}" onsubmit="return confirm('Создать учетные записи для всех студентов группы без доступа?')">
                    @csrf
                    <input type="hidden" name="group_id" value="{{ $groupId }}">
                    <button class="btn btn-primary">Сгенерировать доступ всей группе</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Студенты</strong>
        <span class="text-muted small">{{ $students->count() }} чел.</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Студент</th>
                    <th>№ студента</th>
                    <th>Статус доступа</th>
                    <th style="min-width:520px">Управление</th>
                </tr>
            </thead>
            <tbody>
            @forelse($students as $student)
                @php($account = $accounts->get($student->id))
                <tr>
                    <td>
                        <strong>{{ $student->full_name }}</strong>
                        <div class="text-muted small">{{ $student->group?->name }}</div>
                        @if($account?->isGroupLeader())<span class="badge text-bg-info mt-1">Староста группы</span>@endif
                    </td>
                    <td>{{ $student->student_number ?: '—' }}</td>
                    <td>
                        @if($account)
                            <span class="badge text-bg-success">Есть доступ</span>
                            <div class="small mt-1">{{ $account->email }}</div>
                        @else
                            <span class="badge text-bg-secondary">Нет доступа</span>
                        @endif
                    </td>
                    <td>
                        @if(!$account)
                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.students.account',$student) }}">@csrf<input type="hidden" name="auto" value="1"><button class="btn btn-sm btn-primary">Сгенерировать логин и пароль</button></form>
                            <form method="POST" action="{{ route('admin.students.account',$student) }}" class="d-flex gap-2">@csrf
                                <input class="form-control form-control-sm" type="email" name="email" placeholder="Логин / email">
                                <input class="form-control form-control-sm" type="text" name="password" placeholder="Пароль" minlength="6">
                                <button class="btn btn-sm btn-outline-primary">Создать вручную</button>
                            </form>
                        </div>
                        @else
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <form method="POST" action="{{ route('admin.students.password',$account) }}">@csrf<input type="hidden" name="auto" value="1"><button class="btn btn-sm btn-outline-warning">Сгенерировать новый пароль</button></form>
                            <form method="POST" action="{{ route('admin.students.password',$account) }}" class="d-flex gap-2">@csrf
                                <input class="form-control form-control-sm" type="text" name="password" placeholder="Новый пароль" minlength="6" required>
                                <button class="btn btn-sm btn-outline-secondary">Сменить вручную</button>
                            </form>
                            @if(auth()->user()->isAdmin())
                                @if($account->isGroupLeader())
                                <form method="POST" action="{{ route('admin.students.demote-leader',$account) }}" onsubmit="return confirm('Снять роль старосты?')">@csrf<button class="btn btn-sm btn-outline-danger">Снять старосту</button></form>
                                @else
                                <form method="POST" action="{{ route('admin.students.promote-leader',$account) }}" onsubmit="return confirm('Назначить этого студента старостой группы?')">@csrf<button class="btn btn-sm btn-outline-info">Назначить старостой</button></form>
                                @endif
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">В группе пока нет студентов.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyAccounts() {
    const rows = [...document.querySelectorAll('#generatedAccounts tbody tr')];
    const text = rows.map(row => {
        const cells = row.querySelectorAll('td');
        return `${cells[0].innerText}\t${cells[1].innerText}\t${cells[2].innerText}`;
    }).join('\n');
    navigator.clipboard.writeText(text).then(() => alert('Логины и пароли скопированы.'));
}
</script>
@endpush

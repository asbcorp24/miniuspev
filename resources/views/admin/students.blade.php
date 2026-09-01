@extends('layout')
@section('title','Доступ студентов')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Доступ студентов</h1>
        <div class="text-muted">Создание логинов, массовая выдача доступа и смена паролей</div>
    </div>
    <form method="GET" class="d-flex gap-2">
        <select class="form-select" name="group_id" onchange="this.form.submit()">
            @foreach($groups as $group)
                <option value="{{ $group->id }}" @selected((int)$groupId === $group->id)>{{ $group->name }}</option>
            @endforeach
        </select>
    </form>
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

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <strong>Массовое создание</strong>
            <div class="text-muted small">Будут созданы аккаунты только для активных студентов, у которых доступа еще нет.</div>
        </div>
        <form method="POST" action="{{ route('admin.students.bulk') }}" onsubmit="return confirm('Создать учетные записи для всех студентов группы без доступа?')">
            @csrf
            <input type="hidden" name="group_id" value="{{ $groupId }}">
            <button class="btn btn-primary">Создать доступ всей группе</button>
        </form>
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
                    <th style="min-width:420px">Управление</th>
                </tr>
            </thead>
            <tbody>
            @forelse($students as $student)
                @php($account = $accounts->get($student->id))
                <tr>
                    <td>
                        <strong>{{ $student->full_name }}</strong>
                        <div class="text-muted small">{{ $student->group?->name }}</div>
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
                        <form method="POST" action="{{ route('admin.students.account',$student) }}" class="row g-2">
                            @csrf
                            <div class="col-md-7"><input class="form-control form-control-sm" type="email" name="email" placeholder="student@example.com" required></div>
                            <div class="col-md-3"><input class="form-control form-control-sm" type="text" name="password" placeholder="Пароль" minlength="6" required></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Создать</button></div>
                        </form>
                        @else
                        <form method="POST" action="{{ route('admin.students.password',$account) }}" class="row g-2" onsubmit="return confirm('Изменить пароль этого студента?')">
                            @csrf
                            <div class="col-md-8"><input class="form-control form-control-sm" type="text" name="password" placeholder="Новый пароль" minlength="6" required></div>
                            <div class="col-md-4"><button class="btn btn-sm btn-outline-warning w-100">Сменить пароль</button></div>
                        </form>
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

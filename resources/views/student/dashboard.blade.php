@extends('layout')
@section('title','Мой кабинет')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Личный кабинет студента</h1>
        <div class="text-muted">{{ $student->full_name }} · {{ $student->group?->name }}</div>
    </div>
    <a href="{{ route('homeworks.index') }}" class="btn btn-primary">Домашние задания</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Средний балл</div><div class="display-6 fw-semibold">{{ $averageGrade ?? '—' }}</div><div class="small text-muted">Журнал + домашние задания</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Посещаемость</div><div class="display-6 fw-semibold">{{ $attendancePercent !== null ? $attendancePercent.'%' : '—' }}</div><div class="small text-muted">С учетом присутствия и опозданий</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Прогулы</div><div class="display-6 fw-semibold">{{ $absenceCount }}</div><div class="small text-muted">Неуважительные пропуски</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="card-body"><div class="text-muted small">Задолженности</div><div class="display-6 fw-semibold {{ $debtCount ? 'text-danger' : 'text-success' }}">{{ $debtCount }}</div><div class="small text-muted">Оценки «2»</div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Успеваемость по дисциплинам</strong><span class="text-muted small">Общий средний включает ДЗ</span></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Дисциплина</th><th>Средний</th><th>В журнале</th><th>За ДЗ</th><th>Посещаемость</th><th>Прогулы</th><th>Долги</th></tr></thead>
                    <tbody>
                    @forelse($subjectStats as $stat)
                        <tr>
                            <td class="fw-semibold">{{ $stat['subject']->name }}</td>
                            <td><span class="badge {{ $stat['average'] !== null && $stat['average'] < 3 ? 'text-bg-danger' : 'text-bg-primary' }}">{{ $stat['average'] ?? '—' }}</span></td>
                            <td>{{ $stat['journal_average'] ?? '—' }}</td>
                            <td>{{ $stat['homework_average'] ?? '—' }}</td>
                            <td>{{ $stat['attendance'] !== null ? $stat['attendance'].'%' : '—' }}</td>
                            <td>{{ $stat['absences'] }}</td>
                            <td class="{{ $stat['debts'] ? 'text-danger fw-semibold' : '' }}">{{ $stat['debts'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Пока нет данных по дисциплинам.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><strong>Задолженности</strong></div>
            <div class="card-body p-0">
                @forelse($debts as $debt)
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between gap-2"><strong>{{ $debt['title'] }}</strong><span class="badge text-bg-danger">2</span></div>
                        <div class="small text-muted">{{ $debt['subject'] }} · {{ $debt['type'] }} @if($debt['date'])· {{ $debt['date'] }}@endif</div>
                    </div>
                @empty
                    <div class="p-4 text-center text-success">Задолженностей нет.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Ближайшие домашние задания</strong><a class="small" href="{{ route('homeworks.index') }}">Все задания</a></div>
            <div class="card-body p-0">
                @forelse($upcomingHomeworks as $homework)
                    @php($submission = $homework->my_submission)
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between gap-3">
                            <div><a class="fw-semibold text-decoration-none" href="{{ route('homeworks.index') }}">{{ $homework->title }}</a><div class="small text-muted">{{ $homework->subject?->name }}</div></div>
                            <div class="text-end small">
                                @if($homework->due_at)<div class="{{ $homework->due_at->isToday() ? 'text-warning fw-semibold' : '' }}">до {{ $homework->due_at->format('d.m.Y H:i') }}</div>@else<div class="text-muted">без срока</div>@endif
                                @if($submission)
                                    <span class="badge {{ $submission->grade ? 'text-bg-success' : 'text-bg-info' }}">{{ $submission->grade ? 'Оценка '.$submission->grade : 'Сдано' }}</span>
                                @else
                                    <span class="badge text-bg-warning">Не сдано</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">Ближайших заданий нет.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><strong>История сдачи домашних работ</strong></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Работа</th><th>Сдано</th><th>Статус</th><th>Оценка</th></tr></thead>
                    <tbody>
                    @forelse($submissions as $submission)
                        <tr>
                            <td><div class="fw-semibold">{{ $submission->homework?->title }}</div><div class="small text-muted">{{ $submission->homework?->subject?->name }}</div></td>
                            <td>{{ $submission->submitted_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>
                                @if($submission->status === 'graded')<span class="badge text-bg-success">Проверено</span>
                                @elseif($submission->status === 'returned')<span class="badge text-bg-warning">На доработку</span>
                                @else<span class="badge text-bg-info">На проверке</span>@endif
                            </td>
                            <td class="fw-bold">{{ $submission->grade ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Сданных работ пока нет.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white"><strong>Последние отметки в журнале</strong></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Дата</th><th>Дисциплина</th><th>Тема</th><th>Посещение</th><th>Оценка</th><th>Комментарий</th></tr></thead>
            <tbody>
            @forelse($recentRecords as $record)
                <tr>
                    <td>{{ $record->lesson?->lesson_date?->format('d.m.Y') }}</td>
                    <td>{{ $record->lesson?->subject?->name }}</td>
                    <td>{{ $record->lesson?->topic ?: '—' }}</td>
                    <td>
                        @if($record->attendance === 'present')<span class="badge text-bg-success">Присутствовал</span>
                        @elseif($record->attendance === 'late')<span class="badge text-bg-warning">Опоздал</span>
                        @elseif($record->attendance === 'excused')<span class="badge text-bg-info">Уважительно</span>
                        @else<span class="badge text-bg-danger">Прогул</span>@endif
                    </td>
                    <td class="fw-bold">{{ $record->grade ?? '—' }}</td>
                    <td class="text-muted">{{ $record->comment ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Записей журнала пока нет.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

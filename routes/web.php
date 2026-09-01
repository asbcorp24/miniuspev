<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [JournalController::class, 'dashboard'])->name('dashboard');
    Route::get('/journal', [JournalController::class, 'journal'])->name('journal');
    Route::post('/lessons', [JournalController::class, 'createLesson'])->name('lessons.store');
    Route::patch('/records/{record}', [JournalController::class, 'updateRecord'])->name('records.update');
    Route::post('/groups', [JournalController::class, 'storeGroup'])->name('groups.store');
    Route::post('/students', [JournalController::class, 'storeStudent'])->name('students.store');
    Route::post('/subjects', [JournalController::class, 'storeSubject'])->name('subjects.store');

    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/csv', [ReportController::class, 'csv'])->name('reports.csv');

    Route::get('/homeworks', [HomeworkController::class, 'index'])->name('homeworks.index');
    Route::post('/homeworks', [HomeworkController::class, 'store'])->name('homeworks.store');
    Route::get('/homeworks/{homework}', [HomeworkController::class, 'show'])->name('homeworks.show');
    Route::post('/homeworks/{homework}/submit', [HomeworkController::class, 'submit'])->name('homeworks.submit');
    Route::post('/homework-submissions/{submission}/grade', [HomeworkController::class, 'grade'])->name('homeworks.grade');
    Route::get('/homework-files/{file}/download', [HomeworkController::class, 'download'])->name('homeworks.files.download');

    Route::get('/admin/teachers', [AdminController::class, 'teachers'])->name('admin.teachers');
    Route::post('/admin/teachers', [AdminController::class, 'storeTeacher'])->name('admin.teachers.store');
    Route::post('/admin/teachers/{user}/assign', [AdminController::class, 'assign'])->name('admin.teachers.assign');

    Route::get('/admin/students', [AdminController::class, 'students'])->name('admin.students');
    Route::post('/admin/students/{student}/account', [AdminController::class, 'createStudentAccount'])->name('admin.students.account');
    Route::post('/admin/student-accounts/{user}/password', [AdminController::class, 'resetStudentPassword'])->name('admin.students.password');
    Route::post('/admin/students/accounts/bulk', [AdminController::class, 'bulkCreateStudentAccounts'])->name('admin.students.bulk');
});

<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JournalController;
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

    Route::get('/admin/teachers', [AdminController::class, 'teachers'])->name('admin.teachers');
    Route::post('/admin/teachers', [AdminController::class, 'storeTeacher'])->name('admin.teachers.store');
    Route::post('/admin/teachers/{user}/assign', [AdminController::class, 'assign'])->name('admin.teachers.assign');
});

<?php

use App\Http\Controllers\JournalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [JournalController::class, 'dashboard'])->name('dashboard');
Route::get('/journal', [JournalController::class, 'journal'])->name('journal');
Route::post('/lessons', [JournalController::class, 'createLesson'])->name('lessons.store');
Route::patch('/records/{record}', [JournalController::class, 'updateRecord'])->name('records.update');
Route::post('/groups', [JournalController::class, 'storeGroup'])->name('groups.store');
Route::post('/students', [JournalController::class, 'storeStudent'])->name('students.store');
Route::post('/subjects', [JournalController::class, 'storeSubject'])->name('subjects.store');

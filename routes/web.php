<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/evaluation', EvaluationController::class)->name('evaluation');

Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
// "create" must come before "{ticket}", or /tickets/create would be treated as a ticket ID.
Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

// Human review actions
Route::patch('/tickets/{ticket}/category', [TicketController::class, 'correctCategory'])->name('tickets.correct-category');
Route::patch('/tickets/{ticket}/resolve', [TicketController::class, 'resolve'])->name('tickets.resolve');
Route::post('/tickets/{ticket}/reanalyze', [TicketController::class, 'reanalyze'])->name('tickets.reanalyze');

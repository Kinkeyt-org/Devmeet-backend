<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('v1')->group(function () {
    // Route::apiResource('events', EventController::class);
});



Route::get('events', [EventController::class, 'index']);
Route::get('events/{id}', [EventController::class, 'show']);
Route::middleware(['auth:sanctum', 'role:organizer'])->group(function () {
    Route::post('events', [EventController::class, 'store']);
    Route::put('events/{id}', [EventController::class, 'update']);
    Route::delete('events/{id}', [EventController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:attendee'])->group(function () {
    Route::post('event/{id}/book', [TicketController::class, 'store']);
    Route::get('/my-tickets', [TicketController::class, 'index']);
    Route::patch('tickets/{id}/cancel', [TicketController::class, 'update']);
});
require __DIR__.'/auth.php';
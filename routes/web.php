<?php

use App\Http\Controllers\OwnerApprovalController;
use App\Livewire\PublicBooking;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/v/{property}', PublicBooking::class)->name('public.booking');

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/approve/{token}', OwnerApprovalController::class)->name('owner.approve');
    Route::post('/approve/{token}', [OwnerApprovalController::class, 'decide'])->name('owner.approve.decide');
});

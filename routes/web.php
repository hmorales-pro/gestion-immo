<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\OwnerApprovalController;
use App\Livewire\ManageBooking;
use App\Livewire\PublicBooking;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Prospect: book a visit for a property.
Route::get('/v/{property}', PublicBooking::class)->name('public.booking');

Route::middleware('throttle:30,1')->group(function () {
    // Owner: approve / reject (private link).
    Route::get('/approve/{token}', OwnerApprovalController::class)->name('owner.approve');
    Route::post('/approve/{token}', [OwnerApprovalController::class, 'decide'])->name('owner.approve.decide');

    // Prospect: see, reschedule, cancel, complete information (private link).
    Route::get('/rdv/{token}', ManageBooking::class)->name('booking.manage');
    Route::get('/rdv/{token}/visite.ics', [CalendarController::class, 'booking'])->name('booking.ics');
});

// Agent: subscribable calendar feed (Google / Apple / Outlook).
Route::get('/calendar/{token}.ics', [CalendarController::class, 'feed'])
    ->middleware('throttle:60,1')
    ->name('calendar.feed');

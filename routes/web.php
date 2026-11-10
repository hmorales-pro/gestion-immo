<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\OwnerApprovalController;
use App\Http\Controllers\Site\SiteController;
use App\Livewire\ManageBooking;
use App\Livewire\PublicBooking;
use Illuminate\Support\Facades\Route;

// Marketing website (official site only, IMMOBOOKING_WEBSITE=true). Agencies' own
// installations go straight to the admin panel.
if (config('immobooking.website')) {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/demo', [SiteController::class, 'demo'])->name('site.demo');
    Route::get('/guides', [SiteController::class, 'guides'])->name('site.guides');
    Route::get('/guides/{slug}', [SiteController::class, 'guide'])->where('slug', '[a-z0-9-]+')->name('site.guide');
    Route::get('/mentions-legales', [SiteController::class, 'legal'])->name('site.legal');
    Route::get('/confidentialite', [SiteController::class, 'privacy'])->name('site.privacy');
    Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('site.sitemap');
} else {
    Route::redirect('/', '/admin')->name('home');
}

Route::get('/robots.txt', [SiteController::class, 'robots']);

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

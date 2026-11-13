<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\OwnerApprovalController;
use App\Http\Controllers\OwnerReportController;
use App\Http\Controllers\PropertyPosterController;
use App\Http\Controllers\Site\SiteController;
use App\Http\Controllers\Site\ToolsController;
use App\Http\Controllers\VoucherController;
use App\Livewire\ManageBooking;
use App\Livewire\PublicBooking;
use App\Livewire\SignVisitVoucher;
use Illuminate\Support\Facades\Route;

// Marketing website (official site only, IMMOBOOKING_WEBSITE=true). Agencies' own
// installations go straight to the admin panel.
if (config('immobooking.website')) {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('/demo', [SiteController::class, 'demo'])->name('site.demo');
    Route::get('/guides', [SiteController::class, 'guides'])->name('site.guides');
    Route::get('/guides/{slug}', [SiteController::class, 'guide'])->where('slug', '[a-z0-9-]+')->name('site.guide');
    Route::get('/outils', [ToolsController::class, 'index'])->name('site.tools');
    Route::get('/outils/salaire-pour-louer', [ToolsController::class, 'rentSalary'])->name('site.tool.salaire-pour-louer');
    Route::get('/outils/generateur-qr-code', [ToolsController::class, 'qrCode'])->name('site.tool.generateur-qr-code');
    Route::get('/outils/generateur-qr-code/qr-code.svg', [ToolsController::class, 'qrCodeDownload'])
        ->middleware('throttle:60,1')
        ->name('site.tool.generateur-qr-code.download');
    Route::get('/outils/checklist-visite-appartement', [ToolsController::class, 'checklist'])->name('site.tool.checklist-visite-appartement');
    Route::get('/outils/modele-bon-de-visite', [ToolsController::class, 'voucherTemplate'])->name('site.tool.modele-bon-de-visite');
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

    // Owner: follow-up of every visit and the visitors' feedback (private link).
    Route::get('/suivi/{token}', OwnerReportController::class)->name('owner.report');

    // Prospect: see, reschedule, cancel, complete information (private link).
    Route::get('/rdv/{token}', ManageBooking::class)->name('booking.manage');
    Route::get('/rdv/{token}/visite.ics', [CalendarController::class, 'booking'])->name('booking.ics');
});

// Agent-only pages: visit voucher signed on the phone, printable poster with QR code.
Route::middleware('auth')->group(function () {
    Route::get('/bon-de-visite/{booking}', SignVisitVoucher::class)->name('voucher.sign');
    Route::get('/bon-de-visite/{booking}/imprimer', [VoucherController::class, 'print'])->name('voucher.print');
    Route::get('/affiche/{property}', [PropertyPosterController::class, 'poster'])->name('property.poster');
    Route::get('/affiche/{property}/qr-code.svg', [PropertyPosterController::class, 'qr'])->name('property.qr');
});

// Agent: subscribable calendar feed (Google / Apple / Outlook).
Route::get('/calendar/{token}.ics', [CalendarController::class, 'feed'])
    ->middleware('throttle:60,1')
    ->name('calendar.feed');

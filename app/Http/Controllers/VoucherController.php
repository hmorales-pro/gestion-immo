<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function print(Booking $booking): View
    {
        abort_unless(auth()->user()?->can('view', $booking), 404);

        return view('voucher.print', ['booking' => $booking->load(['property.agent', 'slot'])]);
    }
}

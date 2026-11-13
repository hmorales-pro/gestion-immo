<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Support\QrCode;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Printable A4 poster and QR code of a property's booking page (window, sign, flyers). */
class PropertyPosterController extends Controller
{
    public function poster(Property $property): View
    {
        abort_unless(auth()->user()?->can('view', $property), 404);

        return view('poster.property', [
            'property' => $property,
            'qr' => QrCode::svg($property->publicUrl(), 600),
        ]);
    }

    public function qr(Property $property): Response
    {
        abort_unless(auth()->user()?->can('view', $property), 404);

        return response(QrCode::svg($property->publicUrl(), 1000), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-visite-'.str($property->title)->slug()->limit(40, '').'.svg"',
        ]);
    }
}

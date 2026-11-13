<?php

namespace App\Actions\Bookings;

use App\Exceptions\BookingException;
use App\Models\Booking;
use Illuminate\Validation\ValidationException;

/** Stores the visit voucher (bon de visite) signed by the visitor on the agent's phone. */
class SignVoucher
{
    private const MAX_BYTES = 300_000;

    /**
     * @throws BookingException|ValidationException
     */
    public function execute(Booking $booking, string $signerName, string $signature): Booking
    {
        if (! $booking->isConfirmed()) {
            throw new BookingException('Seule une visite confirmée peut faire l\'objet d\'un bon de visite.');
        }

        if ($booking->hasSignedVoucher()) {
            throw new BookingException('Ce bon de visite est déjà signé.');
        }

        $this->ensureIsPng($signature);

        $booking->update([
            'voucher_signer_name' => trim($signerName),
            'voucher_signature' => $signature,
            'voucher_signed_at' => now(),
        ]);

        return $booking;
    }

    private function ensureIsPng(string $dataUrl): void
    {
        $prefix = 'data:image/png;base64,';
        $binary = str_starts_with($dataUrl, $prefix) && strlen($dataUrl) <= self::MAX_BYTES
            ? base64_decode(substr($dataUrl, strlen($prefix)), strict: true)
            : false;

        if ($binary === false || ! str_starts_with($binary, "\x89PNG\r\n\x1a\n") || strlen($binary) < 200) {
            throw ValidationException::withMessages(['signature' => 'La signature est vide ou illisible. Signez dans le cadre.']);
        }
    }
}

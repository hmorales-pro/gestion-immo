<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\EmploymentStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $property_id
 * @property int $property_slot_id
 * @property string $prospect_name
 * @property string $prospect_email
 * @property string $prospect_phone
 * @property EmploymentStatus $employment_status
 * @property string|null $message
 * @property BookingStatus $status
 * @property string $token
 * @property Carbon|null $decided_at
 * @property-read Property $property
 * @property-read PropertySlot $slot
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'property_id',
        'property_slot_id',
        'prospect_name',
        'prospect_email',
        'prospect_phone',
        'employment_status',
        'message',
        'status',
        'token',
        'manage_token',
        'sequence',
        'decided_at',
        'cancellation_reason',
        'information_requested_at',
    ];

    protected $hidden = ['token', 'manage_token'];

    protected $attributes = [
        'status' => 'PENDING',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'employment_status' => EmploymentStatus::class,
            'decided_at' => 'datetime',
            'information_requested_at' => 'datetime',
            'sequence' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            $booking->token ??= static::generateToken();
            $booking->manage_token ??= static::generateToken();
        });
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<PropertySlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(PropertySlot::class, 'property_slot_id');
    }

    public function isPending(): bool
    {
        return $this->status === BookingStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::Confirmed;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /** Owner's private link (approve / reject). */
    public function approvalUrl(): string
    {
        return route('owner.approve', $this->token);
    }

    /** Prospect's private link (see, reschedule, cancel, complete info). */
    public function manageUrl(): string
    {
        return route('booking.manage', $this->manage_token);
    }

    public function icsUrl(): string
    {
        return route('booking.ics', $this->manage_token);
    }

    /** Stable iCalendar UID so every update / cancellation targets the same event. */
    public function calendarUid(): string
    {
        return 'booking-'.$this->id.'@'.parse_url((string) config('app.url'), PHP_URL_HOST);
    }

    /** Routes the prospect's notifications. */
    public function routeNotificationForMail(): string
    {
        return $this->prospect_email;
    }

    public function routeNotificationForSms(): string
    {
        return $this->prospect_phone;
    }
}

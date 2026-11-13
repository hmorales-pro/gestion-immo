<?php

namespace App\Models;

use App\Enums\BookingMode;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $user_id
 * @property string $title
 * @property string $address
 * @property string|null $description
 * @property int $slot_duration
 * @property BookingMode $booking_mode
 * @property string $owner_name
 * @property string $owner_email
 * @property string|null $owner_phone
 * @property string|null $owner_token
 * @property string|null $occupant_name
 * @property string|null $occupant_email
 * @property string|null $occupant_phone
 */
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'title',
        'address',
        'description',
        'slot_duration',
        'booking_mode',
        'owner_name',
        'owner_email',
        'owner_phone',
        'occupant_name',
        'occupant_email',
        'occupant_phone',
    ];

    protected $hidden = ['owner_token'];

    protected function casts(): array
    {
        return [
            'slot_duration' => 'integer',
            'booking_mode' => BookingMode::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<PropertySlot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(PropertySlot::class)->orderBy('start_time');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @param  Builder<Property>  $query
     */
    public function scopeOwnedBy(Builder $query, User|int $user): void
    {
        $query->where('user_id', $user instanceof User ? $user->getKey() : $user);
    }

    protected static function booted(): void
    {
        static::creating(function (Property $property): void {
            $property->owner_token ??= Str::random(64);
        });
    }

    /**
     * @return HasMany<WaitlistEntry, $this>
     */
    public function waitlist(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }

    public function publicUrl(): string
    {
        return route('public.booking', $this);
    }

    /** Owner's private follow-up page: every visit and the visitors' feedback. */
    public function ownerReportUrl(): string
    {
        if (blank($this->owner_token)) {
            $this->forceFill(['owner_token' => Str::random(64)])->save();
        }

        return route('owner.report', $this->owner_token);
    }

    public function hasOccupant(): bool
    {
        return filled($this->occupant_email) || filled($this->occupant_phone);
    }

    /** Anonymous notifiable for the owner (e-mail + SMS). */
    public function ownerRoute(): AnonymousNotifiable
    {
        return Notification::route('mail', $this->owner_email)
            ->route('sms', $this->owner_phone);
    }

    /** Anonymous notifiable for the current occupant, or null when there is none. */
    public function occupantRoute(): ?AnonymousNotifiable
    {
        if (! $this->hasOccupant()) {
            return null;
        }

        $route = new AnonymousNotifiable;
        if (filled($this->occupant_email)) {
            $route->route('mail', $this->occupant_email);
        }
        if (filled($this->occupant_phone)) {
            $route->route('sms', $this->occupant_phone);
        }

        return $route->route('occupant', true);
    }
}

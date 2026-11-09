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
    ];

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

    public function publicUrl(): string
    {
        return route('public.booking', $this);
    }
}

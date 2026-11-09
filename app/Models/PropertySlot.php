<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\PropertySlotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $property_id
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property bool $is_booked
 */
class PropertySlot extends Model
{
    /** @use HasFactory<PropertySlotFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id',
        'start_time',
        'end_time',
        'is_booked',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_booked' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Slots a prospect can still request: in the future, not booked, and not
     * already held by a request awaiting a decision.
     *
     * @param  Builder<PropertySlot>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_booked', false)
            ->where('start_time', '>', now())
            ->whereDoesntHave('bookings', fn (Builder $q) => $q->where('status', BookingStatus::Pending));
    }

    public function isAvailable(): bool
    {
        return ! $this->is_booked
            && $this->start_time->isFuture()
            && ! $this->bookings()->where('status', BookingStatus::Pending)->exists();
    }

    /**
     * @param  Builder<PropertySlot>  $query
     */
    public function scopeOverlapping(Builder $query, Carbon $start, Carbon $end): void
    {
        $query->where('start_time', '<', $end)->where('end_time', '>', $start);
    }

    /** Human readable label, e.g. "samedi 14 novembre 2026, 10:00 – 10:30". */
    public function label(): string
    {
        return $this->start_time->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY, HH:mm')
            .' – '.$this->end_time->format('H:i');
    }
}

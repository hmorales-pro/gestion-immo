<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

/**
 * Someone waiting for a slot of a fully booked property. Deleted once notified.
 *
 * @property int $id
 * @property string $property_id
 * @property string $name
 * @property string $email
 */
class WaitlistEntry extends Model
{
    use Notifiable;

    protected $fillable = ['property_id', 'name', 'email'];

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}

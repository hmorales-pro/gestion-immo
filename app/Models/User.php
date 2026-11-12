<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'calendar_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Every user of this self-hosted instance is an agent of the agency.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * @return HasMany<Property, $this>
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /** Lazily creates the secret used by the agent's calendar feed. */
    public function calendarToken(): string
    {
        // Read the raw attribute: a freshly created model has no calendar_token key yet.
        if (blank($this->attributes['calendar_token'] ?? null)) {
            $this->regenerateCalendarToken();
        }

        return $this->attributes['calendar_token'];
    }

    public function regenerateCalendarToken(): void
    {
        $this->forceFill(['calendar_token' => Str::random(48)])->save();
    }

    public function calendarFeedUrl(): string
    {
        return route('calendar.feed', $this->calendarToken());
    }
}

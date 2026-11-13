<?php

namespace App\Actions\Slots;

use App\Actions\Bookings\NotifyWaitlist;
use App\Models\Property;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Splits a time range (e.g. Saturday 10:00 → 12:00) into slots of the property's
 * slot duration, optionally repeated weekly. Slots that would overlap an existing
 * slot of the property are skipped.
 */
class GenerateSlots
{
    public function __construct(private readonly NotifyWaitlist $notifyWaitlist) {}

    /**
     * @return array{created: int, skipped: int}
     */
    public function execute(Property $property, Carbon $date, string $from, string $to, int $weeks = 1): array
    {
        $duration = max(5, $property->slot_duration);
        $created = 0;
        $skipped = 0;

        if ($weeks < 1 || $weeks > 52) {
            throw new InvalidArgumentException('Weeks must be between 1 and 52.');
        }

        DB::transaction(function () use ($property, $date, $from, $to, $weeks, $duration, &$created, &$skipped): void {
            for ($week = 0; $week < $weeks; $week++) {
                $day = $date->copy()->addWeeks($week);
                $cursor = $day->copy()->setTimeFromTimeString($from);
                $rangeEnd = $day->copy()->setTimeFromTimeString($to);

                if ($rangeEnd->lessThanOrEqualTo($cursor)) {
                    throw new InvalidArgumentException("L'heure de fin doit être après l'heure de début.");
                }

                while ($cursor->copy()->addMinutes($duration)->lessThanOrEqualTo($rangeEnd)) {
                    $start = $cursor->copy();
                    $end = $cursor->copy()->addMinutes($duration);
                    $cursor = $end;

                    if ($start->isPast() || $property->slots()->getQuery()->overlapping($start, $end)->exists()) {
                        $skipped++;

                        continue;
                    }

                    $property->slots()->create(['start_time' => $start, 'end_time' => $end]);
                    $created++;
                }
            }
        });

        if ($created > 0) {
            $this->notifyWaitlist->execute($property);
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}

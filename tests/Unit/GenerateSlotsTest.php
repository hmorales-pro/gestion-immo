<?php

namespace Tests\Unit;

use App\Actions\Slots\GenerateSlots;
use App\Models\Property;
use App\Models\PropertySlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class GenerateSlotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_splits_range_by_slot_duration(): void
    {
        $property = Property::factory()->create(['slot_duration' => 45]);

        $result = app(GenerateSlots::class)->execute($property, today()->addDay(), '10:00', '12:00');

        // 10:00, 10:45 — 11:30 would end after 12:00.
        $this->assertSame(['created' => 2, 'skipped' => 0], $result);
        $this->assertSame(['10:00', '10:45'], $property->slots->map(fn ($s) => $s->start_time->format('H:i'))->all());
    }

    public function test_skips_overlapping_slots(): void
    {
        $property = Property::factory()->create(['slot_duration' => 30]);
        PropertySlot::factory()->for($property)->at(today()->addDay()->setTime(10, 15))->create();

        $result = app(GenerateSlots::class)->execute($property, today()->addDay(), '10:00', '11:00');

        // 10:00–10:30 and 10:30–11:00 both overlap 10:15–10:45.
        $this->assertSame(['created' => 0, 'skipped' => 2], $result);
    }

    public function test_rejects_inverted_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(GenerateSlots::class)->execute(Property::factory()->create(), today()->addDay(), '12:00', '10:00');
    }
}

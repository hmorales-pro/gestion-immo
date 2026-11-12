<?php

namespace App\Services\Calendar;

use Carbon\CarbonInterface;

/**
 * Minimal RFC 5545 writer: enough for invitations (REQUEST / CANCEL) that Google Calendar,
 * Apple Calendar and Outlook understand, and for a subscribable feed (PUBLISH).
 * Times are written in UTC, so no VTIMEZONE block is needed.
 */
class IcsCalendar
{
    public const METHOD_PUBLISH = 'PUBLISH';

    public const METHOD_REQUEST = 'REQUEST';

    public const METHOD_CANCEL = 'CANCEL';

    /** @var list<array<string, mixed>> */
    private array $events = [];

    public function __construct(
        private readonly string $method = self::METHOD_PUBLISH,
        private readonly ?string $name = null,
    ) {}

    /**
     * @param  array{
     *     uid: string,
     *     start: CarbonInterface,
     *     end: CarbonInterface,
     *     summary: string,
     *     location?: string|null,
     *     description?: string|null,
     *     url?: string|null,
     *     sequence?: int,
     *     status?: 'CONFIRMED'|'TENTATIVE'|'CANCELLED',
     *     organizer?: array{name: string, email: string}|null,
     *     attendees?: list<array{name: string, email: string}>,
     *     updated?: CarbonInterface|null,
     * }  $event
     */
    public function addEvent(array $event): static
    {
        $this->events[] = $event;

        return $this;
    }

    public function render(): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//ImmoBooking//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:'.$this->method,
        ];

        if ($this->name) {
            $lines[] = 'X-WR-CALNAME:'.self::escape($this->name);
            $lines[] = 'REFRESH-INTERVAL;VALUE=DURATION:PT15M';
            $lines[] = 'X-PUBLISHED-TTL:PT15M';
        }

        foreach ($this->events as $event) {
            $status = $event['status'] ?? 'CONFIRMED';
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.$event['uid'];
            $lines[] = 'DTSTAMP:'.self::date(($event['updated'] ?? null) ?? now());
            $lines[] = 'DTSTART:'.self::date($event['start']);
            $lines[] = 'DTEND:'.self::date($event['end']);
            $lines[] = 'SEQUENCE:'.(int) ($event['sequence'] ?? 0);
            $lines[] = 'STATUS:'.$status;
            $lines[] = 'SUMMARY:'.self::escape($event['summary']);

            foreach (['location' => 'LOCATION', 'description' => 'DESCRIPTION', 'url' => 'URL'] as $key => $property) {
                if (filled($event[$key] ?? null)) {
                    $lines[] = $property.':'.self::escape($event[$key]);
                }
            }

            if ($organizer = $event['organizer'] ?? null) {
                $lines[] = 'ORGANIZER;CN='.self::param($organizer['name']).':mailto:'.$organizer['email'];
            }

            foreach ($event['attendees'] ?? [] as $attendee) {
                $partstat = $status === 'CONFIRMED' ? 'ACCEPTED' : 'NEEDS-ACTION';
                $lines[] = 'ATTENDEE;CN='.self::param($attendee['name']).';ROLE=REQ-PARTICIPANT;PARTSTAT='.$partstat.':mailto:'.$attendee['email'];
            }

            if ($status !== 'CANCELLED') {
                $lines[] = 'BEGIN:VALARM';
                $lines[] = 'TRIGGER:-PT1H';
                $lines[] = 'ACTION:DISPLAY';
                $lines[] = 'DESCRIPTION:'.self::escape($event['summary']);
                $lines[] = 'END:VALARM';
            }

            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    public function method(): string
    {
        return $this->method;
    }

    public static function date(CarbonInterface $date): string
    {
        return $date->copy()->utc()->format('Ymd\THis\Z');
    }

    public static function escape(string $text): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $text,
        );
    }

    private static function param(string $value): string
    {
        return '"'.str_replace(['"', "\r", "\n"], ["'", ' ', ' '], $value).'"';
    }

    /** Lines longer than 75 octets are folded (RFC 5545 §3.1), without splitting UTF-8 characters. */
    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out = '';
        $current = '';
        foreach (mb_str_split($line) as $char) {
            $limit = $out === '' ? 75 : 74;
            if (strlen($current) + strlen($char) > $limit) {
                $out .= ($out === '' ? '' : "\r\n ").$current;
                $current = '';
            }
            $current .= $char;
        }

        return $out.($out === '' ? '' : "\r\n ").$current;
    }
}

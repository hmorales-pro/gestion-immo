<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS driver
    |--------------------------------------------------------------------------
    | "log" writes messages to the application log (default, no account needed).
    | "brevo", "twilio" and "vonage" send real text messages with the credentials below
    | (Brevo reuses BREVO_API_KEY; the sender is 11 alphanumeric characters max).
    */
    'driver' => env('SMS_DRIVER', 'log'),

    'from' => env('SMS_FROM', 'ImmoBooking'),

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'vonage' => [
        'key' => env('VONAGE_KEY'),
        'secret' => env('VONAGE_SECRET'),
        'from' => env('VONAGE_FROM'),
    ],
];

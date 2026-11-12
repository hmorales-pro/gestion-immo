<?php

namespace App\Services\Sms;

interface SmsSender
{
    /**
     * @param  string  $to  Phone number in E.164 format (e.g. +33612345678).
     */
    public function send(string $to, string $message): void;
}

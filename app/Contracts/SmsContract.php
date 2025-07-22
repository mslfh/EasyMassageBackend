<?php

namespace App\Contracts;

interface SmsContract
{
    public function verifyToken();

    public function sendSms(string $message,array $phone_number,string $schedule_time = null);

    public function getBalance();
}

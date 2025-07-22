<?php

namespace App\Services;

use App\Contracts\SmsContract;

class SmsService
{
    protected $smsRepository;

    public function __construct(SmsContract $smsRepository)
    {
        $this->smsRepository = $smsRepository;
    }

    public function verifyToken()
    {
        return $this->smsRepository->verifyToken();
    }

    public function sendSms(string $message,array $phone_number,string $schedule_time = null)
    {
        //容错
        return $this->smsRepository->sendSms($message, $phone_number,$schedule_time);
    }

    public function getBalance()
    {
        return $this->smsRepository->getBalance();
    }
}

<?php

namespace App\Repositories;

use App\Contracts\SmsContract;
use Illuminate\Support\Facades\Http;
use App\Models\Notification;
class SmsRepository implements SmsContract
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.sms.api_url'); // Ensure this is set in your config/services.php
        $this->apiKey = config('services.sms.api_key'); // Ensure this is set in your config/services.php
    }

    public function verifyToken()
    {
        // TODO: Implement verifyToken() method If needed
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get("{$this->apiUrl}/user/token/verify");

        return $response->json();
    }

    public function sendSms($text, $phone_number, $schedule_time = null)
    {
        $url = "{$this->apiUrl}/send-sms";
        $fields = [
            'sms_text' => $text, // SMS text
            'numbers' => $phone_number, // Numbers array
        ];
        if($schedule_time){
            $fields['schedule_time'] = $schedule_time; // Schedule time
        }
        $headers = [
            "APPKEY: {$this->apiKey}",
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $ch = curl_init(); // Open connection
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Enable SSL verification
        curl_setopt($ch, CURLOPT_CAINFO, storage_path('certificates/cacert.pem')); // Path to the CA certificate file

        // $result = curl_exec($ch);
        $result =  "{\"meta\":{\"code\":200,\"status\":\"SUCCESS\"},\"msg\":\"Queued\",\"data\":{\"messages\":[{\"message_id\":\"5D64C2FD-D68E-96E5-0D34-3ED1A3AD327C\",\"from\":\"61481076130\",\"to\":\"61491928668\",\"body\":\"Hello, this is a test message\",\"date\":\"2025-04-29 14:09:39\",\"custom_string\":\"\",\"direction\":\"out\"}],\"total_numbers\":1,\"success_number\":1,\"credits_used\":1},\"low_sms_alert\":\"Your account credits are low, you have 29.00 credits remaining, please top-up via the platform\"}";

        curl_close($ch);
        return json_decode($result);
    }

    public function getBalance()
    {
        $url = "{$this->apiUrl}/account";
        $fields = [
        ];
        $headers = [
            "APPKEY: {$this->apiKey}",
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        $ch = curl_init(); // Open connection
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Enable SSL verification
        curl_setopt($ch, CURLOPT_CAINFO, storage_path('certificates/cacert.pem')); // Path to the CA certificate file
        $result = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($result, true);
        return $response;
    }

}

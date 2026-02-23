<?php

namespace App\Services\Notification;

use App\Models\SmsLog;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class SmsService
{
    /**
     * Send OTP and log in sms_logs (type = otp / login_otp etc.)
     */
    public function sendOtp(string $phone, string $type, $userName = 'User', $countryCode = '+91')
    {
        // TODO: Change time in production
        $lastSms = SmsLog::otp()
            ->where('created_at', '>=', now()->subSeconds(10))
            ->where(function ($query) use ($phone) {
                $query->where('phone', $phone)
                    ->orWhere('ip_address', request()->ip());
            })
            ->latest('id')
            ->first();

        if ($lastSms) {
            return 'Please wait at least 30 seconds before requesting another OTP.';
        }

        $smsCountPhone = SmsLog::otp()->where('phone', $phone)->whereDate('created_at', today())->count();
        // TODO: Change count in production
        if ($smsCountPhone >= 50) {
            return 'You have reached the OTP request limit (5) for this phone number today.';
        }

        $otp = App::environment('local') ? '123456' : (string) rand(100000, 999999);
        $message = "Hi {$userName}, your code for {$type} is {$otp}. Please do not share this OTP. - " . config('app.name');

        $to = $this->normalizePhone($countryCode, $phone);
        $smsResponse = $this->sendViaProvider($to, $message, $otp);

        SmsLog::create([
            'phone' => $phone,
            'otp' => $otp,
            'message' => $message,
            'country_code' => $countryCode,
            'sms_data' => $smsResponse ? json_encode($smsResponse) : null,
            'ip_address' => request()->ip(),
            'type' => $type,
        ]);

        return true;
    }

    /**
     * Send general SMS (non-OTP) and log in sms_logs (type = sms).
     */
    public function sendSms(string $phone, string $message, string $countryCode = '+91', ?int $userId = null, ?int $adminId = null): array
    {
        $to = $this->normalizePhone($countryCode, $phone);
        $smsResponse = $this->sendViaProvider($to, $message, null);

        SmsLog::create([
            'user_id' => $userId,
            'admin_id' => $adminId,
            'phone' => $phone,
            'message' => $message,
            'country_code' => $countryCode,
            'sms_data' => $smsResponse ? json_encode($smsResponse) : null,
            'ip_address' => request()->ip(),
            'type' => SmsLog::TYPE_SMS,
        ]);

        return $smsResponse ?? ['status' => 'logged', 'phone' => $phone];
    }

    /**
     * Build E.164 number from country code and phone.
     */
    public function normalizePhone(string $countryCode, string $phone): string
    {
        $phone = preg_replace('/[^\d]/', '', $phone);
        $countryCode = preg_replace('/[^\d+]/', '', $countryCode);
        $countryCode = ltrim($countryCode, '0');
        if (str_starts_with($countryCode, '+')) {
            $countryCode = substr($countryCode, 1);
        }
        return '+' . $countryCode . $phone;
    }

    /**
     * Send via configured gateway (Twilio or log).
     * @param string $to E.164 number e.g. +919876543210
     */
    protected function sendViaProvider(string $to, string $message, ?string $otp = null): array
    {
        $driver = config('sms.driver', 'log');

        if ($driver === 'twilio') {
            return $this->sendViaTwilio($to, $message, $otp);
        }

        // log driver or fallback: no real send
        Log::channel('stack')->info('SMS (log driver)', [
            'to' => $to,
            'message' => $message,
            'otp' => $otp,
        ]);
        return [
            'status' => 'logged',
            'phone' => $to,
            'message' => $message,
            'otp' => $otp,
            'sent_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Send SMS using Twilio API.
     */
    protected function sendViaTwilio(string $to, string $message, ?string $otp = null): array
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.sms_from') ?? config('sms.twilio.from');

        if (empty($sid) || empty($token) || empty($from)) {
            Log::warning('Twilio SMS skipped: missing SID, token or from number.');
            return [
                'status' => 'skipped',
                'error' => 'Twilio not configured',
                'phone' => $to,
                'sent_at' => now()->toDateTimeString(),
            ];
        }

        try {
            $client = new TwilioClient($sid, $token);
            $twilioMessage = $client->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);

            return [
                'status' => 'sent',
                'sid' => $twilioMessage->sid,
                'phone' => $to,
                'message' => $message,
                'otp' => $otp,
                'sent_at' => now()->toDateTimeString(),
            ];
        } catch (\Throwable $e) {
            Log::error('Twilio SMS failed: ' . $e->getMessage(), [
                'to' => $to,
                'code' => $e->getCode(),
            ]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'phone' => $to,
                'message' => $message,
                'otp' => $otp,
                'sent_at' => now()->toDateTimeString(),
            ];
        }
    }

    public function verifyOtp(string $phone, string $otp, $maxAge = 5)
    {
        $smsLog = SmsLog::otp()
            ->where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes($maxAge))
            ->latest()
            ->first();

        if (!$smsLog) {
            return 'Invalid OTP. Please enter the correct OTP. Please request a new OTP.';
        }

// OTP mismatch
        if ($smsLog->otp !== $otp) {
            return 'Invalid OTP. Please enter the correct OTP.';
        }

// OTP already used / expired flag
        if ($smsLog->expired) {
            return 'This OTP has expired. Please request a new OTP.';
        }

        // Mark OTP as expired
        $smsLog->expired = 1;
        $smsLog->save();

        return true;
    }

}


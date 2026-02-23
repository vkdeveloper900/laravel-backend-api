<?php

namespace Tests\Unit;

use App\Models\SmsLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsLogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_otp_returns_only_logs_with_otp(): void
    {
        SmsLog::create([
            'phone' => '9876543210',
            'message' => 'OTP msg',
            'otp' => '123456',
            'country_code' => '+91',
            'type' => 'login_otp',
        ]);
        SmsLog::create([
            'phone' => '9876543211',
            'message' => 'General SMS',
            'otp' => null,
            'country_code' => '+91',
            'type' => SmsLog::TYPE_SMS,
        ]);

        $otpLogs = SmsLog::otp()->get();

        $this->assertCount(1, $otpLogs);
        $this->assertNotNull($otpLogs->first()->otp);
    }

    public function test_scope_sms_only_returns_only_sms_type(): void
    {
        SmsLog::create([
            'phone' => '9876543210',
            'message' => 'SMS',
            'country_code' => '+91',
            'type' => SmsLog::TYPE_SMS,
        ]);
        SmsLog::create([
            'phone' => '9876543211',
            'message' => 'OTP',
            'otp' => '123456',
            'country_code' => '+91',
            'type' => 'login_otp',
        ]);

        $smsOnly = SmsLog::smsOnly()->get();

        $this->assertCount(1, $smsOnly);
        $this->assertSame(SmsLog::TYPE_SMS, $smsOnly->first()->type);
    }

    public function test_scope_active_returns_only_non_expired(): void
    {
        SmsLog::create([
            'phone' => '9876543210',
            'message' => 'A',
            'otp' => '111111',
            'country_code' => '+91',
            'expired' => false,
            'type' => 'otp',
        ]);
        SmsLog::create([
            'phone' => '9876543211',
            'message' => 'B',
            'otp' => '222222',
            'country_code' => '+91',
            'expired' => true,
            'type' => 'otp',
        ]);

        $active = SmsLog::active()->get();

        $this->assertCount(1, $active);
        $this->assertFalse($active->first()->expired);
    }

    public function test_full_phone_attribute_concatenates_country_code_and_phone(): void
    {
        $log = new SmsLog([
            'country_code' => '+91',
            'phone' => '9876543210',
        ]);

        $this->assertSame('+919876543210', $log->full_phone);
    }

    public function test_type_constants(): void
    {
        $this->assertSame('otp', SmsLog::TYPE_OTP);
        $this->assertSame('sms', SmsLog::TYPE_SMS);
    }
}

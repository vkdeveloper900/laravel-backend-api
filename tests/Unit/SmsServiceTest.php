<?php

namespace Tests\Unit;

use App\Models\SmsLog;
use App\Services\Notification\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SmsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('sms.driver', 'log');
        $this->service = app(SmsService::class);
    }

    public function test_normalize_phone_builds_e164(): void
    {
        $this->assertSame('+919876543210', $this->service->normalizePhone('+91', '9876543210'));
        $this->assertSame('+919876543210', $this->service->normalizePhone('91', '9876543210'));
        $this->assertSame('+11234567890', $this->service->normalizePhone('+1', '1234567890'));
    }

    public function test_normalize_phone_strips_non_digits(): void
    {
        $this->assertSame('+919876543210', $this->service->normalizePhone('+91', '98-76-54-32-10'));
        $this->assertSame('+919876543210', $this->service->normalizePhone('+91', ' 9876543210 '));
    }

    public function test_send_otp_creates_sms_log_and_returns_true(): void
    {
        $result = $this->service->sendOtp('9876543210', 'login_otp', 'User', '+91');

        $this->assertTrue($result);
        $this->assertDatabaseCount('sms_logs', 1);
        $log = SmsLog::first();
        $this->assertSame('9876543210', $log->phone);
        $this->assertSame('+91', $log->country_code);
        $this->assertSame('login_otp', $log->type);
        $this->assertNotNull($log->otp);
        $this->assertStringContainsString($log->otp, $log->message);
        $this->assertFalse($log->expired);
    }

    public function test_send_otp_rate_limit_returns_message_when_recent_otp_exists(): void
    {
        SmsLog::create([
            'phone' => '9876543210',
            'otp' => '123456',
            'message' => 'Test',
            'country_code' => '+91',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'type' => 'login_otp',
            'created_at' => now(),
        ]);

        $result = $this->service->sendOtp('9876543210', 'login_otp', 'User', '+91');

        $this->assertIsString($result);
        $this->assertStringContainsString('30 seconds', $result);
        $this->assertDatabaseCount('sms_logs', 1);
    }

    public function test_send_sms_creates_sms_log_with_type_sms(): void
    {
        $response = $this->service->sendSms('9876543210', 'Hello world', '+91', null, null);

        $this->assertArrayHasKey('status', $response);
        $this->assertDatabaseCount('sms_logs', 1);
        $log = SmsLog::first();
        $this->assertSame(SmsLog::TYPE_SMS, $log->type);
        $this->assertSame('Hello world', $log->message);
        $this->assertNull($log->otp);
    }

    public function test_send_sms_can_store_user_id_and_admin_id(): void
    {
        $this->service->sendSms('9876543210', 'Alert', '+91', 1, 2);

        $log = SmsLog::first();
        $this->assertSame(1, $log->user_id);
        $this->assertSame(2, $log->admin_id);
    }

    public function test_verify_otp_success_returns_true_and_marks_expired(): void
    {
        SmsLog::create([
            'phone' => '9876543210',
            'otp' => '123456',
            'message' => 'Your OTP is 123456',
            'country_code' => '+91',
            'expired' => 0,
            'type' => 'login_otp',
            'created_at' => now(),
        ]);

        $result = $this->service->verifyOtp('9876543210', '123456', 5);

        $this->assertTrue($result);
        $this->assertTrue(SmsLog::first()->expired);
    }

    public function test_verify_otp_invalid_no_log_returns_error_string(): void
    {
        $result = $this->service->verifyOtp('9876543210', '123456', 5);

        $this->assertIsString($result);
        $this->assertStringContainsString('Invalid OTP', $result);
        $this->assertNotTrue($result);
    }

    public function test_verify_otp_wrong_code_returns_error_string(): void
    {
        SmsLog::create([
            'phone' => '9876543210',
            'otp' => '123456',
            'message' => 'Your OTP',
            'country_code' => '+91',
            'expired' => 0,
            'type' => 'login_otp',
            'created_at' => now(),
        ]);

        $result = $this->service->verifyOtp('9876543210', '999999', 5);

        $this->assertIsString($result);
        $this->assertStringContainsString('Invalid OTP', $result);
    }

    public function test_verify_otp_expired_flag_returns_error_string(): void
    {
        SmsLog::create([
            'phone' => '9876543210',
            'otp' => '123456',
            'message' => 'Your OTP',
            'country_code' => '+91',
            'expired' => true,
            'type' => 'login_otp',
            'created_at' => now(),
        ]);

        $result = $this->service->verifyOtp('9876543210', '123456', 5);

        $this->assertIsString($result);
        $this->assertStringContainsString('expired', $result);
    }

    public function test_verify_otp_old_record_outside_max_age_returns_error(): void
    {
        $log = SmsLog::create([
            'phone' => '9876543210',
            'otp' => '123456',
            'message' => 'Your OTP',
            'country_code' => '+91',
            'expired' => 0,
            'type' => 'login_otp',
        ]);
        $log->created_at = now()->subMinutes(10);
        $log->save();

        $result = $this->service->verifyOtp('9876543210', '123456', 5);

        $this->assertIsString($result);
        $this->assertStringContainsString('Invalid OTP', $result);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    /** Log type: OTP (login, verify, etc.) */
    public const TYPE_OTP = 'otp';

    /** Log type: General SMS (alerts, marketing, etc.) */
    public const TYPE_SMS = 'sms';

    protected $fillable = [
        'user_id',
        'admin_id',
        'country_code',
        'phone',
        'message',
        'otp',
        'expired',
        'sms_data',
        'ip_address',
        'type',
    ];

    protected $casts = [
        'expired' => 'boolean',
    ];

    /** Full phone number (country_code + phone) */
    public function getFullPhoneAttribute(): string
    {
        return $this->country_code . $this->phone;
    }

    /** Only non-expired OTP logs */
    public function scopeActive($query)
    {
        return $query->where('expired', 0);
    }

    /** Only OTP-type logs (has otp stored) */
    public function scopeOtp($query)
    {
        return $query->whereNotNull('otp');
    }

    /** Only general SMS logs (non-OTP) */
    public function scopeSmsOnly($query)
    {
        return $query->where('type', self::TYPE_SMS);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationReceiver extends Model
{
    public const RECIPIENT_USER = 'user';
    public const RECIPIENT_ADMIN = 'admin';

    protected $fillable = [
        'notification_id',
        'recipient_type',
        'recipient_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}

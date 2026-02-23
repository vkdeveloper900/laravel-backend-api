<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public const TYPE_GENERAL = 'general';
    public const TYPE_ORDER = 'order';
    public const TYPE_TEST_RESULT = 'test_result';
    public const TYPE_SYSTEM = 'system';

    protected $fillable = [
        'type',
        'title',
        'body',
        'data',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function receivers()
    {
        return $this->hasMany(NotificationReceiver::class);
    }
}

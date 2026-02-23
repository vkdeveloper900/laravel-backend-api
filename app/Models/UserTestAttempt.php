<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTestAttempt extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'user_package_id',
        'test_id',
        'started_at',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userPackage()
    {
        return $this->belongsTo(UserPackage::class, 'user_package_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function attemptQuestions()
    {
        return $this->hasMany(UserTestAttemptQuestion::class, 'user_test_attempt_id')->orderBy('sequence');
    }

    public function answers()
    {
        return $this->hasMany(UserTestAnswer::class, 'user_test_attempt_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}

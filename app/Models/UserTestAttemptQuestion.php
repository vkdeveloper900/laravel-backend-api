<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTestAttemptQuestion extends Model
{
    protected $fillable = [
        'user_test_attempt_id',
        'question_id',
        'section_id',
        'sequence',
    ];

    public function userTestAttempt()
    {
        return $this->belongsTo(UserTestAttempt::class, 'user_test_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}

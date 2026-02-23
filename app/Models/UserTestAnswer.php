<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTestAnswer extends Model
{
    protected $fillable = [
        'user_test_attempt_id',
        'question_id',
        'question_option_id',
        'answer_value',
        'is_correct',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function userTestAttempt()
    {
        return $this->belongsTo(UserTestAttempt::class, 'user_test_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function questionOption()
    {
        return $this->belongsTo(QuestionOption::class);
    }
}

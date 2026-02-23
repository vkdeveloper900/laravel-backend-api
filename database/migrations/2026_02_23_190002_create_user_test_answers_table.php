<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_test_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_test_attempt_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('question_option_id')->nullable(); // MCQ: selected option
            $table->string('answer_value', 500)->nullable(); // scale / text
            $table->boolean('is_correct')->nullable();
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->foreign('user_test_attempt_id')->references('id')->on('user_test_attempts')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
            $table->foreign('question_option_id')->references('id')->on('question_options')->onDelete('set null');
            $table->unique(['user_test_attempt_id', 'question_id'], 'uta_answer_attempt_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_test_answers');
    }
};

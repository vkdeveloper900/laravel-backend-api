<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_test_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_test_attempt_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedSmallInteger('sequence');
            $table->timestamps();

            $table->foreign('user_test_attempt_id')->references('id')->on('user_test_attempts')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->unique(['user_test_attempt_id', 'question_id'], 'utaq_attempt_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_test_attempt_questions');
    }
};

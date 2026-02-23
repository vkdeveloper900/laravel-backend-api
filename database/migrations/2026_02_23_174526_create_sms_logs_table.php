<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('ip_address')->default('');
            $table->string('country_code')->default('+91');
            $table->string('phone');
            $table->text('message');
            $table->string('otp', 10)->nullable(); // OTP only for type=otp; null for general SMS
            $table->tinyInteger('expired')->default(0); // 1 = OTP used/expired; 0 for SMS
            $table->text('sms_data')->nullable(); // Provider response JSON
            $table->string('type')->default('sms'); // 'otp', 'login_otp', 'sms', 'marketing', etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};

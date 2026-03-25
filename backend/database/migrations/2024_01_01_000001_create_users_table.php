<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->enum('subscription_type', ['none', 'basic', 'best', 'premium'])->default('none');
            $table->timestamp('subscription_expiry')->nullable();
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->string('fcm_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index('subscription_type');
            $table->index('subscription_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

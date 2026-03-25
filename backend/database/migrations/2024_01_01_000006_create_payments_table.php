<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('restrict');
            $table->foreignId('subscription_id')->nullable()->constrained('user_subscriptions')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->enum('currency', ['USDT', 'BTC'])->default('USDT');
            $table->string('wallet_address');
            $table->string('tx_hash')->nullable()->unique();
            $table->enum('status', ['pending', 'confirmed', 'failed', 'expired'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable()->comment('Extra payment data');
            $table->timestamp('expires_at')->nullable()->comment('Payment window expiry');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['tx_hash']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained()->onDelete('cascade');
            $table->string('timeframe', 10);
            $table->enum('signal_type', ['BUY', 'SELL']);
            $table->decimal('entry_price', 18, 8);
            $table->decimal('stop_loss', 18, 8);
            $table->decimal('take_profit', 18, 8);
            $table->unsignedTinyInteger('confidence_score')->default(0)->comment('0-100');
            $table->json('reason')->comment('Detailed indicator breakdown');
            $table->enum('status', ['pending', 'active', 'win', 'loss', 'expired'])->default('active');
            $table->decimal('result_percentage', 8, 4)->nullable()->comment('Profit/loss %');
            $table->decimal('close_price', 18, 8)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();

            $table->index(['trading_pair_id', 'timeframe', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('created_at');
            $table->index('confidence_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};

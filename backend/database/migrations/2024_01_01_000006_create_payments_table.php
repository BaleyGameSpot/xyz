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
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2)->comment('USD equivalent');
            $table->string('crypto_type', 20)->comment('USDT_TRC20, USDT_ERC20, BTC, ETH, BNB');
            $table->string('tx_hash')->nullable()->unique()->comment('Blockchain transaction hash');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->string('reject_reason', 100)->nullable()->comment('Reason code for rejection');
            $table->text('reject_note')->nullable()->comment('Admin note to user on rejection');
            $table->unsignedBigInteger('verified_by')->nullable()->comment('Admin ID who verified');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('package_id');
            $table->index('status');
            $table->index('created_at');
            $table->foreign('verified_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

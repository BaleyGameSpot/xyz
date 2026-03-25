<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->unique();
            $table->string('name', 100);
            $table->enum('type', ['forex', 'crypto']);
            $table->string('exchange', 50)->nullable()->default('binance');
            $table->json('package_access')->comment('Array of package slugs that can access this pair');
            $table->boolean('is_active')->default(true);
            $table->decimal('pip_size', 10, 6)->default(0.0001)->comment('Pip/tick size for precision');
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_pairs');
    }
};

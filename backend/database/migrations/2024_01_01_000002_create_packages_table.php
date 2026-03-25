<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 8, 2);
            $table->decimal('promo_price', 8, 2)->nullable();
            $table->unsignedTinyInteger('pairs_limit')->nullable()->comment('null = unlimited');
            $table->unsignedTinyInteger('daily_signals_limit')->default(10);
            $table->json('timeframes')->comment('Allowed timeframes array');
            $table->text('description')->nullable();
            $table->json('features')->nullable()->comment('Marketing feature list');
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            // null = dana umum (sedekah tanpa campaign)
            $table->foreignId('campaign_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('order_id', 40)->unique();
            $table->string('donor_name', 100);
            $table->boolean('is_anonymous')->default(false);
            $table->string('phone', 20);
            $table->unsignedBigInteger('amount');
            $table->text('prayer')->nullable();
            $table->boolean('prayer_hidden')->default(false);
            $table->string('payment_method', 30)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('snap_token')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('midtrans_payload')->nullable();
            $table->timestamps();

            $table->index('phone');
            $table->index(['campaign_id', 'status', 'paid_at']);
            $table->index(['status', 'paid_at']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};

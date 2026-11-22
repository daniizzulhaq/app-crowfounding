<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            // null = disalurkan dari dana umum
            $table->foreignId('campaign_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('amount');
            $table->date('distributed_at');
            $table->string('location');
            $table->string('recipient');
            $table->longText('body');
            $table->timestamps();

            $table->index(['campaign_id', 'distributed_at']);
            $table->index('distributed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};

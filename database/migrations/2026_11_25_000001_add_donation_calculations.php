<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('donation_type', 30)->default('nominal');
            $table->string('unit_label', 40)->nullable();
            $table->unsignedBigInteger('unit_price')->nullable();
            $table->decimal('zakat_rate', 5, 2)->default(2.50);
            $table->unsignedBigInteger('zakat_nisab')->nullable();
        });
        Schema::table('donations', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->nullable();
            $table->json('calculation')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('donations', fn (Blueprint $table) => $table->dropColumn(['quantity', 'calculation']));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn(['donation_type', 'unit_label', 'unit_price', 'zakat_rate', 'zakat_nisab']));
    }
};

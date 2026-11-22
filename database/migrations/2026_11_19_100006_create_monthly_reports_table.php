<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedBigInteger('opening_balance')->default(0);
            $table->unsignedBigInteger('total_in')->default(0);
            $table->unsignedBigInteger('total_operational')->default(0);
            $table->unsignedBigInteger('total_distributed')->default(0);
            $table->unsignedBigInteger('closing_balance')->default(0);
            $table->unsignedInteger('donation_count')->default(0);
            $table->unsignedTinyInteger('operational_fee_percent')->default(0);
            $table->json('breakdown')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('pdf_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
            $table->index(['status', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};

<?php

namespace Database\Factories;

use App\Models\MonthlyReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyReport>
 */
class MonthlyReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'year' => (int) now()->subMonth()->year,
            'month' => (int) now()->subMonth()->month,
            'opening_balance' => 0,
            'total_in' => 10_000_000,
            'total_operational' => 0,
            'total_distributed' => 4_000_000,
            'closing_balance' => 6_000_000,
            'donation_count' => 120,
            'operational_fee_percent' => 0,
            'breakdown' => [],
            'status' => 'draft',
            'generated_at' => now(),
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published', 'published_at' => now()]);
    }
}

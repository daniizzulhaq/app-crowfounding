<?php

namespace Database\Factories;

use App\Enums\DonationStatus;
use App\Enums\PaymentMethod;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'order_id' => 'AKA-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
            'donor_name' => $this->faker->name(),
            'is_anonymous' => false,
            'phone' => '628'.$this->faker->numerify('##########'),
            'amount' => $this->faker->randomElement([10_000, 25_000, 50_000, 100_000, 250_000]),
            'prayer' => null,
            'prayer_hidden' => false,
            'status' => DonationStatus::Pending,
            'expires_at' => now()->addHour(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => DonationStatus::Paid,
            'paid_at' => now()->subMinutes($this->faker->numberBetween(1, 60 * 24 * 20)),
            'payment_method' => $this->faker->randomElement([PaymentMethod::Qris, PaymentMethod::BankTransfer, PaymentMethod::Gopay]),
        ]);
    }

    public function generalFund(): static
    {
        return $this->state(['campaign_id' => null]);
    }

    public function anonymous(): static
    {
        return $this->state(['is_anonymous' => true]);
    }

    public function withPrayer(?string $prayer = null): static
    {
        return $this->state(fn () => ['prayer' => $prayer ?? $this->faker->sentence(12)]);
    }
}

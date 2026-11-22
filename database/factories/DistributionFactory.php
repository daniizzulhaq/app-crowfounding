<?php

namespace Database\Factories;

use App\Models\Distribution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Distribution>
 *
 * Note: bypasses DistributionLedger — use only for fixtures where totals are set manually.
 */
class DistributionFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim($this->faker->unique()->sentence(6), '.');

        return [
            'campaign_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'amount' => 1_000_000,
            'distributed_at' => now()->subDays($this->faker->numberBetween(0, 20))->toDateString(),
            'location' => $this->faker->city(),
            'recipient' => $this->faker->numberBetween(10, 200).' penerima manfaat',
            'body' => '<p>'.implode('</p><p>', $this->faker->paragraphs(3)).'</p>',
        ];
    }
}

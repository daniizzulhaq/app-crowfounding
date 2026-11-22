<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim($this->faker->unique()->sentence(5), '.');

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => $this->faker->sentence(14),
            'story' => '<p>'.implode('</p><p>', $this->faker->paragraphs(4)).'</p>',
            'target_amount' => $this->faker->numberBetween(10, 500) * 1_000_000,
            'collected_amount' => 0,
            'distributed_amount' => 0,
            'donor_count' => 0,
            'deadline' => now()->addDays($this->faker->numberBetween(10, 90))->toDateString(),
            'status' => CampaignStatus::Active,
            'is_featured' => false,
            'published_at' => now()->subDays($this->faker->numberBetween(1, 30)),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => CampaignStatus::Draft, 'published_at' => null]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function completed(): static
    {
        return $this->state(['status' => CampaignStatus::Completed, 'deadline' => now()->subDay()->toDateString()]);
    }

    public function collected(int $collected, int $donors = 1, int $distributed = 0): static
    {
        return $this->state([
            'collected_amount' => $collected,
            'donor_count' => $donors,
            'distributed_amount' => $distributed,
        ]);
    }
}

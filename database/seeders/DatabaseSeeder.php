<?php

namespace Database\Seeders;

use App\Actions\Reports\GenerateMonthlyReport;
use App\Actions\Reports\PublishMonthlyReport;
use App\Enums\CampaignStatus;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedSettings();

        $category = Category::create([
            'name' => 'Pendidikan',
            'icon' => 'book',
            'sort_order' => 0,
        ]);

        // Start testing with no donations, donors, or distributions.
        // Use models directly so seeding also works with composer install --no-dev.
        $campaign = Campaign::create([
            'category_id' => $category->id,
            'title' => 'Campaign Testing: Bantuan Pendidikan',
            'summary' => 'Contoh campaign untuk menguji alur donasi Alkiroma Amanah dari saldo awal Rp0.',
            'story' => '<p>Campaign contoh ini digunakan untuk menguji alur donasi sebelum website digunakan secara resmi.</p>'
                .'<p>Rencana program: penyediaan perlengkapan sekolah bagi anak yang membutuhkan.</p>'
                .'<p>Dana terkumpul, jumlah donatur, dan penyaluran dimulai dari nol. Tidak ada transaksi donasi contoh yang dibuat.</p>',
            'target_amount' => 1_000_000,
            'deadline' => null,
            'is_featured' => true,
            'status' => CampaignStatus::Active,
        ]);

        Banner::create([
            'title' => $campaign->title,
            'campaign_id' => $campaign->id,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        // One empty report for testing the report page and PDF download.
        $last = now()->subMonthNoOverflow();
        $report = app(GenerateMonthlyReport::class)->handle($last->year, $last->month);
        app(PublishMonthlyReport::class)->handle($report);
    }

    private function seedAdmin(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@alkiromaamanah.test'],
            ['name' => 'Admin Alkiroma Amanah', 'password' => 'password'],
        );
        $admin->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();
    }

    private function seedSettings(): void
    {
        app(Settings::class)->set([
            'foundation_name' => 'Alkiroma Amanah',
            'pub_permit_number' => '123/HUK-PS/PUB/2026 (contoh)',
            'address' => 'Jl. Contoh Amanah No. 1, Jakarta',
            'phone' => '021-0000-0000',
            'whatsapp' => '081200000000',
            'email' => 'halo@alkiromaamanah.test',
            'bank_accounts' => 'BSI 0000000000 a.n. Yayasan Alkiroma Amanah',
            'operational_fee_percent' => 5,
        ]);
    }
}

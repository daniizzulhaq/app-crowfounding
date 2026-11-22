<?php

namespace Database\Seeders;

use App\Actions\Distributions\DistributionLedger;
use App\Actions\Reports\GenerateMonthlyReport;
use App\Actions\Reports\PublishMonthlyReport;
use App\Enums\CampaignStatus;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Donation;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedSettings();

        $categories = collect([
            ['Pendidikan', 'book'], ['Kesehatan', 'heart'], ['Bencana Alam', 'alert'],
            ['Pangan', 'gift'], ['Masjid & Dakwah', 'home'], ['Yatim & Dhuafa', 'users'],
        ])->map(fn ($c, $i) => Category::create(['name' => $c[0], 'icon' => $c[1], 'sort_order' => $i]));

        $campaigns = collect([
            ['Beasiswa santri penghafal Al-Qur\'an', 0, 150_000_000, 45, true],
            ['Operasi katarak gratis untuk lansia dhuafa', 1, 80_000_000, 30, true],
            ['Tanggap darurat banjir: dapur umum & selimut', 2, 120_000_000, 14, true],
            ['Sedekah beras untuk 500 keluarga prasejahtera', 3, 60_000_000, 25, false],
            ['Renovasi atap musala di desa terpencil', 4, 95_000_000, 60, true],
            ['Santunan bulanan anak yatim', 5, 50_000_000, null, false],
            ['Air bersih: sumur bor untuk kampung kekeringan', 2, 75_000_000, 40, false],
            ['Paket gizi untuk balita stunting', 1, 45_000_000, 20, false],
        ])->map(fn ($c) => Campaign::factory()->create([
            'category_id' => $categories[$c[1]]->id,
            'title' => $c[0],
            'slug' => null,
            'summary' => 'Bantu wujudkan program "'.$c[0].'" bersama Alkiroma Amanah. Setiap rupiah tercatat dan dilaporkan.',
            'story' => '<p>Di banyak tempat, saudara-saudara kita masih menunggu uluran tangan. Program <strong>'.e($c[0]).'</strong> hadir untuk menjawab kebutuhan itu secara nyata.</p>'
                .'<h2>Rencana penggunaan dana</h2><ul><li>Kebutuhan utama penerima manfaat</li><li>Distribusi dan pendampingan relawan</li><li>Dokumentasi dan pelaporan terbuka</li></ul>'
                .'<p>Setiap penyaluran akan kami kabarkan lengkap dengan foto, lokasi, dan jumlah penerima. Semoga menjadi amal jariyah bagi kita semua.</p>',
            'target_amount' => $c[2],
            'deadline' => $c[3] ? now()->addDays($c[3])->toDateString() : null,
            'is_featured' => $c[4],
            'status' => CampaignStatus::Active,
        ]));

        Campaign::factory()->draft()->create(['category_id' => $categories[0]->id, 'title' => 'Draf: wakaf Al-Qur\'an', 'slug' => null]);

        $prayers = [
            'Semoga menjadi amal jariyah untuk almarhum ayah kami.',
            'Ya Allah, mudahkan urusan saudara-saudara kami yang sedang diuji.',
            'Semoga anak-anak yatim tumbuh menjadi generasi yang saleh.',
            'Bismillah, semoga berkah dan bermanfaat.',
            'Semoga Allah sembuhkan yang sakit dan lapangkan rezeki kita semua.',
            'Untuk ibu tercinta, semoga Allah lapangkan kuburnya.',
        ];

        // Paid donations, spread over the last ~2 months.
        foreach ($campaigns as $campaign) {
            Donation::factory()->count(rand(12, 28))->paid()->sequence(fn ($s) => [
                'is_anonymous' => $s->index % 4 === 0,
                'prayer' => $s->index % 3 === 0 ? $prayers[$s->index % count($prayers)] : null,
                'paid_at' => now()->subMinutes(rand(10, 60 * 24 * 55)),
            ])->create(['campaign_id' => $campaign->id]);

            Donation::factory()->count(2)->create(['campaign_id' => $campaign->id]); // pending
        }

        Donation::factory()->count(15)->paid()->generalFund()->create();

        // Sync denormalized totals from real paid donations.
        DB::table('campaigns')->update([
            'collected_amount' => DB::raw('(SELECT COALESCE(SUM(amount),0) FROM donations WHERE donations.campaign_id = campaigns.id AND donations.status = \'paid\')'),
            'donor_count' => DB::raw('(SELECT COUNT(*) FROM donations WHERE donations.campaign_id = campaigns.id AND donations.status = \'paid\')'),
        ]);

        // Distributions through the ledger so balances stay valid.
        $ledger = app(DistributionLedger::class);
        foreach ($campaigns->take(5) as $i => $campaign) {
            $campaign->refresh();
            $amount = intdiv($campaign->collected_amount, 2);

            if ($amount > 0) {
                $ledger->create([
                    'title' => 'Penyaluran tahap 1: '.$campaign->title,
                    'distributed_at' => now()->subDays(3 + $i * 6)->toDateString(),
                    'location' => ['Cianjur, Jawa Barat', 'Garut, Jawa Barat', 'Lebak, Banten', 'Bogor, Jawa Barat', 'Sukabumi, Jawa Barat'][$i],
                    'recipient' => [40, 25, 150, 300, 1][$i].' penerima manfaat',
                    'body' => '<p>Alhamdulillah, berkat titipan para donatur, tim Alkiroma Amanah telah menyalurkan bantuan tahap pertama.</p><p>Bantuan diterima langsung oleh penerima manfaat dan didampingi relawan setempat. Terima kasih atas kepercayaan Anda.</p>',
                ], $campaign->id, $amount);
            }
        }

        $general = (int) Donation::query()->paid()->generalFund()->sum('amount');
        if ($general > 0) {
            $ledger->create([
                'title' => 'Bantuan sembako dari dana sedekah umum',
                'distributed_at' => now()->subDays(2)->toDateString(),
                'location' => 'Jakarta Utara',
                'recipient' => '60 keluarga dhuafa',
                'body' => '<p>Dana sedekah umum disalurkan dalam bentuk paket sembako untuk keluarga dhuafa.</p>',
            ], null, intdiv($general, 2));
        }

        foreach ($campaigns->where('is_featured', true)->take(3)->values() as $i => $campaign) {
            Banner::create(['title' => $campaign->title, 'campaign_id' => $campaign->id, 'sort_order' => $i, 'is_active' => true]);
        }

        // Last month's report, published.
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

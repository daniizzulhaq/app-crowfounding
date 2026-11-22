<?php

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Distribution;
use App\Models\Donation;
use App\Models\MonthlyReport;
use App\Services\Settings;

it('renders the home page with brand, campaigns and prayers but never phone numbers', function () {
    $campaign = Campaign::factory()->featured()->create(['title' => 'Sumur untuk desa']);
    Campaign::factory()->draft()->create(['title' => 'Rahasia draf']);
    Donation::factory()->paid()->withPrayer('Semoga berkah selalu')->create([
        'campaign_id' => $campaign->id, 'phone' => '6281299998888',
    ]);
    Donation::factory()->paid()->withPrayer('Doa tersembunyi')->create(['campaign_id' => $campaign->id, 'prayer_hidden' => true]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Alkiroma Amanah')
        ->assertSee('Sedekah cepat')
        ->assertSee('Sumur untuk desa')
        ->assertSee('Semoga berkah selalu')
        ->assertDontSee('Doa tersembunyi')
        ->assertDontSee('Rahasia draf')
        ->assertDontSee('6281299998888')
        ->assertDontSee('081299998888');
});

it('shows empty states when there is no content', function () {
    $this->get('/')->assertOk()->assertSee('Belum ada campaign aktif')->assertSee('Belum ada kabar penyaluran');
    $this->get('/campaign?q=tidakada')->assertOk()->assertSee('Campaign tidak ditemukan');
    $this->get('/laporan')->assertOk()->assertSee('Belum ada laporan terbit');
});

it('searches, filters by category and sorts campaigns', function () {
    $health = Category::factory()->create(['name' => 'Kesehatan', 'slug' => 'kesehatan']);
    Campaign::factory()->create(['title' => 'Operasi katarak', 'category_id' => $health->id]);
    Campaign::factory()->create(['title' => 'Beasiswa santri']);

    $this->get('/campaign?q=katarak')->assertOk()->assertSee('Operasi katarak')->assertDontSee('Beasiswa santri');
    $this->get('/campaign?kategori=kesehatan')->assertOk()->assertSee('Operasi katarak')->assertDontSee('Beasiswa santri');
    $this->get('/campaign?urut=terkumpul-terbanyak')->assertOk();
    $this->get('/campaign?urut=hack')->assertOk(); // invalid sort falls back
});

it('returns partial HTML with a next page header for infinite scroll', function () {
    Campaign::factory()->count(12)->create();

    $this->get('/campaign', ['X-Partial' => '1'])
        ->assertOk()
        ->assertHeader('X-Next-Page')
        ->assertDontSee('<html', false);
});

it('shows a published campaign detail and hides drafts', function () {
    $campaign = Campaign::factory()->collected(2_500_000, 3)->create(['target_amount' => 10_000_000]);
    $draft = Campaign::factory()->draft()->create();

    $this->get(route('campaigns.show', $campaign))
        ->assertOk()
        ->assertSee($campaign->title)
        ->assertSee('Rp2.500.000')
        ->assertSee('Donasi sekarang')
        ->assertSee('aria-valuenow="25"', false);

    $this->get(route('campaigns.show', $draft))->assertNotFound();
});

it('lists donors with "Hamba Allah" for anonymous donors and no phone', function () {
    $campaign = Campaign::factory()->create();
    Donation::factory()->paid()->anonymous()->create(['campaign_id' => $campaign->id, 'donor_name' => 'Nama Rahasia', 'phone' => '6281200001111']);

    $this->get(route('campaigns.show', $campaign))
        ->assertSee('Hamba Allah')
        ->assertDontSee('Nama Rahasia')
        ->assertDontSee('6281200001111');
});

it('renders distribution, report and static pages', function () {
    $campaign = Campaign::factory()->create();
    $distribution = Distribution::factory()->create(['campaign_id' => $campaign->id, 'title' => 'Paket sembako tahap 1']);
    $report = MonthlyReport::factory()->published()->create(['year' => 2026, 'month' => 10]);
    $draftReport = MonthlyReport::factory()->create(['year' => 2026, 'month' => 9]);

    $this->get('/penyaluran')->assertOk()->assertSee('Paket sembako tahap 1');
    $this->get('/penyaluran?campaign='.$campaign->slug)->assertOk()->assertSee('Paket sembako tahap 1');
    $this->get(route('distributions.show', $distribution))->assertOk()->assertSee($campaign->title);
    $this->get('/laporan')->assertOk()->assertSee('Oktober 2026');
    $this->get(route('reports.show', $report))->assertOk()->assertSee('Unduh laporan');
    $this->get(route('reports.show', $draftReport))->assertNotFound();
    $this->get(route('reports.download', $report))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get('/tentang')->assertOk()->assertSee('Alkiroma Amanah');
    $this->get('/syarat-ketentuan')->assertOk();
    $this->get('/kebijakan-privasi')->assertOk();
    $this->get('/sitemap.xml')->assertOk()->assertSee($campaign->slug);
});

it('includes SEO and Open Graph meta tags', function () {
    $campaign = Campaign::factory()->create(['title' => 'Bantu korban banjir', 'summary' => 'Ringkasan banjir']);

    $this->get(route('campaigns.show', $campaign))
        ->assertSee('<title>Bantu korban banjir · Alkiroma Amanah</title>', false)
        ->assertSee('property="og:title" content="Bantu korban banjir"', false)
        ->assertSee('og-default.png', false)
        ->assertSee('name="description" content="Ringkasan banjir"', false);
});

it('shows a home button on every page that has a back button', function () {
    $campaign = Campaign::factory()->create();
    $donation = Donation::factory()->create();

    foreach ([
        route('campaigns.show', $campaign),
        route('campaigns.donate', $campaign),
        route('donations.create'),
        route('donations.choose'),
        route('donations.status', $donation->order_id),
        route('prayers.index'),
        route('pages.about'),
    ] as $url) {
        $this->get($url)->assertOk()->assertSee('aria-label="Kembali ke beranda"', false);
    }
});

it('lists donor names in a published monthly report, hiding anonymous names and phones', function () {
    $report = MonthlyReport::factory()->published()->create(['year' => 2026, 'month' => 10]);
    $campaign = Campaign::factory()->create(['title' => 'Sumur desa']);
    Donation::factory()->paid()->create(['campaign_id' => $campaign->id, 'donor_name' => 'Budi Santoso', 'amount' => 75_000, 'paid_at' => '2026-10-12 09:00:00', 'phone' => '6281211112222']);
    Donation::factory()->paid()->anonymous()->create(['campaign_id' => $campaign->id, 'donor_name' => 'Nama Rahasia', 'paid_at' => '2026-10-20 09:00:00']);
    Donation::factory()->paid()->create(['donor_name' => 'Bulan Lain', 'paid_at' => '2026-11-02 09:00:00']);
    Donation::factory()->create(['donor_name' => 'Belum Bayar', 'created_at' => '2026-10-15 09:00:00']);

    $this->get(route('reports.show', $report))
        ->assertOk()
        ->assertSee('Daftar donatur')
        ->assertSee('Budi Santoso')
        ->assertSee('Rp75.000')
        ->assertSee('Hamba Allah')
        ->assertDontSee('Nama Rahasia')
        ->assertDontSee('Bulan Lain')
        ->assertDontSee('Belum Bayar')
        ->assertDontSee('6281211112222');

    $html = view('pdf.monthly-report', ['report' => $report, 'settings' => app(Settings::class)])->render();
    expect($html)->toContain('Budi Santoso')->toContain('Hamba Allah')->not->toContain('Nama Rahasia');
});

it('paginates report donors and hides drafts', function () {
    $report = MonthlyReport::factory()->published()->create(['year' => 2026, 'month' => 10]);
    $draft = MonthlyReport::factory()->create(['year' => 2026, 'month' => 9]);
    Donation::factory()->paid()->count(25)->create(['paid_at' => '2026-10-05 10:00:00']);

    $this->get(route('reports.donors', [$report, 'p' => 2]), ['X-Partial' => '1'])->assertOk()->assertDontSee('<html', false);
    $this->get(route('reports.donors', $draft))->assertNotFound();
});

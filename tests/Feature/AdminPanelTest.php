<?php

use App\Enums\DonationStatus;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\CampaignResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\DistributionResource;
use App\Filament\Resources\DonationResource;
use App\Filament\Resources\MonthlyReportResource;
use App\Filament\Resources\PrayerResource;
use App\Filament\Widgets\DonationsChart;
use App\Filament\Widgets\DonationStats;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Distribution;
use App\Models\Donation;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Queue::fake();
    $this->actingAs(admin());
});

it('blocks non-admin users and guests', function () {
    auth()->logout();
    $this->get('/admin')->assertRedirect('/admin/login');

    $this->actingAs(User::factory()->create());
    $this->get('/admin')->assertForbidden();
});

it('renders the dashboard and every resource page', function () {
    Donation::factory()->paid()->count(3)->create();
    Distribution::factory()->create();

    // Widgets are lazy-loaded, so render them directly.
    $this->get('/admin')->assertOk();
    livewire(DonationStats::class)->assertSee('Donasi hari ini')->assertSee('Campaign aktif');
    livewire(DonationsChart::class)->assertOk();

    foreach ([CampaignResource::class, CategoryResource::class, DonationResource::class, DistributionResource::class, MonthlyReportResource::class, PrayerResource::class] as $resource) {
        $this->get($resource::getUrl('index'))->assertOk();
    }

    $this->get(CampaignResource::getUrl('create'))->assertOk();
    $this->get(DistributionResource::getUrl('create'))->assertOk();
    $this->get(ManageSettings::getUrl())->assertOk();
});

it('creates a campaign that goes live immediately', function () {
    $category = Category::factory()->create();

    livewire(CampaignResource\Pages\CreateCampaign::class)
        ->fillForm([
            'title' => 'Wakaf sumur',
            'slug' => 'wakaf-sumur',
            'category_id' => $category->id,
            'story' => '<p>Cerita</p>',
            'target_amount' => 5_000_000,
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $campaign = Campaign::firstWhere('slug', 'wakaf-sumur');
    expect($campaign->published_at)->not->toBeNull()
        ->and($campaign->collected_amount)->toBe(0);
    $this->get(route('campaigns.show', $campaign))->assertOk();
});

it('shows real names of anonymous donors and keeps donations read-only', function () {
    $donation = Donation::factory()->paid()->anonymous()->create(['donor_name' => 'Nama Asli Donatur']);

    livewire(DonationResource\Pages\ListDonations::class)
        ->assertCanSeeTableRecords([$donation])
        ->assertSee('Nama Asli Donatur');

    expect(DonationResource::canCreate())->toBeFalse()
        ->and(DonationResource::canEdit($donation))->toBeFalse()
        ->and(DonationResource::canDelete($donation))->toBeFalse();
});

it('filters donations by status and exports Excel', function () {
    $paid = Donation::factory()->paid()->create();
    $pending = Donation::factory()->create();

    livewire(DonationResource\Pages\ListDonations::class)
        ->filterTable('status', [DonationStatus::Paid->value])
        ->assertCanSeeTableRecords([$paid])
        ->assertCanNotSeeTableRecords([$pending])
        ->callAction('export')
        ->assertFileDownloaded();
});

it('checks a donation status against Midtrans through the verified pipeline', function () {
    $campaign = Campaign::factory()->create();
    $donation = Donation::factory()->create(['campaign_id' => $campaign->id, 'amount' => 30_000]);
    Http::fake(['*/v2/*/status' => Http::response(midtransPayload($donation))]);

    livewire(DonationResource\Pages\ListDonations::class)
        ->callTableAction('checkStatus', $donation);

    expect($donation->fresh()->status)->toBe(DonationStatus::Paid)
        ->and($campaign->fresh()->collected_amount)->toBe(30_000);
});

it('records a distribution and blocks amounts above the balance', function () {
    $campaign = Campaign::factory()->collected(1_000_000)->create();

    livewire(DistributionResource\Pages\CreateDistribution::class)
        ->fillForm([
            'title' => 'Sembako',
            'slug' => 'sembako',
            'campaign_id' => $campaign->id,
            'amount' => 2_000_000,
            'distributed_at' => today()->toDateString(),
            'location' => 'Bogor',
            'recipient' => '10 keluarga',
            'body' => '<p>Isi</p>',
        ])
        ->call('create')
        ->assertHasFormErrors(['amount']);

    livewire(DistributionResource\Pages\CreateDistribution::class)
        ->fillForm([
            'title' => 'Sembako',
            'slug' => 'sembako',
            'campaign_id' => $campaign->id,
            'amount' => 600_000,
            'distributed_at' => today()->toDateString(),
            'location' => 'Bogor',
            'recipient' => '10 keluarga',
            'body' => '<p>Isi</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($campaign->fresh()->distributed_amount)->toBe(600_000);
});

it('hides inappropriate prayers', function () {
    $donation = Donation::factory()->paid()->withPrayer('Doa tidak pantas')->create();

    livewire(PrayerResource\Pages\ListPrayers::class)->callTableAction('toggle', $donation);

    expect($donation->fresh()->prayer_hidden)->toBeTrue();
    $this->get('/doa')->assertDontSee('Doa tidak pantas');
});

it('generates and publishes a monthly report', function () {
    Donation::factory()->paid()->create(['paid_at' => now()->subMonthNoOverflow()->startOfMonth()->addDay(), 'amount' => 100_000]);
    $last = now()->subMonthNoOverflow();

    livewire(MonthlyReportResource\Pages\ListMonthlyReports::class)
        ->callAction('generate', ['month' => $last->month, 'year' => $last->year]);

    $report = MonthlyReport::first();
    expect($report->total_in)->toBe(100_000);

    livewire(MonthlyReportResource\Pages\ListMonthlyReports::class)->callTableAction('publish', $report);

    expect($report->fresh()->isPublished())->toBeTrue();
});

it('saves settings and caps operational fee at 10%', function () {
    livewire(ManageSettings::class)
        ->fillForm(['foundation_name' => 'Alkiroma Amanah', 'operational_fee_percent' => 15])
        ->call('save')
        ->assertHasFormErrors(['operational_fee_percent']);

    livewire(ManageSettings::class)
        ->fillForm(['foundation_name' => 'Alkiroma Amanah', 'pub_permit_number' => 'PUB-77', 'operational_fee_percent' => 7])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(Settings::class)->get('pub_permit_number'))->toBe('PUB-77')
        ->and(app(Settings::class)->operationalFeePercent())->toBe(7);
});

<?php

use App\Actions\Donations\ApplyPaymentNotification;
use App\Filament\Resources\CampaignResource\Pages\CreateCampaign;
use App\Filament\Resources\CampaignResource\Pages\EditCampaign;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Donation;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(fn () => Queue::fake());

it('lets admins create an ongoing campaign with a blank target', function ($target) {
    $this->actingAs(admin());
    $category = Category::factory()->create();
    livewire(CreateCampaign::class)->fillForm([
        'title' => 'Waqaf Quran Berkelanjutan', 'slug' => 'waqaf-quran',
        'category_id' => $category->id, 'story' => '<p>Waqaf Quran tanpa target dana.</p>',
        'status' => 'active', 'target_amount' => $target, 'deadline' => null,
    ])->call('create')->assertHasNoFormErrors();
    $campaign = Campaign::where('slug', 'waqaf-quran')->sole();
    expect($campaign->target_amount)->toBeNull()->and($campaign->deadline)->toBeNull()
        ->and($campaign->hasTarget())->toBeFalse()->and($campaign->acceptsDonations())->toBeTrue();
})->with(['empty field' => [''], 'null field' => [null]]);

it('lets admins clear and restore the target without changing collected donations', function () {
    $this->actingAs(admin());
    $campaign = Campaign::factory()->collected(300000, 2)->create(['target_amount' => 1000000]);
    livewire(EditCampaign::class, ['record' => $campaign->getRouteKey()])
        ->fillForm(['target_amount' => ''])->call('save')->assertHasNoFormErrors();
    expect($campaign->fresh()->target_amount)->toBeNull()->and($campaign->fresh()->collected_amount)->toBe(300000);
    livewire(EditCampaign::class, ['record' => $campaign->getRouteKey()])
        ->fillForm(['target_amount' => 750000])->call('save')->assertHasNoFormErrors();
    expect($campaign->fresh()->hasTarget())->toBeTrue()->and($campaign->fresh()->progress_percent)->toBe(40);
});

it('still validates positive targets when admins choose to set one', function () {
    $this->actingAs(admin());
    $category = Category::factory()->create();
    livewire(CreateCampaign::class)->fillForm([
        'title' => 'Target terlalu kecil', 'slug' => 'target-kecil', 'category_id' => $category->id,
        'story' => '<p>Cerita.</p>', 'status' => 'active', 'target_amount' => 1000,
    ])->call('create')->assertHasFormErrors(['target_amount' => 'min']);
});

it('hides empty progress and target amounts on public campaign surfaces', function () {
    $campaign = Campaign::factory()->featured()->collected(300000, 2)->create(['target_amount' => null, 'deadline' => null]);
    foreach ([route('home'), route('campaigns.index'), route('campaigns.show', $campaign), route('donations.choose')] as $url) {
        $this->get($url)->assertOk()->assertSee('Tanpa target dana')
            ->assertDontSee('role="progressbar"', false)->assertDontSee('dari target');
    }
    $this->getJson('/api/v1/campaigns')->assertOk()->assertJsonPath('data.0.has_target', false)->assertJsonPath('data.0.target_amount', null);
    $this->getJson('/api/v1/campaigns/'.$campaign->slug)->assertOk()
        ->assertJsonPath('data.has_target', false)->assertJsonPath('data.target_amount', null)->assertJsonPath('data.progress_percent', 0);
});

it('keeps progress for funded targets and accepts zero as an ongoing legacy value', function () {
    $targeted = Campaign::factory()->collected(300000, 2)->create(['target_amount' => 1000000]);
    $this->get(route('campaigns.show', $targeted))->assertOk()->assertSee('role="progressbar"', false)->assertSee('Rp1.000.000');
    $this->getJson('/api/v1/campaigns/'.$targeted->slug)->assertJsonPath('data.has_target', true)->assertJsonPath('data.progress_percent', 30);
    $ongoing = Campaign::factory()->create(['target_amount' => 0]);
    expect($ongoing->fresh()->target_amount)->toBeNull()->and($ongoing->hasTarget())->toBeFalse();
});

it('accepts paid Quran units indefinitely without a funding target or deadline', function () {
    fakeSnap();
    $category = Category::factory()->create(['donation_type' => 'unit', 'unit_label' => 'mushaf', 'unit_price' => 150000]);
    $campaign = Campaign::factory()->for($category)->create(['target_amount' => null, 'deadline' => null]);
    $this->postJson('/api/v1/donations', ['campaign' => $campaign->slug, 'quantity' => 2, 'amount' => 300000,
        'donor_name' => 'Donatur', 'phone' => '081234567890'])->assertCreated();
    app(ApplyPaymentNotification::class)->handle(midtransPayload(Donation::sole()));
    expect($campaign->fresh()->target_amount)->toBeNull()->and($campaign->fresh()->acceptsDonations())->toBeTrue()
        ->and($campaign->fresh()->collected_amount)->toBe(300000)->and($campaign->paidDonations()->sum('quantity'))->toBe(2);
    $this->get(route('campaigns.show', $campaign))->assertOk()->assertSee('2 mushaf')->assertSee('Rp300.000')->assertSee('Tanpa target dana');
});

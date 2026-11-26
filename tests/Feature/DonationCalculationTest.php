<?php

use App\Actions\Donations\ApplyPaymentNotification;
use App\Filament\Resources\CategoryResource\Pages\ManageCategories;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Donation;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(fn () => Queue::fake());

function calculatedCampaign(string $type, array $config = []): Campaign
{
    $category = Category::factory()->create(array_merge([
        'donation_type' => $type, 'unit_label' => 'mushaf', 'unit_price' => 150000,
        'zakat_rate' => '2.50', 'zakat_nisab' => 120000000,
    ], $config));

    return Campaign::factory()->for($category)->create();
}

function calculatedPayment(Campaign $campaign, array $data = []): array
{
    return array_merge(['campaign' => $campaign->slug, 'amount' => 300000,
        'donor_name' => 'Donatur', 'phone' => '081234567890', 'is_anonymous' => false], $data);
}

it('shares category settings with campaign lists, details and the web form', function () {
    $campaign = calculatedCampaign('unit');
    $this->getJson('/api/v1/campaigns')->assertOk()->assertJsonPath('data.0.donation_config.unit_price', 150000);
    $this->getJson('/api/v1/campaigns/'.$campaign->slug)->assertOk()->assertJsonPath('data.donation_config.type', 'unit');
    $this->get(route('campaigns.donate', $campaign))->assertOk()->assertSee('Jumlah mushaf')->assertSee('Rp150.000');
    $campaign->category->update(['unit_price' => 200000]);
    $this->getJson('/api/v1/campaigns/'.$campaign->slug)->assertOk()->assertJsonPath('data.donation_config.unit_price', 200000);
});

it('quotes unit quantities using only the stored price', function () {
    $campaign = calculatedCampaign('unit');
    $this->postJson('/api/v1/donation-quote', ['campaign' => $campaign->slug, 'quantity' => 2, 'unit_price' => 1])
        ->assertOk()->assertJsonPath('data.amount', 300000)->assertJsonPath('data.quantity', 2)
        ->assertJsonPath('data.calculation.unit_price', 150000);
    $this->postJson(route('donations.quote'), ['campaign' => $campaign->slug, 'amount' => 300000])
        ->assertOk()->assertJsonPath('data.quantity', 2);
});

it('creates identical quantity payments from web and mobile without counting pending units', function (string $endpoint) {
    fakeSnap();
    $campaign = calculatedCampaign('unit');
    $this->postJson($endpoint, calculatedPayment($campaign, ['quantity' => 2]))->assertCreated();
    $donation = Donation::sole();
    expect($donation->amount)->toBe(300000)->and($donation->quantity)->toBe(2)
        ->and($donation->calculation['unit_price'])->toBe(150000)
        ->and($campaign->paidDonations()->sum('quantity'))->toBe(0);
    $campaign->category->update(['unit_price' => 200000]);
    app(ApplyPaymentNotification::class)->handle(midtransPayload($donation));
    app(ApplyPaymentNotification::class)->handle(midtransPayload($donation));
    expect($donation->fresh()->calculation['unit_price'])->toBe(150000)
        ->and($campaign->paidDonations()->sum('quantity'))->toBe(2)
        ->and($campaign->fresh()->collected_amount)->toBe(300000);
    $this->getJson('/api/v1/campaigns/'.$campaign->slug)->assertJsonPath('data.paid_units', 2);
    $this->get(route('donations.status', $donation->order_id))->assertOk()->assertSee('2 mushaf');
})->with(['/donasi', '/api/v1/donations']);

it('rejects fractional units, nonmultiples and forged totals without creating payments', function (array $input) {
    fakeSnap();
    $campaign = calculatedCampaign('unit');
    $this->postJson('/api/v1/donations', calculatedPayment($campaign, $input))->assertUnprocessable();
    expect(Donation::count())->toBe(0);
})->with([
    'fractional' => [['quantity' => 1.5]], 'zero' => [['quantity' => 0]],
    'forged total' => [['quantity' => 2, 'amount' => 150000]],
    'not a multiple' => [['amount' => 250000]], 'too many' => [['quantity' => 100001]],
]);

it('rejects stale category type and price on payment submission', function () {
    fakeSnap();
    $campaign = calculatedCampaign('unit');
    $campaign->category->update(['unit_price' => 200000]);
    $this->postJson('/api/v1/donations', calculatedPayment($campaign, ['quantity' => 2, 'expected_type' => 'unit']))
        ->assertUnprocessable()->assertJsonValidationErrors('amount');
    $campaign->category->update(['donation_type' => 'nominal']);
    $this->postJson('/api/v1/donations', calculatedPayment($campaign, ['quantity' => 2, 'expected_type' => 'unit']))
        ->assertUnprocessable()->assertJsonValidationErrors('calculation_input');
    expect(Donation::count())->toBe(0);
});

it('calculates income zakat with the correct period, nisab boundary and rupiah rounding', function (int $nisab, string $period, int $income, int $bonus, int $amount, bool $eligible) {
    $campaign = calculatedCampaign('zakat_income', ['zakat_nisab' => $nisab]);
    $this->postJson('/api/v1/donation-quote', ['campaign' => $campaign->slug, 'payment_mode' => 'calculate',
        'calculation_input' => compact('period', 'income', 'bonus')])
        ->assertOk()->assertJsonPath('data.amount', $amount)->assertJsonPath('data.eligible', $eligible);
})->with([
    [120000000, 'monthly', 10000000, 2000000, 300000, true],
    [120000000, 'annual', 120000000, 0, 3000000, true],
    [120000000, 'monthly', 9999999, 0, 0, false],
    [120000001, 'monthly', 10000000, 0, 0, false],
    [120000001, 'monthly', 10000001, 0, 250001, true],
]);

it('requires nisab and haul for mal and subtracts eligible short term debts', function (bool $haul, int $amount) {
    $campaign = calculatedCampaign('zakat_mal', ['zakat_nisab' => 150000000]);
    $this->postJson('/api/v1/donation-quote', ['campaign' => $campaign->slug, 'payment_mode' => 'calculate',
        'calculation_input' => ['cash' => 100000000, 'gold' => 60000000, 'investments' => 20000000,
            'other' => 10000000, 'debts' => 10000000, 'haul' => $haul]])
        ->assertOk()->assertJsonPath('data.amount', $amount)->assertJsonPath('data.eligible', $haul);
})->with([[true, 4500000], [false, 0]]);

it('recomputes zakat before payment and stores no raw financial inputs', function () {
    fakeSnap();
    $campaign = calculatedCampaign('zakat_income');
    $data = calculatedPayment($campaign, ['payment_mode' => 'calculate',
        'calculation_input' => ['period' => 'monthly', 'income' => 12000000, 'bonus' => 0]]);
    $this->postJson('/api/v1/donations', array_merge($data, ['amount' => 250000]))->assertUnprocessable();
    $response = $this->postJson('/api/v1/donations', $data)->assertCreated()
        ->assertJsonPath('data.calculation.type', 'zakat_income')->assertJsonPath('data.amount', 300000);
    expect(Donation::sole()->calculation)->not->toHaveKey('income')->not->toHaveKey('calculation_input');
    $campaign->category->update(['zakat_rate' => '3.00']);
    $this->getJson($response->json('data.status_url'))->assertJsonPath('data.calculation.rate', '2.50');
});

it('blocks missing nisab, negative inputs, unpaid eligibility and hidden campaigns', function () {
    fakeSnap();
    $campaign = calculatedCampaign('zakat_income', ['zakat_nisab' => null]);
    $data = calculatedPayment($campaign, ['payment_mode' => 'calculate', 'calculation_input' => ['income' => 12000000]]);
    $this->postJson('/api/v1/donation-quote', $data)->assertUnprocessable();
    $this->postJson('/api/v1/donations', $data)->assertUnprocessable();
    $campaign->category->update(['zakat_nisab' => 120000000]);
    $this->postJson('/api/v1/donation-quote', array_replace_recursive($data, ['calculation_input' => ['income' => -1]]))->assertUnprocessable();
    $this->postJson('/api/v1/donations', array_replace_recursive($data, ['calculation_input' => ['income' => 1]]))->assertUnprocessable();
    $campaign->update(['status' => 'draft']);
    $this->postJson('/api/v1/donation-quote', $data)->assertUnprocessable();
    expect(Donation::count())->toBe(0);
});

it('allows manual zakat payment and renders both calculator forms', function () {
    fakeSnap();
    foreach (['zakat_income', 'zakat_mal'] as $type) {
        $campaign = calculatedCampaign($type);
        $this->get(route('campaigns.donate', $campaign))->assertOk()->assertSee('Hitung zakat')->assertSee('Bayar langsung');
        $this->postJson('/api/v1/donations', calculatedPayment($campaign, ['payment_mode' => 'direct']))->assertCreated();
    }
    expect(Donation::count())->toBe(2)->and(Donation::first()->calculation['mode'])->toBe('direct');
});

it('lets admin configure unit categories and requires their price', function () {
    $this->actingAs(admin());
    livewire(ManageCategories::class)->callAction('create', data: [
        'name' => 'Wakaf Quran', 'slug' => 'wakaf-quran', 'sort_order' => 1,
        'donation_type' => 'unit', 'unit_label' => 'mushaf', 'unit_price' => 150000,
    ])->assertHasNoActionErrors();
    expect(Category::where('slug', 'wakaf-quran')->sole()->unit_price)->toBe(150000);
    livewire(ManageCategories::class)->callAction('create', data: [
        'name' => 'Tanpa Harga', 'slug' => 'tanpa-harga', 'sort_order' => 2,
        'donation_type' => 'unit', 'unit_label' => 'mushaf',
    ])->assertHasActionErrors(['unit_price' => 'required']);
});

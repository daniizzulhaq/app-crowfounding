<?php

use App\Actions\Donations\ApplyPaymentNotification;
use App\Models\Campaign;
use App\Models\Distribution;
use App\Models\Donation;
use App\Models\MobileDonorToken;
use App\Models\MonthlyReport;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('exposes the same published campaigns and hides drafts on every entry point', function () {
    $campaign = Campaign::factory()->create();
    $draft = Campaign::factory()->draft()->create();
    $this->getJson('/api/v1/home')->assertOk()->assertJsonPath('data.latest.0.slug', $campaign->slug);
    $this->getJson('/api/v1/campaigns')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1);
    $this->getJson('/api/v1/campaigns/'.$draft->slug)->assertNotFound();
    $this->getJson('/api/v1/campaigns/'.$draft->slug.'/donors')->assertNotFound();
    $this->getJson('/api/v1/campaigns?q='.urlencode($campaign->title))->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/campaigns?urut=invalid')->assertUnprocessable();
    $this->get('/api/v1/campaigns/missing')->assertNotFound()->assertHeader('Content-Type', 'application/json');
});

it('respects anonymity and moderated prayers without leaking donor data', function () {
    $campaign = Campaign::factory()->create();
    Donation::factory()->for($campaign)->paid()->anonymous()->withPrayer('PRIVATE PRAYER')->create([
        'donor_name' => 'PRIVATE REAL NAME', 'prayer_hidden' => true, 'phone' => '6281234567890',
        'snap_token' => 'PRIVATE SNAP', 'midtrans_payload' => ['secret' => 'PRIVATE PAYLOAD'],
    ]);
    $response = $this->getJson('/api/v1/campaigns/'.$campaign->slug.'/donors')->assertOk()
        ->assertJsonPath('data.0.display_name', Donation::ANONYMOUS_NAME)->assertJsonPath('data.0.prayer', null);
    foreach (['PRIVATE REAL NAME', 'PRIVATE PRAYER', '6281234567890', 'PRIVATE SNAP', 'PRIVATE PAYLOAD'] as $secret) {
        $response->assertDontSee($secret);
    }
    $this->getJson('/api/v1/prayers')->assertOk()->assertJsonCount(0, 'data');
});

it('creates mobile payments and uses the existing verified payment pipeline', function () {
    fakeSnap();
    $campaign = Campaign::factory()->create();
    $response = $this->postJson('/api/v1/donations', [
        'campaign' => $campaign->slug, 'amount' => 25000, 'donor_name' => 'Donatur mobile',
        'phone' => '081234567890', 'is_anonymous' => false,
    ])->assertCreated()->assertJsonPath('data.status', 'pending');
    $donation = Donation::firstWhere('order_id', $response->json('data.order_id'));
    $response->assertJsonPath('data.finish_url', route('donations.status', $donation->order_id));
    Http::assertSent(fn ($request) => str_contains($request->url(), '/snap/v1/transactions')
        && $request['callbacks']['finish'] === $response->json('data.finish_url'));
    expect($campaign->fresh()->collected_amount)->toBe(0);
    app(ApplyPaymentNotification::class)->handle(midtransPayload($donation));
    app(ApplyPaymentNotification::class)->handle(midtransPayload($donation));
    expect($campaign->fresh()->collected_amount)->toBe(25000)->and($campaign->fresh()->donor_count)->toBe(1);
    $this->getJson($response->json('data.status_url'))->assertOk()->assertJsonPath('data.status', 'paid');
    $this->getJson('/api/v1/donations/'.$donation->order_id)->assertForbidden();
    $this->getJson(str_replace($donation->order_id, 'AKA-000000-AAAAAAAA', $response->json('data.status_url')))->assertForbidden();
    $this->get(route('campaigns.show', $campaign))->assertOk()->assertSee('Rp25.000');
});

it('rejects closed and draft campaigns and rolls back gateway failures', function () {
    $closed = Campaign::factory()->completed()->create();
    $data = ['campaign' => $closed->slug, 'amount' => 25000, 'is_anonymous' => true, 'phone' => '081234567890'];
    $this->postJson('/api/v1/donations', $data)->assertUnprocessable();
    $data['campaign'] = Campaign::factory()->draft()->create()->slug;
    $this->postJson('/api/v1/donations', $data)->assertUnprocessable();
    $data['campaign'] = null;
    $this->postJson('/api/v1/donations', array_merge($data, ['amount' => 1]))->assertUnprocessable();
    Http::fake(['*' => Http::response([], 503)]);
    $this->postJson('/api/v1/donations', $data)->assertStatus(503);
    expect(Donation::count())->toBe(0);
});

it('verifies WhatsApp and limits donor history to that number then revokes the session', function () {
    $phone = '6281234567890';
    $this->postJson('/api/v1/auth/otp', ['phone' => '081234567890'])->assertOk()->assertJsonMissing(['token']);
    $otp = OtpCode::latest('id')->first();
    $otp->update(['code_hash' => Hash::make('123456')]);
    $this->postJson('/api/v1/auth/verify', ['phone' => $phone, 'code' => '000000'])->assertUnprocessable();
    expect($otp->fresh()->attempts)->toBe(1);
    $response = $this->postJson('/api/v1/auth/verify', ['phone' => $phone, 'code' => '123456'])->assertOk();
    $plain = $response->json('data.token');
    expect(MobileDonorToken::first()->token_hash)->toBe(hash('sha256', $plain));
    Donation::factory()->paid()->create(['phone' => $phone, 'amount' => 50000]);
    Donation::factory()->paid()->create(['phone' => '6289999999999']);
    $headers = ['Authorization' => 'Bearer '.$plain];
    $this->getJson('/api/v1/me', $headers)->assertOk()->assertJsonPath('data.total_paid', 50000);
    $this->getJson('/api/v1/me/donations', $headers)->assertOk()->assertJsonCount(1, 'data');
    $this->postJson('/api/v1/auth/verify', ['phone' => $phone, 'code' => '123456'])->assertUnprocessable();
    $this->postJson('/api/v1/auth/logout', [], $headers)->assertOk();
    $this->getJson('/api/v1/me', $headers)->assertUnauthorized();
});

it('requires an unexpired bearer token and never accepts a phone query as authentication', function () {
    $this->getJson('/api/v1/me/donations?phone=6281234567890')->assertUnauthorized();
    $plain = bin2hex(random_bytes(32));
    MobileDonorToken::create(['phone' => '6281234567890', 'token_hash' => hash('sha256', $plain), 'expires_at' => now()->subSecond()]);
    $this->getJson('/api/v1/me/donations', ['Authorization' => 'Bearer '.$plain])->assertUnauthorized();
});

it('exposes published reports, distributions and public settings only', function () {
    $draft = MonthlyReport::factory()->create();
    $published = MonthlyReport::factory()->published()->create(['month' => 1, 'year' => 2025]);
    $distribution = Distribution::factory()->create();
    $this->getJson('/api/v1/reports')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/reports/'.$draft->getRouteKey())->assertNotFound();
    $this->getJson('/api/v1/reports/'.$published->getRouteKey())->assertOk()->assertJsonPath('data.total_in', $published->total_in);
    $this->getJson('/api/v1/distributions/'.$distribution->slug)->assertOk()->assertJsonPath('data.amount', $distribution->amount);
    $this->getJson('/api/v1/settings')->assertOk()->assertDontSee('server_key')->assertDontSee('WHATSAPP_TOKEN');
});

it('expires signed receipts and rate limits donation creation', function () {
    fakeSnap();
    $data = ['amount' => 25000, 'is_anonymous' => true, 'phone' => '081234567890'];
    $response = $this->postJson('/api/v1/donations', $data)->assertCreated();
    $this->travel(8)->days();
    $this->getJson($response->json('data.status_url'))->assertForbidden();
    foreach (range(1, 6) as $i) {
        $this->postJson('/api/v1/donations', $data)->assertCreated();
    }
    $this->postJson('/api/v1/donations', $data)->assertStatus(429);
});

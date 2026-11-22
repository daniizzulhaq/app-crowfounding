<?php

use App\Jobs\SendWhatsAppMessage;
use App\Models\Donation;
use App\Support\DeviceDonations;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

function lastOtpCode(): string
{
    $code = '';
    Queue::assertPushed(SendWhatsAppMessage::class, function ($job) use (&$code) {
        if (preg_match('/\*(\d{6})\*/', $job->message, $m)) {
            $code = $m[1];
        }

        return true;
    });

    return $code;
}

it('shows an empty state with a WhatsApp option when this device has no donations', function () {
    $this->get('/donasi-saya')->assertOk()->assertSee('Belum ada donasi dari HP ini')->assertSee(route('my-donations.phone'));
    $this->get('/donasi-saya/nomor')->assertOk()->assertSee('Kirim kode verifikasi');
});

it('verifies via OTP and shows only that number\'s donations', function () {
    Donation::factory()->paid()->create(['phone' => '6281234567890', 'amount' => 75_000]);
    Donation::factory()->create(['phone' => '6281234567890', 'amount' => 20_000]);
    $other = Donation::factory()->paid()->create(['phone' => '6289999999999']);

    $this->post('/donasi-saya/otp', ['phone' => '0812-3456-7890'])
        ->assertRedirect(route('my-donations.verify'));

    $this->get(route('my-donations.verify'))->assertOk()->assertSee('0812****7890');

    $this->post('/donasi-saya/verifikasi', ['code' => lastOtpCode()])
        ->assertRedirect(route('my-donations.index'));

    $this->get('/donasi-saya')
        ->assertOk()
        ->assertSee('Rp75.000') // total paid
        ->assertSee('Menunggu')
        ->assertDontSee($other->order_id);
});

it('rejects a wrong code', function () {
    $this->post('/donasi-saya/otp', ['phone' => '081234567890']);

    $this->from(route('my-donations.verify'))
        ->post('/donasi-saya/verifikasi', ['code' => '999999'])
        ->assertSessionHasErrors('code');

    $this->get('/donasi-saya')->assertSee('Belum ada donasi dari HP ini');
});

it('validates the phone number', function () {
    $this->post('/donasi-saya/otp', ['phone' => '123'])->assertSessionHasErrors('phone');
    Queue::assertNothingPushed();
});

it('rate limits OTP requests per number', function () {
    foreach (range(1, 3) as $i) {
        $this->post('/donasi-saya/otp', ['phone' => '081234567890'])->assertRedirect();
    }

    $this->post('/donasi-saya/otp', ['phone' => '081234567890'])->assertTooManyRequests();
});

it('logs the donor out', function () {
    $this->post('/donasi-saya/otp', ['phone' => '081234567890']);
    $this->post('/donasi-saya/verifikasi', ['code' => lastOtpCode()]);

    $this->post('/donasi-saya/keluar')->assertRedirect(route('my-donations.index'));
    $this->get('/donasi-saya')->assertSee('Belum ada donasi dari HP ini');
});

it('shows donations made from this device automatically, without phone or OTP', function () {
    fakeSnap();

    $response = $this->postJson(route('donations.store'), [
        'amount' => 40_000, 'donor_name' => 'Fulan', 'is_anonymous' => false, 'phone' => '081234567890',
    ])->assertCreated()->assertCookie(DeviceDonations::COOKIE);
    $orderId = $response->json('order_id');
    $cookie = $response->getCookie(DeviceDonations::COOKIE, decrypt: true, unserialize: false)->getValue();

    $someoneElse = Donation::factory()->paid()->create(['phone' => '6281234567890']);

    // The test client doesn't keep cookies between requests: send it back like a browser would.
    $this->withCookie(DeviceDonations::COOKIE, $cookie)
        ->get('/donasi-saya')
        ->assertOk()
        ->assertSee('Donasi yang dibuat dari HP ini')
        ->assertSee($orderId)
        ->assertDontSee($someoneElse->order_id) // same phone, other device: needs OTP
        ->assertSee(route('my-donations.phone'));
});

it('ignores a tampered or garbage device cookie', function () {
    $other = Donation::factory()->paid()->create();

    $this->withUnencryptedCookie(DeviceDonations::COOKIE, json_encode([$other->order_id]))
        ->get('/donasi-saya')
        ->assertOk()
        ->assertDontSee($other->order_id);
});

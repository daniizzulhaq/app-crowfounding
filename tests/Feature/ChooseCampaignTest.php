<?php

use App\Models\Campaign;
use App\Models\Category;

it('lists only campaigns that can receive donations, plus the general fund', function () {
    $open = Campaign::factory()->create(['title' => 'Sumur untuk desa']);
    Campaign::factory()->completed()->create(['title' => 'Campaign selesai']);
    Campaign::factory()->draft()->create(['title' => 'Campaign draf']);
    Campaign::factory()->create(['title' => 'Lewat deadline', 'deadline' => today()->subDay()]);

    $this->get(route('donations.choose'))
        ->assertOk()
        ->assertSee('Mau donasi ke mana?')
        ->assertSee('Sedekah umum')
        ->assertSee('Sumur untuk desa')
        ->assertSee(route('campaigns.donate', $open), false)
        ->assertDontSee('Campaign selesai')
        ->assertDontSee('Campaign draf')
        ->assertDontSee('Lewat deadline');
});

it('carries the chosen amount to the donation form', function () {
    $campaign = Campaign::factory()->create();

    $this->get(route('donations.choose', ['nominal' => 50000]))
        ->assertSee('Rp50.000')
        ->assertSee(route('campaigns.donate', ['campaign' => $campaign, 'nominal' => 50000]), false)
        ->assertSee(route('donations.create', ['nominal' => 50000]), false);

    $this->get(route('campaigns.donate', ['campaign' => $campaign, 'nominal' => 50000]))
        ->assertOk()
        ->assertSee('Bayar Rp50.000')
        ->assertSee($campaign->title);
});

it('ignores an amount below the minimum', function () {
    $this->get(route('donations.choose', ['nominal' => 500]))->assertOk()->assertDontSee('Nominal dipilih');
});

it('searches and filters campaigns to choose from', function () {
    $food = Category::factory()->create(['slug' => 'pangan']);
    Campaign::factory()->create(['title' => 'Beras untuk dhuafa', 'category_id' => $food->id]);
    Campaign::factory()->create(['title' => 'Beasiswa santri']);

    $this->get(route('donations.choose', ['q' => 'beras']))->assertSee('Beras untuk dhuafa')->assertDontSee('Beasiswa santri');
    $this->get(route('donations.choose', ['kategori' => 'pangan']))->assertSee('Beras untuk dhuafa')->assertDontSee('Beasiswa santri');
    $this->get(route('donations.choose', ['q' => 'tidakada']))->assertSee('Campaign tidak ditemukan');
});

it('supports infinite scroll partials', function () {
    Campaign::factory()->count(12)->create();

    $this->get(route('donations.choose'), ['X-Partial' => '1'])
        ->assertOk()
        ->assertHeader('X-Next-Page')
        ->assertDontSee('<html', false);
});

it('shows the Donasi button in the bottom navigation and routes quick sedekah to the picker', function () {
    $this->get('/')
        ->assertSee('href="'.route('donations.choose').'"', false)
        ->assertSee('action="'.route('donations.choose').'"', false)
        ->assertSee('Langsung sedekah umum');
});

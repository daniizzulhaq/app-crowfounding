<?php

use App\Models\Campaign;
use App\Models\Category;

it('returns the same illustration in the API and website when no cover is uploaded', function (string $title, string $artwork) {
    $category = Category::factory()->create(['name' => 'Kebaikan bersama']);
    $campaign = Campaign::factory()->featured()->for($category)->create(['title' => $title]);
    $url = asset('images/crowdfunding/'.$artwork.'.jpg');

    $this->getJson('/api/v1/campaigns')->assertOk()
        ->assertJsonPath('data.0.cover_url', $url)->assertJsonPath('data.0.is_illustration', true);
    $this->getJson('/api/v1/campaigns/'.$campaign->slug)->assertOk()
        ->assertJsonPath('data.cover_url', $url)->assertJsonPath('data.is_illustration', true);
    $this->getJson('/api/v1/home')->assertOk()
        ->assertJsonPath('data.latest.0.cover_url', $url)->assertJsonPath('data.featured.0.cover_url', $url);
    $this->get('/campaign')->assertOk()->assertSee('src="'.$url.'"', false);
    $this->get('/campaign/'.$campaign->slug)->assertOk()->assertSee('src="'.$url.'"', false);
    expect(is_file(public_path('images/crowdfunding/'.$artwork.'.jpg')))->toBeTrue();
})->with([
    ['Bantu renovasi masjid', 'mosque'],
    ['Bantu biaya operasi', 'health'],
    ['Beasiswa untuk santri', 'education'],
    ['Bagikan sembako', 'food-aid'],
    ['Santunan yatim', 'children'],
    ['Bersama berbagi kebaikan', 'volunteers'],
]);

it('selects the illustration from the category when the title has no matching keyword', function () {
    $category = Category::factory()->create(['name' => 'Pendidikan']);
    $campaign = Campaign::factory()->for($category)->create(['title' => 'Ulurkan tanganmu']);
    $url = asset('images/crowdfunding/education.jpg');
    $this->getJson('/api/v1/campaigns/'.$campaign->slug)->assertOk()->assertJsonPath('data.cover_url', $url);
    $this->get('/campaign/'.$campaign->slug)->assertOk()->assertSee('src="'.$url.'"', false);
});

it('keeps uploaded covers ahead of illustrations with or without generated conversions', function (bool $converted) {
    $campaign = Campaign::factory()->create(['title' => 'Bantuan pendidikan']);
    $campaign->media()->create([
        'collection_name' => 'cover', 'name' => 'Foto asli', 'file_name' => 'foto-asli.jpg',
        'mime_type' => 'image/jpeg', 'disk' => 'public', 'conversions_disk' => 'public', 'size' => 100,
        'manipulations' => [], 'custom_properties' => [],
        'generated_conversions' => $converted ? ['card' => true] : [], 'responsive_images' => [],
    ]);
    $campaign->unsetRelation('media');
    $url = $campaign->coverUrl();

    $this->getJson('/api/v1/campaigns/'.$campaign->slug)->assertOk()
        ->assertJsonPath('data.cover_url', $url)->assertJsonPath('data.is_illustration', false);
    $this->get('/campaign/'.$campaign->slug)->assertOk()->assertSee('src="'.$url.'"', false);
    expect($url)->toContain($converted ? 'foto-asli-card.webp' : 'foto-asli.jpg');
})->with([false, true]);

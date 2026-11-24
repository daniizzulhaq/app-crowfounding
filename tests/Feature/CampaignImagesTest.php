<?php

use App\Models\Campaign;
use App\Models\Category;

it('returns the same owner supplied program cover in the API and website', function (string $title, string $artwork) {
    $category = Category::factory()->create(['name' => 'Kebaikan bersama']);
    $campaign = Campaign::factory()->featured()->for($category)->create(['title' => $title]);
    $url = asset('images/programs/'.$artwork.'.jpg');

    $this->getJson('/api/v1/campaigns')->assertOk()
        ->assertJsonPath('data.0.cover_url', $url)->assertJsonPath('data.0.is_illustration', false)
        ->assertJsonPath('data.0.cover_source', 'foundation')->assertJsonPath('data.0.cover_asset', $artwork.'.jpg');
    $this->getJson('/api/v1/campaigns/'.$campaign->slug)->assertOk()
        ->assertJsonPath('data.cover_url', $url)->assertJsonPath('data.is_illustration', false);
    $this->getJson('/api/v1/home')->assertOk()
        ->assertJsonPath('data.latest.0.cover_url', $url)->assertJsonPath('data.featured.0.cover_url', $url);
    $this->get('/campaign')->assertOk()->assertSee('src="'.$url.'"', false);
    $this->get('/campaign/'.$campaign->slug)->assertOk()->assertSee('src="'.$url.'"', false)->assertDontSee('Foto ilustrasi');
    expect(is_file(public_path('images/programs/'.$artwork.'.jpg')))->toBeTrue();
})->with([
    ['Jumat Berkah', 'jumat-berkah'],
    ['Sedekah Subuh', 'sedekah-subuh'],
    ['Zakat fitrah dan fidyah', 'zakat'],
    ['Beasiswa untuk santri', 'santunan-yatim'],
    ['Santunan yatim', 'santunan-yatim'],
    ['Bersama berbagi kebaikan', 'jumat-berkah'],
]);

it('selects the program from the category when the title has no matching keyword', function () {
    $category = Category::factory()->create(['name' => 'Pendidikan']);
    $campaign = Campaign::factory()->for($category)->create(['title' => 'Ulurkan tanganmu']);
    $url = asset('images/programs/santunan-yatim.jpg');
    $this->getJson('/api/v1/campaigns/'.$campaign->slug)->assertOk()->assertJsonPath('data.cover_url', $url);
    $this->get('/campaign/'.$campaign->slug)->assertOk()->assertSee('src="'.$url.'"', false);
});

it('keeps uploaded covers ahead of default program covers with or without generated conversions', function (bool $converted) {
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
        ->assertJsonPath('data.cover_url', $url)->assertJsonPath('data.is_illustration', false)
        ->assertJsonPath('data.cover_source', 'upload')->assertJsonPath('data.cover_asset', null);
    $this->get('/campaign/'.$campaign->slug)->assertOk()->assertSee('src="'.$url.'"', false);
    expect($url)->toContain($converted ? 'foto-asli-card.webp' : 'foto-asli.jpg');
})->with([false, true]);

it('shares all four original program banners and their proportions with the homepage and mobile settings', function () {
    $web = $this->get('/')->assertOk()->assertDontSee('Maximus Beaumont')->assertDontSee('images/crowdfunding/children.jpg');
    $settings = $this->getJson('/api/v1/settings')->assertOk()->assertJsonCount(4, 'data.program_banners');
    $home = $this->getJson('/api/v1/home')->assertOk();

    foreach (\App\Support\ProgramImages::all() as $index => $program) {
        $url = asset('images/programs/'.$program['file']);
        $web->assertSee('src="'.$url.'"', false);
        $settings->assertJsonPath('data.program_banners.'.$index.'.image_url', $url);
        $home->assertJsonPath('data.settings.program_banners.'.$index.'.image_url', $url);
        $dimensions = getimagesize(public_path('images/programs/'.$program['file']));
        expect($dimensions[0])->toBe($program['width'])->and($dimensions[1])->toBe($program['height']);
    }
});

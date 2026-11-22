@props(['eyebrow' => 'AL KIROM PEDULI', 'title', 'description', 'icon' => 'heart'])
<section {{ $attributes->class('page-intro') }}>
    <div class="page-intro-icon"><x-glyph :name="$icon" /></div>
    <p class="eyebrow">{{ $eyebrow }}</p>
    <h1>{{ $title }}</h1>
    <p class="page-intro-description">{{ $description }}</p>
</section>

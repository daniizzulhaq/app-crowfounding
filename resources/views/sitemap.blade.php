{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach (['home', 'campaigns.index', 'distributions.index', 'reports.index', 'prayers.index', 'pages.about'] as $name)
        <url><loc>{{ route($name) }}</loc></url>
    @endforeach
    @foreach ($campaigns as $campaign)
        <url><loc>{{ route('campaigns.show', $campaign) }}</loc><lastmod>{{ $campaign->updated_at->toAtomString() }}</lastmod></url>
    @endforeach
    @foreach ($distributions as $distribution)
        <url><loc>{{ route('distributions.show', $distribution) }}</loc><lastmod>{{ $distribution->updated_at->toAtomString() }}</lastmod></url>
    @endforeach
    @foreach ($reports as $report)
        <url><loc>{{ route('reports.show', $report) }}</loc><lastmod>{{ $report->updated_at->toAtomString() }}</lastmod></url>
    @endforeach
</urlset>

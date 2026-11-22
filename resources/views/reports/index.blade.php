<x-layouts.app title="Laporan" active="reports">
    <x-page-intro eyebrow="TERBUKA, KARENA AMANAH" title="Setiap rupiah, punya cerita." description="Laporan bulanan dana masuk, biaya operasional, dan penyaluran. Terbuka untuk kamu, setiap waktu." icon="report" />
    <div class="px-5">
        <h2 class="mb-4 text-lg font-extrabold">Laporan bulanan</h2>

        @if ($settings->get('pub_permit_number'))
            <p class="mt-3 flex items-start gap-2 rounded-xl bg-jade-soft px-3 py-2 text-sm text-jade">
                <x-glyph name="shield" class="mt-0.5 size-4" />
                <span>Penggalangan dana berizin PUB No. <span class="font-semibold">{{ $settings->get('pub_permit_number') }}</span></span>
            </p>
        @endif

        <div class="mt-4 space-y-3">
            @forelse ($reports as $report)
                <a href="{{ route('reports.show', $report) }}" class="card flex items-center gap-3 p-4 hover:border-jade/40">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-jade-soft text-jade"><x-glyph name="report" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-bold">{{ $report->period_label }}</span>
                        <span class="block text-xs text-stone">
                            Masuk <span class="money font-semibold text-ink">{{ \App\Support\Rupiah::short($report->total_in) }}</span>
                            · Disalurkan <span class="money font-semibold text-ink">{{ \App\Support\Rupiah::short($report->total_distributed) }}</span>
                        </span>
                    </span>
                    <x-glyph name="chevron-right" class="text-stone" />
                </a>
            @empty
                <x-empty-state icon="report" title="Belum ada laporan terbit" text="Laporan bulanan akan terbit setiap awal bulan berikutnya." class="card" />
            @endforelse
        </div>

        @if ($reports->hasPages())
            <div class="mt-4">{{ $reports->links('pagination::simple-tailwind') }}</div>
        @endif
    </div>
</x-layouts.app>

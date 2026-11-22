<x-layouts.app :title="'Laporan '.$report->period_label" :heading="'Laporan '.$report->period_label" header="back"
               :back="route('reports.index')" :nav="false">
    <x-page-intro eyebrow="LAPORAN AMANAH" :title="$report->period_label" description="Transparansi adalah bagian dari kepercayaanmu. Berikut perjalanan dana kebaikan bulan ini." icon="report" />
    <div class="space-y-4 px-5">
        <section class="card p-4">
            <p class="text-xs text-stone">Saldo akhir bulan</p>
            <p class="money text-2xl font-extrabold text-jade">{{ rupiah($report->closing_balance) }}</p>
            <dl class="mt-4 divide-y divide-line text-sm">
                <div class="flex justify-between py-2.5"><dt class="text-stone">Saldo awal</dt><dd class="money font-semibold">{{ rupiah($report->opening_balance) }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-stone">Dana masuk <span class="text-xs">({{ number_format($report->donation_count, 0, ',', '.') }} donasi)</span></dt><dd class="money font-semibold text-jade">+{{ rupiah($report->total_in) }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-stone">Operasional ({{ $report->operational_fee_percent }}%)</dt><dd class="money font-semibold">−{{ rupiah($report->total_operational) }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="text-stone">Disalurkan</dt><dd class="money font-semibold">−{{ rupiah($report->total_distributed) }}</dd></div>
                <div class="flex justify-between py-2.5"><dt class="font-bold">Saldo akhir</dt><dd class="money font-extrabold">{{ rupiah($report->closing_balance) }}</dd></div>
            </dl>
        </section>

        <section class="card p-4">
            <h2 class="mb-2 text-[15px] font-bold">Rincian per campaign</h2>
            @forelse ($report->breakdown ?? [] as $line)
                <div class="border-t border-line py-3 first:border-t-0">
                    <p class="text-sm font-semibold">{{ $line['title'] }}</p>
                    <div class="mt-1 flex justify-between text-xs text-stone">
                        <span>Masuk <span class="money font-semibold text-ink">{{ rupiah($line['in']) }}</span></span>
                        <span>Disalurkan <span class="money font-semibold text-ink">{{ rupiah($line['distributed']) }}</span></span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-stone">Tidak ada transaksi pada bulan ini.</p>
            @endforelse
        </section>

        <section class="card p-4" x-data="loadMore(@js($donors->nextPageUrl()))">
            <div class="mb-1 flex items-baseline justify-between gap-2">
                <h2 class="text-[15px] font-bold">Daftar donatur</h2>
                <p class="text-xs text-stone"><span class="money font-semibold text-ink">{{ number_format($report->donation_count, 0, ',', '.') }}</span> donasi</p>
            </div>
            <p class="text-xs text-stone">Jazakumullah khairan. Donatur yang menyembunyikan nama tampil sebagai “Hamba Allah”.</p>
            @if ($donors->isEmpty())
                <p class="pt-3 text-sm text-stone">Belum ada donasi pada bulan ini.</p>
            @else
                <ul x-ref="list" class="mt-1 divide-y divide-line">
                    @include('reports._donors')
                </ul>
                <x-load-more :next="$donors->nextPageUrl()" />
            @endif
        </section>

        <a href="{{ route('reports.download', $report) }}" class="btn btn-primary w-full"><x-glyph name="download" /> Unduh laporan (PDF)</a>
        <p class="pb-6 text-center text-xs text-stone">Diterbitkan {{ $report->published_at?->translatedFormat('d F Y') }}</p>
    </div>
</x-layouts.app>

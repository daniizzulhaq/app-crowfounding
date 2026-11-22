<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $report->period_label }} — {{ $settings->foundationName() }}</title>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #16211C; }
        .kop { border-bottom: 3px solid #0E7A5A; padding-bottom: 10px; margin-bottom: 16px; }
        .kop td { vertical-align: middle; }
        .brand { font-size: 18px; font-weight: bold; color: #0E7A5A; }
        .muted { color: #65736C; }
        h1 { font-size: 14px; margin: 0 0 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #DFE5E1; padding: 6px 8px; }
        table.data th { background: #E2F1EA; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .total td { font-weight: bold; background: #F5F7F4; }
        .sign { margin-top: 36px; width: 100%; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 9px; color: #65736C; text-align: center; }
    </style>
</head>
<body>
    <table class="kop" width="100%">
        <tr>
            <td width="64">
                @if (is_file($settings->logoPath()))
                    <img src="{{ $settings->logoPath() }}" width="56" height="56" alt="">
                @endif
            </td>
            <td>
                <div class="brand">{{ $settings->foundationName() }}</div>
                @if ($settings->get('address'))<div class="muted">{{ $settings->get('address') }}</div>@endif
                <div class="muted">
                    @if ($settings->get('pub_permit_number'))Izin PUB No. {{ $settings->get('pub_permit_number') }} · @endif
                    {{ $settings->get('phone') }} {{ $settings->get('email') }}
                </div>
            </td>
        </tr>
    </table>

    <h1>Laporan Keuangan Bulanan — {{ $report->period_label }}</h1>
    <p class="muted">Dibuat {{ $report->generated_at?->translatedFormat('d F Y H:i') }} · Biaya operasional {{ $report->operational_fee_percent }}% dari dana masuk</p>

    <table class="data">
        <tr><th>Uraian</th><th class="num">Jumlah</th></tr>
        <tr><td>Saldo awal</td><td class="num">{{ rupiah($report->opening_balance) }}</td></tr>
        <tr><td>Dana masuk ({{ number_format($report->donation_count, 0, ',', '.') }} donasi)</td><td class="num">{{ rupiah($report->total_in) }}</td></tr>
        <tr><td>Biaya operasional ({{ $report->operational_fee_percent }}%)</td><td class="num">({{ rupiah($report->total_operational) }})</td></tr>
        <tr><td>Dana disalurkan</td><td class="num">({{ rupiah($report->total_distributed) }})</td></tr>
        <tr class="total"><td>Saldo akhir</td><td class="num">{{ rupiah($report->closing_balance) }}</td></tr>
    </table>

    <h1 style="margin-top: 18px;">Rincian per campaign</h1>
    <table class="data">
        <tr><th>Campaign</th><th class="num">Donasi</th><th class="num">Dana masuk</th><th class="num">Disalurkan</th></tr>
        @forelse ($report->breakdown ?? [] as $line)
            <tr>
                <td>{{ $line['title'] }}</td>
                <td class="num">{{ number_format($line['donations'], 0, ',', '.') }}</td>
                <td class="num">{{ rupiah($line['in']) }}</td>
                <td class="num">{{ rupiah($line['distributed']) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">Tidak ada transaksi pada periode ini.</td></tr>
        @endforelse
    </table>

    <h1 style="margin-top: 18px;">Daftar donatur</h1>
    <p class="muted">Donatur yang menyembunyikan nama ditulis “Hamba Allah”. Nomor kontak tidak dicantumkan.</p>
    <table class="data">
        <tr><th width="24">No</th><th width="70">Tanggal</th><th>Nama</th><th>Campaign</th><th class="num">Nominal</th></tr>
        @forelse ($report->publicDonations()->lazy(200) as $donation)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $donation->paid_at?->format('d/m/Y') }}</td>
                <td>{{ $donation->display_name }}</td>
                <td>{{ $donation->campaign?->title ?? 'Sedekah umum' }}</td>
                <td class="num">{{ rupiah($donation->amount) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">Tidak ada donasi pada periode ini.</td></tr>
        @endforelse
    </table>

    <table class="sign">
        <tr>
            <td width="60%"></td>
            <td>
                {{ now()->translatedFormat('d F Y') }}<br>
                Pengurus {{ $settings->foundationName() }}<br><br><br><br>
                ______________________
            </td>
        </tr>
    </table>

    <div class="footer">{{ $settings->foundationName() }} · Laporan ini dihasilkan otomatis dari sistem dan dapat diverifikasi di {{ config('app.url') }}/laporan</div>
</body>
</html>

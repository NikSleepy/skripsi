<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report['title'] }}</title>

    @vite(['resources/css/app.css'])

    <style>
        @page {
            size: A4 {{ $report['landscape'] ? 'landscape' : 'portrait' }};
            margin: 12mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .sheet {
                margin: 0 !important;
                max-width: none !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .report-table {
                font-size: 10px;
            }
        }
    </style>
</head>

<body class="min-h-full bg-slate-100 text-slate-700 antialiased">
    {{-- Bilah alat tidak ikut tercetak --}}
    <div class="no-print sticky top-0 z-10 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex w-full max-w-5xl flex-wrap items-center gap-2 px-4 py-3">
            <a href="{{ route('reports.exports.index', request()->query()) }}" class="btn btn-ghost btn-sm">
                <x-icon name="chevron-right" class="h-4 w-4 rotate-180" />
                Ganti laporan
            </a>
            <p class="text-xs text-slate-500">{{ $report['title'] }} • {{ $report['subtitle'] }}</p>

            <div class="ml-auto flex flex-wrap items-center gap-2">
                <a href="{{ route('reports.exports.download', request()->query()) }}" class="btn btn-outline btn-sm">
                    <x-icon name="download" class="h-4 w-4" />
                    Unduh Excel/CSV
                </a>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                    <x-icon name="table" class="h-4 w-4" />
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>
    </div>

    <div class="sheet mx-auto my-6 w-full max-w-5xl rounded-xl bg-white p-8 shadow-card ring-1 ring-slate-200">
        <header class="border-b border-slate-200 pb-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">
                {{ config('app.name', 'Dashboard Siswa') }} — Sekolah Dasar
            </p>
            <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">{{ $report['title'] }}</h1>
            <p class="mt-0.5 text-sm text-slate-500">{{ $report['subtitle'] }}</p>
        </header>

        @if ($report['meta'] !== [])
            <dl class="mt-4 grid gap-x-8 gap-y-2 text-xs sm:grid-cols-3">
                @foreach ($report['meta'] as $meta)
                    <div class="flex gap-2">
                        <dt class="w-32 shrink-0 text-slate-500">{{ $meta['label'] }}</dt>
                        <dd class="font-medium text-slate-800">: {{ $meta['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif

        @if ($report['columns'] === [] || $report['rows'] === [])
            <div class="mt-6 rounded-lg bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-inset ring-slate-200">
                Belum ada data yang dapat ditampilkan untuk laporan ini.
            </div>
        @else
            <div class="mt-6 overflow-x-auto">
                <table class="report-table w-full border-collapse text-xs">
                    <thead>
                        <tr>
                            @foreach ($report['columns'] as $column)
                                <th class="border border-slate-300 bg-slate-50 px-2 py-1 text-left font-semibold text-slate-700">
                                    {{ $column }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['rows'] as $index => $row)
                            <tr class="{{ $index === count($report['rows']) - 1 && $report['type'] !== 'ranking' ? 'bg-slate-50 font-semibold' : '' }}">
                                @foreach ($row as $cell)
                                    <td class="border border-slate-300 px-2 py-1">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($report['notes'] !== [])
            <div class="mt-4 text-xs leading-relaxed text-slate-500">
                <p class="font-semibold uppercase tracking-wide text-slate-500">Catatan</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach ($report['notes'] as $note)
                        <li>{{ $note }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($report['signatures'] !== [])
            <div class="mt-10 flex flex-wrap justify-between gap-8 text-xs">
                @foreach ($report['signatures'] as $index => $signature)
                    <div class="{{ $index === 0 ? 'order-2' : 'order-1' }} min-w-[14rem] text-center">
                        <p class="text-slate-500">{{ $signature['label'] }}</p>
                        <div class="h-16"></div>
                        <p class="border-t border-slate-400 pt-1 font-semibold text-slate-800">{{ $signature['name'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>

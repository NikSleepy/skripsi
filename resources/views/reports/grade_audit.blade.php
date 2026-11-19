<x-app-layout title="Audit Nilai">
    <x-page-header title="Audit Nilai"
                   subtitle="Jejak perubahan nilai: siapa yang mengubah, kapan, beserta nilai lama dan nilai barunya.">
        <x-badge tone="neutral">{{ $filters['from'] }} → {{ $filters['to'] }}</x-badge>
        <a href="{{ route('reports.grade-audit') }}" class="btn btn-ghost btn-sm">30 hari terakhir</a>
    </x-page-header>

    <form method="GET" action="{{ route('reports.grade-audit') }}" class="card mb-6 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-input-label for="from" value="Dari tanggal" />
                <x-text-input id="from" name="from" type="date" class="mt-1" :value="$filters['from']" />
            </div>
            <div>
                <x-input-label for="to" value="Sampai tanggal" />
                <x-text-input id="to" name="to" type="date" class="mt-1" :value="$filters['to']" />
            </div>
            <div>
                <x-input-label for="class" value="Kelas" />
                <select id="class" name="class" class="input-base mt-1">
                    <option value="">Semua kelas</option>
                    @foreach ($classes as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) $filters['class'] === (string) $classroom->id)>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="subject" value="Mata pelajaran" />
                <select id="subject" name="subject" class="input-base mt-1">
                    <option value="">Semua mapel</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected((string) $filters['subject'] === (string) $subject->id)>
                            {{ $subject->code }} — {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="user" value="Pengubah" />
                <select id="user" name="user" class="input-base mt-1">
                    <option value="">Semua pengguna</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) $filters['user'] === (string) $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="action" value="Jenis aksi" />
                <select id="action" name="action" class="input-base mt-1">
                    <option value="">Semua aksi</option>
                    <option value="created" @selected($filters['action'] === 'created')>Input nilai baru</option>
                    <option value="updated" @selected($filters['action'] === 'updated')>Perubahan nilai</option>
                    <option value="submitted" @selected($filters['action'] === 'submitted')>Penyerahan nilai</option>
                    <option value="locked" @selected($filters['action'] === 'locked')>Penguncian nilai</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <x-input-label for="search" value="Cari siswa" />
                <div class="relative mt-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <x-text-input id="search" name="search" class="pl-9" :value="$filters['search']"
                                  placeholder="Nama atau NIS siswa" />
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="submit" class="btn btn-secondary btn-sm">
                <x-icon name="filter" class="h-4 w-4" />
                Terapkan filter
            </button>
            <a href="{{ route('reports.grade-audit') }}" class="btn btn-ghost btn-sm">Reset</a>
            <p class="ml-auto text-xs text-slate-500">
                Menampilkan {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} catatan
            </p>
        </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Catatan Perubahan" :value="$stats['logs']" icon="shield" tone="brand"
                     hint="Baris log pada rentang tanggal terpilih" />
        <x-stat-card label="Nilai Terdampak" :value="$stats['grades']" icon="clipboard" tone="info"
                     hint="Nilai yang pernah dibuat atau diubah" />
        <x-stat-card label="Pengubah" :value="$stats['users']" icon="users" tone="warning"
                     hint="Akun yang tercatat mengubah nilai" />
        <x-stat-card label="Siswa Terdampak" :value="$stats['students']" icon="user" tone="success"
                     hint="Siswa yang nilainya tercatat pada log" />
    </div>

    @if ($rows->isEmpty())
        <section class="card mt-6">
            @if ($filters['search'] !== '' || filled($filters['class']) || filled($filters['subject']) || filled($filters['user']) || filled($filters['action']))
                <x-empty-state icon="search" title="Tidak ada catatan yang cocok"
                               description="Coba ubah filter atau perlebar rentang tanggal untuk melihat catatan lainnya.">
                    <a href="{{ route('reports.grade-audit') }}" class="btn btn-outline btn-sm mt-2">Reset filter</a>
                </x-empty-state>
            @else
                <x-empty-state icon="shield" title="Belum ada catatan perubahan nilai"
                               description="Setiap input dan perubahan nilai akan tercatat otomatis pada halaman ini." />
            @endif
        </section>
    @else
        <section class="card mt-6 hidden lg:block">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead class="table-head">
                        <tr>
                            <th class="table-head-cell">Waktu</th>
                            <th class="table-head-cell">Siswa</th>
                            <th class="table-head-cell">Kelas &amp; mapel</th>
                            <th class="table-head-cell">Aksi</th>
                            <th class="table-head-cell">Perubahan</th>
                            <th class="table-head-cell">Pengubah</th>
                            <th class="table-head-cell text-right">Rincian</th>
                        </tr>
                    </thead>

                    @foreach ($rows as $row)
                        <tbody x-data="{ open: false }" class="divide-y divide-slate-100">
                            <tr class="table-row">
                                <td class="table-cell whitespace-nowrap">{{ $row->changed_at_label }}</td>
                                <td class="table-cell">
                                    <p class="font-medium text-slate-800">{{ $row->log->full_name }}</p>
                                    <p class="text-xs text-slate-500">NIS {{ $row->log->nis }}</p>
                                </td>
                                <td class="table-cell">
                                    <span class="font-medium text-slate-700">{{ $row->log->class_name }}</span>
                                    <span class="block text-xs text-slate-500">
                                        {{ $row->log->subject_code }} — {{ $row->log->subject_name }}
                                    </span>
                                </td>
                                <td class="table-cell">
                                    @switch($row->log->action)
                                        @case('created')
                                            <x-badge tone="brand">Input baru</x-badge>
                                            @break
                                        @case('submitted')
                                            <x-badge tone="info">Diserahkan</x-badge>
                                            @break
                                        @case('locked')
                                            <x-badge tone="neutral">Dikunci</x-badge>
                                            @break
                                        @default
                                            <x-badge tone="warning">Perubahan</x-badge>
                                    @endswitch
                                </td>
                                <td class="table-cell">
                                    @include('reports.partials.grade-audit-changes', ['changes' => $row->changes])
                                </td>
                                <td class="table-cell">{{ $row->log->changed_by_name ?? 'Akun dihapus' }}</td>
                                <td class="table-cell text-right">
                                    <button type="button" class="btn btn-ghost btn-sm" @click="open = !open"
                                            aria-label="Buka rincian perubahan">
                                        <span x-text="open ? 'Tutup' : 'Lihat'"></span>
                                    </button>
                                </td>
                            </tr>

                            <tr x-show="open" x-cloak>
                                <td colspan="7" class="bg-slate-50 px-5 py-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nilai lama</p>
                                            @if ($row->old_pretty)
                                                <pre class="mt-1 overflow-x-auto rounded-lg bg-white p-3 text-xs text-slate-600 ring-1 ring-inset ring-slate-200">{{ $row->old_pretty }}</pre>
                                            @else
                                                <p class="mt-1 text-xs text-slate-500">Belum ada nilai sebelumnya.</p>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nilai baru</p>
                                            @if ($row->new_pretty)
                                                <pre class="mt-1 overflow-x-auto rounded-lg bg-white p-3 text-xs text-slate-600 ring-1 ring-inset ring-slate-200">{{ $row->new_pretty }}</pre>
                                            @else
                                                <p class="mt-1 text-xs text-slate-500">Tidak ada nilai baru pada catatan ini.</p>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs text-slate-500">
                                        Catatan: {{ $row->log->reason ?: 'Tanpa keterangan' }} • ID log #{{ $row->log->id }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $logs->links() }}</div>
            @endif
        </section>

        <div class="mt-6 space-y-3 lg:hidden">
            @foreach ($rows as $row)
                <section class="card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ $row->log->full_name }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $row->log->class_name }} • {{ $row->log->subject_code }} • {{ $row->changed_at_label }}
                            </p>
                        </div>
                        @switch($row->log->action)
                            @case('created')
                                <x-badge tone="brand">Input baru</x-badge>
                                @break
                            @case('submitted')
                                <x-badge tone="info">Diserahkan</x-badge>
                                @break
                            @case('locked')
                                <x-badge tone="neutral">Dikunci</x-badge>
                                @break
                            @default
                                <x-badge tone="warning">Perubahan</x-badge>
                        @endswitch
                    </div>

                    <div class="mt-3">
                        @include('reports.partials.grade-audit-changes', ['changes' => $row->changes])
                    </div>

                    <p class="mt-3 text-xs text-slate-500">
                        Oleh: {{ $row->log->changed_by_name ?? 'Akun dihapus' }} •
                        {{ $row->log->reason ?: 'Tanpa keterangan' }}
                    </p>
                </section>
            @endforeach

            @if ($logs->hasPages())
                <div class="pt-2">{{ $logs->links() }}</div>
            @endif
        </div>
    @endif
</x-app-layout>

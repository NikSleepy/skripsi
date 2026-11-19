<x-app-layout title="Data Kelas">
    <x-page-header title="Data Kelas" :subtitle="'Total '.$totalClassrooms.' kelas terdaftar.'">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        <a href="{{ route('admin.classrooms.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tambah kelas
        </a>
    </x-page-header>

    <form method="GET" action="{{ route('admin.classrooms.index') }}" class="card mb-6 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <x-input-label for="search" value="Cari kelas" />
                <div class="relative mt-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <x-text-input id="search" name="search" class="pl-9" :value="$filters['search']"
                                  placeholder="Nama kelas, misalnya 1A" />
                </div>
            </div>

            <div>
                <x-input-label for="grade_level" value="Tingkat" />
                <select id="grade_level" name="grade_level" class="input-base mt-1">
                    <option value="">Semua tingkat</option>
                    @foreach ($gradeLevels as $level)
                        <option value="{{ $level }}" @selected((string) $filters['grade_level'] === (string) $level)>
                            Tingkat {{ $level }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="submit" class="btn btn-secondary btn-sm">
                <x-icon name="filter" class="h-4 w-4" />
                Terapkan filter
            </button>
            <a href="{{ route('admin.classrooms.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            <p class="ml-auto text-xs text-slate-500">
                Menampilkan {{ $classrooms->firstItem() ?? 0 }}–{{ $classrooms->lastItem() ?? 0 }}
                dari {{ $classrooms->total() }} kelas
            </p>
        </div>
    </form>

    @if ($classrooms->isEmpty())
        <section class="card">
            @if ($filters['search'] !== '' || filled($filters['grade_level']))
                <x-empty-state icon="search" title="Tidak ada kelas yang cocok"
                               description="Coba ubah kata kunci atau reset filter untuk melihat seluruh data.">
                    <a href="{{ route('admin.classrooms.index') }}" class="btn btn-outline btn-sm mt-2">Reset filter</a>
                </x-empty-state>
            @else
                <x-empty-state icon="layers" title="Belum ada data kelas"
                               description="Tambahkan kelas terlebih dahulu sebelum menempatkan siswa dan menyusun penugasan mengajar.">
                    <a href="{{ route('admin.classrooms.create') }}" class="btn btn-primary btn-sm mt-2">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tambah kelas
                    </a>
                </x-empty-state>
            @endif
        </section>
    @else
        {{-- Tabel untuk layar besar --}}
        <section class="card hidden lg:block">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead class="table-head">
                        <tr>
                            <th class="table-head-cell">Kelas</th>
                            <th class="table-head-cell">Wali kelas</th>
                            <th class="table-head-cell">Siswa aktif</th>
                            <th class="table-head-cell">Mapel</th>
                            <th class="table-head-cell text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($classrooms as $classroom)
                            @php
                                $homeroomNames = $classroom->enrollments
                                    ->pluck('homeroomTeacher')->filter()->unique('id')->pluck('full_name');
                                $activeCount = $classroom->enrollments->where('status', 'aktif')->count();
                                $otherCount = $classroom->enrollments->count() - $activeCount;
                                $subjectCount = $classroom->classSubjects->pluck('subject_id')->unique()->count();
                            @endphp
                            <tr class="table-row">
                                <td class="table-cell">
                                    <div class="flex items-center gap-3">
                                        <span class="avatar">{{ $classroom->name }}</span>
                                        <div>
                                            <p class="font-medium text-slate-800">{{ $classroom->name }}</p>
                                            <p class="text-xs text-slate-500">Tingkat {{ $classroom->grade_level }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    @if ($homeroomNames->isNotEmpty())
                                        <x-badge tone="brand">{{ $homeroomNames->implode(', ') }}</x-badge>
                                    @else
                                        <span class="text-xs text-slate-400">Belum ditetapkan</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    @if ($classroom->enrollments->isEmpty())
                                        <span class="text-xs text-slate-400">Belum ada siswa</span>
                                    @else
                                        <span class="font-medium text-slate-800">{{ $activeCount }} siswa</span>
                                        @if ($otherCount > 0)
                                            <span class="block text-xs text-slate-500">{{ $otherCount }} tidak aktif</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="table-cell">
                                    @if ($subjectCount > 0)
                                        <span class="font-medium text-slate-800">{{ $subjectCount }} mapel</span>
                                    @else
                                        <span class="text-xs text-slate-400">Belum ada</span>
                                    @endif
                                </td>
                                <td class="table-cell text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.classrooms.show', $classroom) }}"
                                           class="btn btn-ghost btn-sm">Detail</a>
                                        <a href="{{ route('admin.classrooms.edit', $classroom) }}"
                                           class="btn btn-ghost btn-sm">Ubah</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($classrooms->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $classrooms->links() }}</div>
            @endif
        </section>

        {{-- Kartu untuk layar kecil --}}
        <div class="space-y-3 lg:hidden">
            @foreach ($classrooms as $classroom)
                @php
                    $homeroomNames = $classroom->enrollments
                        ->pluck('homeroomTeacher')->filter()->unique('id')->pluck('full_name');
                    $activeCount = $classroom->enrollments->where('status', 'aktif')->count();
                    $subjectCount = $classroom->classSubjects->pluck('subject_id')->unique()->count();
                @endphp
                <section class="card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">Kelas {{ $classroom->name }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">Tingkat {{ $classroom->grade_level }}</p>
                        </div>
                        <x-badge tone="neutral">{{ $activeCount }} siswa</x-badge>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <dt class="text-slate-500">Wali kelas</dt>
                            <dd class="font-medium text-slate-800">{{ $homeroomNames->implode(', ') ?: 'Belum ditetapkan' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Mapel</dt>
                            <dd class="font-medium text-slate-800">{{ $subjectCount > 0 ? $subjectCount.' mapel' : 'Belum ada' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('admin.classrooms.show', $classroom) }}" class="btn btn-outline btn-sm">Detail</a>
                        <a href="{{ route('admin.classrooms.edit', $classroom) }}" class="btn btn-outline btn-sm">Ubah</a>
                    </div>
                </section>
            @endforeach

            @if ($classrooms->hasPages())
                <div class="pt-2">{{ $classrooms->links() }}</div>
            @endif
        </div>
    @endif
</x-app-layout>

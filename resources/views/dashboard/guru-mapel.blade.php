<x-app-layout title="Dashboard Guru Mapel">
    @php
        $periodLabel = $semester
            ? 'Semester '.$semester->name.' • TA '.$schoolYear?->name
            : 'Belum ada periode aktif';

        $classCount = $assignments->pluck('class_id')->unique()->count();
        $studentSlots = $assignments->sum('student_count');
        $graded = $assignments->sum('graded_count');
        $progress = $studentSlots > 0 ? round($graded / $studentSlots * 100) : 0;
    @endphp

    @if ($teacherId === null)
        <x-page-header title="Dashboard Guru Mapel" :subtitle="$periodLabel" />

        <x-empty-state icon="building" title="Akun Anda belum terhubung ke profil guru"
                       description="Minta admin menghubungkan akun ini dengan data guru agar penugasan mengajar bisa ditampilkan." />
    @else
        <x-page-header title="Penugasan Mengajar" :subtitle="$periodLabel">
            <x-badge tone="neutral">{{ $classCount }} kelas • {{ $assignments->count() }} penugasan</x-badge>
        </x-page-header>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Kelas Diampu" :value="$classCount" icon="layers" tone="brand" />
            <x-stat-card label="Penugasan Mapel" :value="$assignments->count()" icon="book" tone="info" />
            <x-stat-card label="Nilai Sudah Diinput" :value="$graded.' / '.$studentSlots" icon="clipboard" tone="success"
                         :hint="$progress.'% dari seluruh siswa'" />
            <x-stat-card label="Belum Diserahkan" :value="$gradeStatus['draft']" icon="alert-triangle" tone="warning"
                         hint="Masih berstatus draft" />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <section class="card xl:col-span-2">
                <div class="card-header">
                    <div>
                        <p class="card-title">Daftar Penugasan</p>
                        <p class="card-subtitle">Progres pengisian nilai untuk semester berjalan</p>
                    </div>
                    <a href="{{ route('grades.index') }}" class="btn btn-outline btn-sm">
                        <x-icon name="clipboard" class="h-4 w-4" />
                        Input nilai
                    </a>
                </div>

                @if ($assignments->isEmpty())
                    <x-empty-state icon="book" title="Belum ada penugasan pada tahun ajaran ini"
                                   description="Penugasan mapel dibuat admin melalui menu Class Subjects." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-head-cell">Kelas</th>
                                    <th class="table-head-cell">Mata pelajaran</th>
                                    <th class="table-head-cell">KKM</th>
                                    <th class="table-head-cell">Progres nilai</th>
                                    <th class="table-head-cell">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assignments as $assignment)
                                    <tr class="table-row">
                                        <td class="table-cell font-semibold text-slate-800">{{ $assignment->class_name }}</td>
                                        <td class="table-cell">
                                            <span class="font-medium text-slate-700">{{ $assignment->subject_code }}</span>
                                            <span class="block text-xs text-slate-500">{{ $assignment->subject_name }}</span>
                                        </td>
                                        <td class="table-cell">{{ number_format((float) $assignment->kkm, 2, ',', '.') }}</td>
                                        <td class="table-cell">
                                            <div class="flex items-center gap-2">
                                                <div class="progress-track w-24">
                                                    <div class="progress-bar {{ $assignment->progress === 100 ? 'bg-emerald-500' : 'bg-brand-500' }}"
                                                         style="width: {{ $assignment->progress }}%"></div>
                                                </div>
                                                <span class="text-xs text-slate-500">
                                                    {{ $assignment->graded_count }}/{{ $assignment->student_count }} siswa
                                                </span>
                                            </div>
                                        </td>
                                        <td class="table-cell">
                                            <a href="{{ route('grades.edit', $assignment->id) }}" class="btn btn-ghost btn-sm">
                                                Input nilai
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <div class="space-y-6">
                <x-grade-status-card :summary="$gradeStatus" />

                @if ($homeroomClasses->isNotEmpty())
                    <section class="card">
                        <div class="card-header">
                            <div>
                                <p class="card-title">Sebagai Wali Kelas</p>
                                <p class="card-subtitle">Anda juga membina kelas berikut</p>
                            </div>
                        </div>
                        <div class="card-body space-y-2">
                            @foreach ($homeroomClasses as $homeroom)
                                <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                                    <span class="text-sm font-medium text-slate-700">Kelas {{ $homeroom->name }}</span>
                                    <x-badge tone="success">Wali kelas</x-badge>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </div>
    @endif
</x-app-layout>

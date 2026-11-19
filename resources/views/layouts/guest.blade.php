@props(['title' => null])

<!DOCTYPE html>
<html lang="id" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Masuk' }} — {{ config('app.name', 'Dashboard Siswa') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="min-h-full bg-slate-50 font-sans antialiased">
        <div class="flex min-h-screen flex-col lg:flex-row">
            <aside class="relative hidden flex-1 flex-col justify-between bg-brand-700 p-10 text-white lg:flex">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
                        <x-icon name="building" class="h-5 w-5" />
                    </span>
                    <div class="leading-tight">
                        <p class="text-sm font-semibold">Dashboard Siswa</p>
                        <p class="text-xs text-brand-100">Sekolah Dasar</p>
                    </div>
                </div>

                <div class="max-w-md">
                    <h2 class="text-2xl font-semibold leading-snug">
                        Satu tempat untuk nilai, absensi, dan rekomendasi beasiswa siswa.
                    </h2>
                    <ul class="mt-6 space-y-3 text-sm text-brand-50">
                        <li class="flex items-start gap-2">
                            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                            Nilai tugas, UTS, dan UAS dengan penanda KKM otomatis.
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                            Absensi harian dan rekap kehadiran per kelas.
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                            Rekomendasi beasiswa dan audit perubahan nilai.
                        </li>
                    </ul>
                </div>

                <p class="text-xs text-brand-100">
                    Data siswa bersifat rahasia. Akses diberikan sesuai peran akun Anda.
                </p>
            </aside>

            <main class="flex flex-1 items-center justify-center px-4 py-10 sm:px-8">
                <div class="w-full max-w-md">
                    <div class="mb-6 flex items-center gap-3 lg:hidden">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                            <x-icon name="building" class="h-5 w-5" />
                        </span>
                        <div class="leading-tight">
                            <p class="text-sm font-semibold text-slate-900">Dashboard Siswa</p>
                            <p class="text-xs text-slate-500">Sekolah Dasar</p>
                        </div>
                    </div>

                    <div class="card p-6 sm:p-8">
                        <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? 'Masuk ke akun' }}</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Gunakan email dan password yang diberikan admin sekolah.
                        </p>

                        <div class="mt-6">
                            {{ $slot }}
                        </div>
                    </div>

                    <p class="mt-6 text-center text-xs text-slate-400">
                        Butuh bantuan? Hubungi admin sekolah untuk reset password.
                    </p>
                </div>
            </main>
        </div>
    </body>
</html>

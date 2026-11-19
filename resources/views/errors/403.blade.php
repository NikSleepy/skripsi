<!DOCTYPE html>
<html lang="id" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>403 — Akses ditolak</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="flex min-h-full items-center justify-center bg-slate-50 px-4 font-sans antialiased">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-card ring-1 ring-slate-200">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                <x-icon name="shield" class="h-7 w-7" />
            </span>

            <p class="mt-4 text-sm font-semibold uppercase tracking-wide text-rose-500">Error 403</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-900">Akses ditolak</h1>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                {{ $exception?->getMessage() ?: 'Akun Anda tidak memiliki kewenangan untuk membuka halaman ini.' }}
            </p>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">
                        <x-icon name="home" class="h-4 w-4" />
                        Kembali ke dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Masuk ke aplikasi</a>
                @endauth
            </div>

            <p class="mt-6 text-xs text-slate-400">
                Butuh akses? Hubungi admin sekolah untuk menyesuaikan role akun Anda.
            </p>
        </div>
    </body>
</html>

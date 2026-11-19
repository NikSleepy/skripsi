@props(['title' => null])

@php
    $user = auth()->user();
    $role = $user?->role;
    $menu = $role ? \App\Support\Navigation::for($role) : [];
    $pageTitle = $title ?: 'Dashboard';
    $academicPeriod = \App\Support\AcademicPeriod::label();
@endphp

<!DOCTYPE html>
<html lang="id" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle }} — {{ config('app.name', 'Dashboard Siswa') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="min-h-full bg-slate-50 font-sans antialiased" x-data="{ sidebar: false, userMenu: false }">
        {{-- Sidebar desktop, berubah jadi drawer pada layar kecil --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-out lg:translate-x-0"
            :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-100 px-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white shadow-sidebar">
                    <x-icon name="building" class="h-5 w-5" />
                </span>
                <div class="min-w-0 leading-tight">
                    <p class="truncate text-sm font-semibold text-slate-900">Dashboard Siswa</p>
                    <p class="truncate text-[11px] text-slate-500">Sekolah Dasar</p>
                </div>
                <button type="button" class="ml-auto rounded-lg p-1 text-slate-400 hover:bg-slate-100 lg:hidden"
                        @click="sidebar = false" aria-label="Tutup menu">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
                @foreach ($menu as $group => $items)
                    <div>
                        <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $group }}</p>
                        <ul class="space-y-1">
                            @foreach ($items as $item)
                                <li>
                                    @if (! empty($item['soon']))
                                        <span class="sidebar-link sidebar-link-disabled" title="Belum tersedia pada tahap ini">
                                            <x-icon :name="$item['icon']" class="h-[18px] w-[18px]" />
                                            <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                            <span class="chip-soon">Segera</span>
                                        </span>
                                    @else
                                        <a href="{{ route($item['route']) }}"
                                           class="sidebar-link @if (request()->routeIs($item['active'])) sidebar-link-active @endif">
                                            <x-icon :name="$item['icon']" class="h-[18px] w-[18px]" />
                                            <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>

            <div class="shrink-0 border-t border-slate-100 p-4">
                <div class="flex items-center gap-3">
                    <span class="avatar">{{ $user?->initials() }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900">{{ $user?->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $role?->label() }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm w-full">
                        <x-icon name="logout" class="h-4 w-4" />
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden"
             @click="sidebar = false"></div>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/80 backdrop-blur">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button type="button" class="btn btn-ghost btn-sm -ml-1 lg:hidden" @click="sidebar = true"
                            aria-label="Buka menu">
                        <x-icon name="menu" class="h-5 w-5" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <h1 class="truncate text-base font-semibold text-slate-900">{{ $pageTitle }}</h1>
                        <p class="truncate text-xs text-slate-500">{{ $academicPeriod }}</p>
                    </div>

                    @if ($role)
                        <span class="badge {{ $role->badge() }} hidden sm:inline-flex">{{ $role->label() }}</span>
                    @endif

                    <div class="relative" @click.outside="userMenu = false">
                        <button type="button" @click="userMenu = !userMenu"
                                class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-slate-100">
                            <span class="avatar h-8 w-8 text-xs">{{ $user?->initials() }}</span>
                            <x-icon name="chevron-down" class="h-4 w-4 text-slate-400" />
                        </button>

                        <div x-show="userMenu" x-transition style="display: none"
                             class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $user?->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user?->email }}</p>
                            </div>
                            <div class="p-1">
                                <a href="{{ route('profile.edit') }}"
                                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                    <x-icon name="user" class="h-4 w-4" />
                                    Profil & Password
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">
                                        <x-icon name="logout" class="h-4 w-4" />
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl px-4 pb-28 pt-6 sm:px-6 lg:px-8 lg:pb-12">
                <x-flash />
                {{ $slot }}
            </main>
        </div>

        {{-- Navigasi bawah khusus layar kecil --}}
        <nav class="fixed inset-x-0 bottom-0 z-30 flex border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden">
            <a href="{{ route('dashboard') }}"
               class="bottom-nav-link @if (request()->routeIs('dashboard')) bottom-nav-link-active @endif">
                <x-icon name="home" class="h-5 w-5" />
                Dashboard
            </a>
            <a href="{{ route('profile.edit') }}"
               class="bottom-nav-link @if (request()->routeIs('profile.*')) bottom-nav-link-active @endif">
                <x-icon name="user" class="h-5 w-5" />
                Profil
            </a>
            <button type="button" class="bottom-nav-link" @click="sidebar = true">
                <x-icon name="menu" class="h-5 w-5" />
                Menu
            </button>
        </nav>
    </body>
</html>

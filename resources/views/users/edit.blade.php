<x-app-layout :title="'Ubah: '.$user->name">
    <x-page-header :title="'Ubah: '.$user->name" :subtitle="$user->email">
        <x-badge :tone="$user->is_active ? 'success' : 'danger'">
            {{ $user->is_active ? 'Akun aktif' : 'Akun nonaktif' }}
        </x-badge>
        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline btn-sm">
            <x-icon name="search" class="h-4 w-4" />
            Lihat detail
        </a>
    </x-page-header>

    @include('users.partials.form')
</x-app-layout>

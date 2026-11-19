<x-app-layout title="Profil & Password">
    <x-page-header title="Profil & Password" subtitle="Kelola data akun dan keamanan akun Anda." />

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card">
            <div class="card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section class="card">
            <div class="card-body">
                @include('profile.partials.update-password-form')
            </div>
        </section>

        <section class="card ring-rose-200 lg:col-span-2">
            <div class="card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </section>
    </div>
</x-app-layout>

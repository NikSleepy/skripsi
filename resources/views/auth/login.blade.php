<x-guest-layout title="Masuk ke akun">
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email')" required autofocus autocomplete="username"
                          placeholder="nama@sekolah.test" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500">
                <span class="ms-2 text-sm text-slate-600">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
                    Lupa password?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full">Masuk</x-primary-button>
    </form>

    <div class="mt-6 rounded-xl bg-slate-50 p-4 text-xs leading-relaxed text-slate-500 ring-1 ring-inset ring-slate-200">
        <p class="font-semibold text-slate-600">Akun demo (password: password)</p>
        <ul class="mt-2 space-y-1">
            <li>Admin — <span class="font-medium text-slate-600">admin@sekolah.test</span></li>
            <li>Kepala Sekolah — <span class="font-medium text-slate-600">kepsek@sekolah.test</span></li>
            <li>Wali Kelas — <span class="font-medium text-slate-600">siti.aminah@sekolah.test</span></li>
            <li>Guru Mapel — <span class="font-medium text-slate-600">agus.setiawan@sekolah.test</span></li>
            <li>Orang Tua — <span class="font-medium text-slate-600">sutrisno@orangtua.test</span></li>
        </ul>
    </div>
</x-guest-layout>

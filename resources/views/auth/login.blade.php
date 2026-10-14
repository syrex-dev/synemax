<x-guest-layout>
    <h1 class="text-2xl font-black text-text">Masuk admin</h1>
    <p class="mt-2 text-base text-text-muted">Kelola jadwal, film, dan booking Home Cinema.</p>
    <x-auth-session-status class="mt-4 text-base text-success" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6">
        @csrf
        <div>
            <x-input-label for="email" value="Email admin" class="text-text" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Kata sandi" class="text-text" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="mt-4 inline-flex min-h-11 items-center gap-2 text-base text-text-muted">
            <input id="remember_me" type="checkbox" class="size-5 rounded border-border bg-bg text-accent focus:ring-accent" name="remember">
            <span>Ingat saya di perangkat ini</span>
        </label>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
            @if (Route::has('password.request'))
                <a class="min-h-11 content-center text-base text-accent underline decoration-border underline-offset-4 hover:text-text" href="{{ route('password.request') }}">Lupa kata sandi?</a>
            @endif
            <button class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-accent px-6 text-base font-bold text-accent-contrast hover:opacity-90 sm:w-auto">Masuk</button>
        </div>
    </form>
</x-guest-layout>

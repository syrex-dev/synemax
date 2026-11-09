<x-guest-layout>
    <div x-data="{ showPassword: false }">
        <h1 class="text-xl sm:text-2xl font-black text-text">Masuk admin</h1>
        <p class="mt-1 text-xs text-text-muted hidden min-[700px]:block">Kelola jadwal, film, dan booking SynemaX.</p>

        <x-auth-session-status class="mt-3 text-xs text-success" :status="session('status')" />

        {{-- Role alert error banner di atas form --}}
        @if ($errors->any())
            <div role="alert" class="mt-3 rounded-xl border border-danger/40 bg-danger-surface p-3 text-xs font-semibold text-danger-text">
                <p>{{ $errors->first('email') ?: ($errors->first('password') ?: 'Email atau kata sandi tidak cocok.') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-4 space-y-3.5" novalidate>
            @csrf
            <div>
                <label for="email" class="block text-xs font-bold text-text mb-1">Email admin</label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       inputmode="email"
                       autocomplete="username"
                       autocapitalize="none"
                       spellcheck="false"
                       enterkeyhint="next"
                       class="min-h-11 w-full rounded-xl border {{ $errors->has('email') ? 'border-danger' : 'border-border-input' }} bg-surface-2 px-3 text-sm text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400">
            </div>

            <div>
                <style>
                    /* Hilangkan ikon reveal password bawaan browser Edge / Chromium agar tidak dobel */
                    input[type="password"]::-ms-reveal,
                    input[type="password"]::-ms-clear,
                    #password::-ms-reveal,
                    #password::-ms-clear {
                        display: none !important;
                        width: 0 !important;
                        height: 0 !important;
                        pointer-events: none !important;
                    }
                </style>
                <label for="password" class="block text-xs font-bold text-text mb-1">Kata sandi</label>
                <div class="relative">
                    <input id="password"
                           x-ref="pwdInput"
                           :type="showPassword ? 'text' : 'password'"
                           name="password"
                           required
                           autocomplete="current-password"
                           autocapitalize="none"
                           spellcheck="false"
                           enterkeyhint="done"
                           class="min-h-11 w-full rounded-xl border {{ $errors->has('password') || $errors->any() ? 'border-danger' : 'border-border-input' }} bg-surface-2 pl-3 pr-11 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400 [&::-ms-reveal]:hidden [&::-ms-clear]:hidden">
                    <button type="button"
                            @click="showPassword = !showPassword; $refs.pwdInput.focus()"
                            :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            :aria-pressed="showPassword ? 'true' : 'false'"
                            class="absolute right-0 top-0 flex size-11 items-center justify-center text-text-muted hover:text-text focus:outline-none">
                        <svg x-show="!showPassword" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                <x-form.checkbox name="remember" id="remember_me" label="Ingat saya" />
                @if (config('mail.default') === 'smtp' && Route::has('password.request'))
                    <a class="text-xs font-semibold text-amber-700 dark:text-amber-400 hover:underline" href="{{ route('password.request') }}">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="btn-gold-cinema inline-flex min-h-12 w-full items-center justify-center rounded-xl text-sm font-bold shadow-none">
                    Masuk
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>

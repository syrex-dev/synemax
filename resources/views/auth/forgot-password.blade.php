<x-guest-layout>
    <h1 class="text-2xl font-black">Atur ulang kata sandi</h1>
    <p class="mt-2 text-base leading-6 text-text-muted">Masukkan email akun admin. Kami akan mengirimkan tautan untuk membuat kata sandi baru.</p>
    <x-auth-session-status class="mt-4 text-base text-success" :status="session('status')" />
    <form method="POST" action="{{ route('password.email') }}" class="mt-6">
        @csrf
        <label for="email" class="text-base font-semibold text-text">Email admin</label>
        <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
        <button class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-accent px-5 text-base font-bold text-accent-contrast hover:opacity-90">Kirim tautan reset</button>
    </form>
</x-guest-layout>

<x-guest-layout>
    <h1 class="text-2xl font-black">Konfirmasi kata sandi</h1>
    <p class="mt-2 text-base text-text-muted">Masukkan ulang kata sandi untuk melanjutkan ke bagian yang aman.</p>
    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6">
        @csrf
        <label for="password" class="text-base font-semibold text-text">Kata sandi</label>
        <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="current-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
        <button class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-accent px-5 text-base font-bold text-accent-contrast hover:opacity-90">Konfirmasi</button>
    </form>
</x-guest-layout>

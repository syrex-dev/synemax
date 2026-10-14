<x-guest-layout>
    <h1 class="text-2xl font-black">Buat kata sandi baru</h1>
    <form method="POST" action="{{ route('password.store') }}" class="mt-6">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <label for="email" class="text-sm font-semibold">Email admin</label>
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div class="mt-4">
            <label for="password" class="text-sm font-semibold">Kata sandi baru</label>
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="mt-4">
            <label for="password_confirmation" class="text-sm font-semibold">Ulangi kata sandi baru</label>
            <x-text-input id="password_confirmation" class="mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <button class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-accent px-5 text-base font-bold text-accent-contrast hover:opacity-90">Simpan kata sandi baru</button>
    </form>
</x-guest-layout>

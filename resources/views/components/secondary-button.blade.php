<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex min-h-11 items-center justify-center rounded-xl border border-border bg-surface px-4 py-2 text-sm font-semibold text-text shadow-token transition hover:bg-surface-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>

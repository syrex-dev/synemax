@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'min-h-12 rounded-xl border border-border bg-bg text-base text-text shadow-token placeholder:text-text-muted focus:border-accent focus:ring-accent disabled:cursor-not-allowed disabled:opacity-60']) }}>

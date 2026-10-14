@props(['tableClass' => ''])

<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="w-full table-fixed border-collapse text-left text-sm {{ $tableClass }}">
        @if (isset($columns))
            <colgroup>{{ $columns }}</colgroup>
        @endif
        <thead class="bg-surface-2 text-text-muted">
            <tr>{{ $head }}</tr>
        </thead>
        <tbody class="divide-y divide-border">
            {{ $slot }}
        </tbody>
    </table>
</div>

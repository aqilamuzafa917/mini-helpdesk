<x-layouts::app.sidebar :title="$title ?? null" >
    <head>
        <x-slot name="head">
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        </x-slot>
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>

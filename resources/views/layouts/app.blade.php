<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'medbridGe') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-med-black text-med-text min-h-screen">

        {{-- Ambient glow --}}
        <div class="fixed inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[800px] h-[500px] rounded-full bg-med-green opacity-[0.04] blur-3xl"></div>
        </div>

        {{-- Floating Nav --}}
        <nav class="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[calc(100%-2rem)] max-w-6xl">
            <div class="glass border border-med-border rounded-full px-2 py-2 shadow-2xl">
                <div class="flex items-center justify-between gap-2">

                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-1.5">
                        <div class="w-7 h-7 rounded-full bg-med-green flex items-center justify-center">
                            <span class="text-black font-extrabold text-sm">m</span>
                        </div>
                        <span class="text-sm font-bold text-med-text tracking-tight hidden sm:inline">
                            medbrid<span class="text-med-green">G</span>e
                        </span>
                    </a>

                    <div class="hidden md:flex items-center gap-1">
                        @php
                            $navLinks = [
                                ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard'],
                                ['label' => 'Uploads', 'route' => 'uploads.index', 'pattern' => 'uploads.*'],
                                ['label' => 'Reports', 'route' => 'reports.index', 'pattern' => 'reports.*'],
                                ['label' => 'Insights', 'route' => 'insights.index', 'pattern' => 'insights.*'],
                                ['label' => 'Activity', 'route' => 'activity.index', 'pattern' => 'activity.*'],
                            ];
                        @endphp

                        @foreach($navLinks as $link)
                            @php $active = request()->routeIs($link['pattern']); @endphp
                            <a href="{{ route($link['route']) }}"
                               class="px-3 py-1.5 rounded-full text-sm font-medium transition-all
                                      {{ $active
                                            ? 'bg-med-green text-black'
                                            : 'text-med-muted hover:text-med-text hover:bg-med-hover' }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('help.index') }}"
                           class="hidden md:flex w-8 h-8 rounded-full items-center justify-center text-med-muted hover:text-med-text hover:bg-med-hover transition"
                           title="Help">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </a>

                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open"
                                    class="flex items-center gap-2 pl-2 pr-3 py-1 rounded-full hover:bg-med-hover transition">
                                <div class="w-7 h-7 rounded-full bg-med-green flex items-center justify-center text-black font-bold text-xs">
                                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                                </div>
                                <span class="text-sm text-med-text hidden sm:inline">
                                    {{ Auth::user()->name ?? 'User' }}
                                </span>
                            </button>

                            <div x-show="open"
                                 @click.outside="open = false"
                                 x-transition
                                 class="absolute right-0 mt-3 w-56 bg-med-card-2 border border-med-border rounded-2xl shadow-2xl py-2 z-50">
                                <div class="px-4 py-3 border-b border-med-border">
                                    <div class="text-sm font-medium text-med-text">{{ Auth::user()->name }}</div>
                                    <div class="text-xs text-med-muted">{{ Auth::user()->email }}</div>
                                </div>
                                <a href="{{ route('profile.edit') }}"
                                   class="block px-4 py-2 text-sm text-med-muted hover:text-med-text hover:bg-med-hover transition">
                                    Profile
                                </a>
                                <a href="{{ route('help.index') }}"
                                   class="block px-4 py-2 text-sm text-med-muted hover:text-med-text hover:bg-med-hover transition md:hidden">
                                    Help
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm text-med-muted hover:text-red-400 hover:bg-med-hover transition">
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="pt-24 pb-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            {{ $slot }}
        </main>

        @stack('scripts')
    </body>
</html>
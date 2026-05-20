<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            [x-cloak] { display: none !important; }
            /* スクロールバーのカスタマイズ */
            ::-webkit-scrollbar { width: 8px; }
            ::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); }
            ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
            ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
        </style>
    </head>
    <body class="font-sans antialiased bg-[#0f172a] text-slate-200 min-h-screen relative overflow-x-hidden">
        <div class="fixed top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/20 rounded-full blur-[120px] -z-10"></div>
        <div class="fixed bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/20 rounded-full blur-[120px] -z-10"></div>

        <div class="relative z-10">
            @include('layouts.navigation')

            @isset($header)
                <header class="bg-white/5 backdrop-blur-md border-b border-white/10">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
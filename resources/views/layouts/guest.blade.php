<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-200 antialiased bg-[#0f172a] min-h-screen relative overflow-hidden flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        
        <div class="fixed top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/20 rounded-full blur-[120px] -z-10"></div>
        <div class="fixed bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/20 rounded-full blur-[120px] -z-10"></div>

        <div>
            <a href="/" class="text-3xl font-black tracking-tighter text-blue-400 drop-shadow-lg">
                PMT SYSTEM
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-8 px-8 py-10 bg-white/5 backdrop-blur-xl border border-white/10 shadow-2xl overflow-hidden sm:rounded-3xl relative z-10">
            {{ $slot }}
        </div>

    </body>
</html>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ログイン - {{ config('app.name', 'Nexus') }}</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,900&display=swap" rel="stylesheet" />
    @include('partials.brand-font')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0f172a] text-white font-sans antialiased min-h-screen flex flex-col items-center justify-center px-4 relative overflow-hidden">

    <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-blue-600/10 rounded-full blur-[120px] -z-10 pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-20%] w-[60%] h-[60%] bg-sky-600/5 rounded-full blur-[100px] -z-10 pointer-events-none"></div>

    <div class="mb-10 text-center transition-all duration-1000 transform hover:scale-105 z-10">
        <h1 class="brand-logo uppercase text-4xl md:text-5xl">
            Nexus
        </h1>
        <p class="mt-4 font-mono text-[10px] sm:text-[11px] tracking-[0.28em] uppercase text-slate-500 whitespace-nowrap">
            <span class="brand-accent">N</span>etworked<span class="text-slate-600 mx-2">·</span><span class="brand-accent">EX</span>ternal<span class="text-slate-600 mx-2">·</span><span class="brand-accent">U</span>keoi<span class="text-slate-600 mx-2">·</span><span class="brand-accent">S</span>ystem
        </p>
    </div>

    <div class="w-full max-w-md px-8 py-10 bg-white/5 backdrop-blur-2xl border border-blue-500/20 rounded-[2.5rem] shadow-2xl z-10">
        
        <div class="text-center mb-10">
            <h2 class="text-2xl font-bold text-white mb-2">ログイン</h2>
            <p class="text-sm text-blue-200/60 font-medium">システムへようこそ。アカウント情報を入力してください。</p>
        </div>

        <x-auth-session-status class="mb-4 text-emerald-400 font-bold text-sm text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="block text-sm font-bold text-blue-200/80 mb-2 ml-1">メールアドレス</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full bg-black/40 border border-blue-500/20 rounded-2xl text-white text-base px-5 py-4 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none placeholder:text-slate-600"
                    placeholder="example@mail.com">
                @error('email') 
                    <span class="text-red-400 text-xs font-bold mt-2 block ml-1">{{ $message }}</span> 
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-blue-200/80 mb-2 ml-1">パスワード</label>
                <input id="password" type="password" name="password" required
                    class="w-full bg-black/40 border border-blue-500/20 rounded-2xl text-white text-base px-5 py-4 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none placeholder:text-slate-600"
                    placeholder="••••••••">
                @error('password') 
                    <span class="text-red-400 text-xs font-bold mt-2 block ml-1">{{ $message }}</span> 
                @enderror
            </div>

            <div class="flex items-center justify-between px-1 mb-2">
                <label for="remember_me" class="inline-flex items-center cursor-pointer">
                    <input id="remember_me" type="checkbox" name="remember" 
                        class="rounded-md bg-black/40 border-blue-500/30 text-blue-600 focus:ring-blue-500 transition-all">
                    <span class="ms-2 text-xs font-bold text-blue-200/60 hover:text-blue-200 transition-colors">ログイン状態を保持</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-bold text-blue-400 hover:text-blue-300 transition-colors">
                        パスワードをお忘れですか？
                    </a>
                @endif
            </div>

            <div class="pt-4">
                <button type="submit" 
                    class="w-full bg-gradient-to-r from-blue-700 via-blue-600 to-blue-500 hover:from-blue-600 hover:to-blue-400 text-white font-black py-4 rounded-2xl shadow-xl shadow-blue-900/40 transition-all active:scale-95 text-base tracking-widest">
                    ログインする
                </button>
            </div>
            
        </form>

    </div>

</body>
</html>
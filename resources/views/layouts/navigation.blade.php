<nav x-data="{ open: false }" class="bg-white/5 backdrop-blur-lg border-b border-white/10 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center font-black text-xl tracking-tighter text-blue-400">
                    PMT SYSTEM
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex text-xs font-bold uppercase tracking-widest">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-slate-300 hover:text-white transition-colors">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('file_manager')" :active="request()->routeIs('file_manager')" class="text-slate-300 hover:text-white transition-colors">
                        ファイル管理
                    </x-nav-link>
                    <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')" class="text-slate-300 hover:text-white transition-colors">
                        案件一覧
                    </x-nav-link>
                    <x-nav-link :href="route('admin.management')" :active="request()->routeIs('admin.*')" class="text-slate-300 hover:text-white transition-colors">
                        マスター設定
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <div class="text-[16px] font-black text-slate-100 mr-4 tracking-widest uppercase">{{ Auth::user()->name }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-[16px] font-black text-slate-400 hover:text-white transition-colors border border-white/10 px-3 py-1 rounded-full bg-white/5">
                        LOGOUT
                    </button>
                </form>
            </div>

            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-white/10 transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-slate-900/95 backdrop-blur-2xl border-b border-white/10 absolute w-full">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-slate-300">
                ダッシュボード
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('file_manager')" :active="request()->routeIs('file_manager')" class="text-slate-300">
                ファイル管理
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')" class="text-slate-300">
                案件一覧
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.management')" :active="request()->routeIs('admin.*')" class="text-slate-300">
                マスター設定
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-white/10">
            <div class="px-4">
                <div class="font-bold text-base text-slate-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-xs text-slate-500">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-400">
                        ログアウト
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
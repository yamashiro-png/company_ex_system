<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-white tracking-tight">
            {{ __('Dashboard') }} <span class="text-blue-400 text-sm ml-2"></span>
        </h2>
    </x-slot>

    <div class="py-10 min-h-screen">
        <div class="max-w-[98%] mx-auto sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-10 gap-6">
                
                <div class="lg:col-span-4 bg-white/5 backdrop-blur-xl border border-white/10 p-6 rounded-3xl shadow-2xl flex flex-col h-full">
                    <h3 class="font-bold text-blue-400 text-sm mb-6 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span class="text-[25px] font-normal opacity-70">案件サマリー</span>
                    </h3>
                    
                    <div class="mb-6 pb-6 border-b border-white/10 text-center">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1"></p>
                        <p class="text-5xl font-black text-white">{{ $activeCount }} <span class="text-sm text-slate-500 font-medium">件</span></p>
                        <p class="text-[15px] text-slate-200 mt-2">現在進行中（未完了）の案件</p>
                    </div>

                    <div class="space-y-2 flex-grow">
                        <div class="space-y-2 flex-grow">
                        @foreach($statusBreakdown as $status => $count)
                            @php
                                // ▼ ここを $status に変更！
                                $statusColor = match($status) {
                                    '見積もり待ち' => 'bg-blue-600/30 text-blue-200 border-blue-500/50',
                                    '見積もり依頼中' => 'bg-orange-600/30 text-orange-200 border-orange-500/50',
                                    '見積もり依頼待ち' => 'bg-yellow-600/30 text-yellow-200 border-yellow-500/50',
                                    '見積もり結果待ち' => 'bg-purple-600/30 text-purple-200 border-purple-500/50',
                                    '受注確定' => 'bg-emerald-600/30 text-emerald-200 border-emerald-500/50',
                                    '入荷登録情報待ち' => 'bg-pink-600/30 text-pink-200 border-pink-500/50',
                                    '出荷情報登録待ち' => 'bg-cyan-600/30 text-cyan-200 border-cyan-500/50',
                                    '出荷情報待ち' => 'bg-indigo-600/30 text-indigo-200 border-indigo-500/50',
                                    '納品済み' => 'bg-teal-600/30 text-teal-200 border-teal-500/50',
                                    '物品入荷待ち' => 'bg-pink-600/30 text-pink-200 border-pink-500/50',
                                    '失注' => 'bg-slate-700 text-slate-400 border-slate-600',
                                    '案件完了' => 'bg-green-600/30 text-green-200 border-green-500/50',
                                    '完了' => 'bg-green-600/30 text-green-200 border-green-500/50',
                                    default => 'bg-slate-700 text-slate-200 border-slate-500',
                                };
                            @endphp
                            <div class="flex justify-between items-center bg-black/20 p-3 rounded-xl border border-white/5 hover:bg-white/5 transition-colors">
                                <span class="px-3 py-1.5 border rounded-full text-xs font-bold tracking-widest {{ $statusColor }}">
                                    {{ $status }}
                                </span>
                                
                                <span class="text-[16px] font-black text-white">{{ $count }} <span class="text-[16px] text-slate-400 font-normal">件</span></span>
                            </div>
                        @endforeach
                    </div>
                    </div>
                </div>

                <div class="lg:col-span-4 bg-red-500/5 backdrop-blur-xl border border-red-500/20 rounded-3xl p-6 shadow-2xl flex flex-col h-full">
                    <h3 class="font-bold text-red-400 text-sm mb-6 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span class="text-[25px] font-normal opacity-70">期限間近</span>
                    </h3>
                    
                    <div class="space-y-3 flex-grow">
                        @forelse($urgentProjects as $project)
                            <a href="{{ route('projects.workspace', $project) }}" class="block bg-black/20 hover:bg-black/40 border border-white/5 p-4 rounded-xl transition-all group">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-[16px] font-bold text-white group-hover:text-red-400 transition-colors">{{ $project->name }}</span>
                                    <span class="text-[16px] font-mono text-red-400 bg-red-500/10 px-2 py-0.5 rounded">{{ \Carbon\Carbon::parse($project->completion_date)->format('m/d') }}</span>
                                </div>
                                <p class="text-[10px] font-bold text-white text-slate-500">{{ $project->customer->name }}</p>
                            </a>
                        @empty
                            <div class="flex flex-col items-center justify-center h-full opacity-50 py-10">
                                <svg class="w-8 h-8 text-slate-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <p class="text-xs text-slate-400">現在、急ぎの案件はありません。</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 overflow-hidden shadow-2xl flex flex-col h-full">
                    <div class="px-6 py-5 border-b border-white/5 bg-white/5">
                        <h3 class="font-bold text-slate-300 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span class="text-xs font-normal text-slate-400">通知</span>
                            @if($notifications->where('type', 'incoming')->isNotEmpty())
                                <span class="min-w-[18px] h-[18px] px-1 bg-amber-500 text-black text-[10px] font-black rounded-full flex items-center justify-center">{{ $notifications->where('type', 'incoming')->count() }}</span>
                            @endif
                            @if($notifications->whereNotNull('dismiss_url')->isNotEmpty())
                                <form action="{{ route('notifications.dismiss_all') }}" method="POST" class="ml-auto" onsubmit="return confirm('承認・却下の通知をすべてクリアしますか？');">
                                    @csrf
                                    <button type="submit" class="text-[10px] font-bold text-slate-500 hover:text-slate-200 bg-white/5 hover:bg-white/10 border border-white/10 px-2.5 py-1 rounded-lg transition-all">
                                        すべてクリア
                                    </button>
                                </form>
                            @endif
                        </h3>
                    </div>

                    <div class="p-4 flex-grow overflow-y-auto max-h-[560px]">
                        <div class="space-y-3">
                            @forelse($notifications as $notification)
                                @php
                                    $dotColor = match($notification['type']) {
                                        'incoming' => 'bg-amber-400',
                                        'approved' => 'bg-green-400',
                                        'rejected' => 'bg-red-400',
                                        default    => 'bg-slate-600',
                                    };
                                @endphp
                                @php
                                    $typeLabel = match($notification['type']) {
                                        'incoming' => '申請',
                                        'approved' => '承認',
                                        'rejected' => '却下',
                                        default    => '通知',
                                    };
                                    $labelColor = match($notification['type']) {
                                        'incoming' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                                        'approved' => 'bg-green-500/20 text-green-300 border-green-500/40',
                                        'rejected' => 'bg-red-500/20 text-red-300 border-red-500/40',
                                        default    => 'bg-slate-700 text-slate-300 border-slate-600',
                                    };
                                @endphp
                                <div class="bg-black/20 border border-white/10 rounded-2xl p-4 hover:bg-white/5 hover:border-white/20 transition-all group">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $dotColor }} shrink-0 {{ $notification['type'] === 'incoming' ? 'animate-pulse' : '' }}"></span>
                                        <span class="px-2.5 py-0.5 border rounded-full text-xs font-black tracking-wider {{ $labelColor }}">{{ $typeLabel }}</span>
                                        <span class="text-xs text-slate-500 font-mono ml-auto">{{ $notification['time']->diffForHumans() }}</span>
                                        @if(!empty($notification['dismiss_url']))
                                            <form action="{{ $notification['dismiss_url'] }}" method="POST">
                                                @csrf
                                                <button type="submit" title="この通知を消す"
                                                        class="p-1 rounded-lg text-slate-600 hover:text-red-400 hover:bg-red-500/10 transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <a href="{{ $notification['url'] }}" class="block">
                                        <p class="text-[15px] font-bold text-slate-100 leading-relaxed group-hover:text-white transition-colors">
                                            {{ $notification['message'] }}
                                        </p>
                                    </a>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-10 opacity-50">
                                    <svg class="w-8 h-8 text-slate-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    <p class="text-xs text-slate-400">新しい通知はありません。</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
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
                                    '物品入荷待ち' => 'bg-pink-600/30 text-pink-200 border-pink-500/50',
                                    '失注' => 'bg-slate-700 text-slate-400 border-slate-600',
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
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-xs font-normal text-slate-500">直近の操作</span>
                        </h3>
                    </div>
                    
                    <div class="p-4 flex-grow overflow-y-auto">
                        <div class="space-y-4">
                            @foreach($logs as $log)
                                <div class="flex gap-3 items-start">
                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-600 mt-1.5 shrink-0"></div>
                                    <div>
                                        <p class="text-[11px] text-slate-300 leading-snug">
                                            <span class="font-bold text-slate-400 mr-1">{{ $log->causer?->name ?? 'System' }}:</span>
                                            {{ $log->description }}
                                        </p>
                                        <p class="text-[9px] text-slate-500 font-mono mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-white tracking-tight">承認待ちの申請</h2>
    </x-slot>

    <div class="py-8 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (session('success'))
                <div class="bg-green-500/20 border border-green-500/50 text-green-200 p-4 rounded-2xl text-sm font-bold" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-4 rounded-2xl text-sm font-bold" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ▼ 承認待ち ▼ --}}
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <span class="w-1.5 h-6 bg-amber-500 rounded-full"></span>
                    あなたへの承認待ち
                    <span class="ml-1 px-2.5 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/40 rounded-full text-xs font-black">{{ $pendingRequests->count() + $pendingProjectRequests->count() }}件</span>
                </h3>

                <div class="space-y-4">
                    {{-- ▼ STEP 4: 最終見積の承認待ち ▼ --}}
                    @foreach($pendingProjectRequests as $editRequest)
                        <div class="bg-black/20 border border-purple-500/30 rounded-2xl p-6 space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <span class="inline-block mb-1.5 px-2.5 py-0.5 bg-purple-500/20 text-purple-300 border border-purple-500/40 rounded-full text-[10px] font-black">最終見積（STEP 4）</span>
                                    <div class="text-xs text-slate-500 mb-0.5">{{ $editRequest->project->customer->name ?? '' }}</div>
                                    <div class="text-lg font-black text-purple-300 tracking-wide">案件「{{ $editRequest->project->name }}」</div>
                                </div>
                                <span class="text-xs text-slate-500 font-mono whitespace-nowrap">{{ $editRequest->created_at->format('Y/m/d H:i') }}</span>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 font-mono font-bold bg-black/30 border border-white/10 rounded-xl px-4 py-3">
                                <span class="text-slate-400">¥{{ number_format($editRequest->project->final_price ?? 0) }}</span>
                                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <span class="text-purple-300 text-xl">¥{{ number_format($editRequest->requested_final_price) }}</span>
                                <span class="ml-auto font-sans text-xs text-slate-400">採用企業：<span class="text-slate-200 font-bold">{{ $editRequest->requested_partner_name }}</span></span>
                            </div>

                            <div class="text-sm">
                                <div class="text-xs text-slate-400 mb-1">申請者：<span class="text-slate-200 font-bold">{{ $editRequest->requester->name ?? '不明' }}</span></div>
                                <div class="text-slate-300 bg-white/5 border border-white/10 rounded-xl p-3 whitespace-pre-wrap text-sm">{{ $editRequest->reason }}</div>
                            </div>

                            <div class="flex items-center gap-3 pt-1">
                                <a href="{{ route('projects.workspace', $editRequest->project) }}"
                                   class="text-xs bg-white/10 hover:bg-white/20 text-slate-200 font-bold px-4 py-2.5 rounded-xl border border-white/20 transition-all">
                                    案件を見る
                                </a>
                                <div class="flex-1"></div>
                                <form action="{{ route('approvals.project.reject', $editRequest) }}" method="POST" onsubmit="return confirm('この申請を却下しますか？');">
                                    @csrf
                                    <button type="submit" class="text-sm bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white font-bold px-6 py-2.5 rounded-xl border border-red-500/40 transition-all">
                                        却下
                                    </button>
                                </form>
                                <form action="{{ route('approvals.project.approve', $editRequest) }}" method="POST" onsubmit="return confirm('承認すると最終見積が ¥{{ number_format($editRequest->requested_final_price) }}（採用企業：{{ $editRequest->requested_partner_name }}）に更新されます。よろしいですか？');">
                                    @csrf
                                    <button type="submit" class="text-sm bg-purple-600 hover:bg-purple-500 text-white font-bold px-8 py-2.5 rounded-xl shadow-lg shadow-purple-900/30 transition-all">
                                        承認する
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach

                    {{-- ▼ STEP 3: 見積金額の承認待ち ▼ --}}
                    @forelse($pendingRequests as $editRequest)
                        <div class="bg-black/20 border border-amber-500/20 rounded-2xl p-6 space-y-4">
                            {{-- 案件・会社情報 --}}
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <span class="inline-block mb-1.5 px-2.5 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 rounded-full text-[10px] font-black">見積金額（STEP 3）</span>
                                    <div class="text-xs text-slate-500 mb-0.5">
                                        {{ $editRequest->estimate->project->customer->name ?? '' }} ／
                                        案件「{{ $editRequest->estimate->project->name }}」
                                    </div>
                                    <div class="text-lg font-black text-emerald-400 tracking-widest">{{ $editRequest->estimate->partner_name }}</div>
                                </div>
                                <span class="text-xs text-slate-500 font-mono whitespace-nowrap">{{ $editRequest->created_at->format('Y/m/d H:i') }}</span>
                            </div>

                            {{-- 金額の変化 --}}
                            <div class="flex items-center gap-3 font-mono font-bold bg-black/30 border border-white/10 rounded-xl px-4 py-3">
                                <span class="text-slate-400">¥{{ number_format($editRequest->estimate->cost_price) }}</span>
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <span class="text-amber-300 text-xl">¥{{ number_format($editRequest->requested_cost_price) }}</span>
                            </div>

                            {{-- 申請者・理由 --}}
                            <div class="text-sm">
                                <div class="text-xs text-slate-400 mb-1">申請者：<span class="text-slate-200 font-bold">{{ $editRequest->requester->name ?? '不明' }}</span></div>
                                <div class="text-slate-300 bg-white/5 border border-white/10 rounded-xl p-3 whitespace-pre-wrap text-sm">{{ $editRequest->reason }}</div>
                            </div>

                            {{-- アクション --}}
                            <div class="flex items-center gap-3 pt-1">
                                <a href="{{ route('projects.workspace', $editRequest->estimate->project) }}"
                                   class="text-xs bg-white/10 hover:bg-white/20 text-slate-200 font-bold px-4 py-2.5 rounded-xl border border-white/20 transition-all">
                                    案件を見る
                                </a>
                                <div class="flex-1"></div>
                                <form action="{{ route('approvals.reject', $editRequest) }}" method="POST" onsubmit="return confirm('この申請を却下しますか？');">
                                    @csrf
                                    <button type="submit" class="text-sm bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white font-bold px-6 py-2.5 rounded-xl border border-red-500/40 transition-all">
                                        却下
                                    </button>
                                </form>
                                <form action="{{ route('approvals.approve', $editRequest) }}" method="POST" onsubmit="return confirm('承認すると見積金額が ¥{{ number_format($editRequest->requested_cost_price) }} に更新されます。よろしいですか？');">
                                    @csrf
                                    <button type="submit" class="text-sm bg-amber-600 hover:bg-amber-500 text-white font-bold px-8 py-2.5 rounded-xl shadow-lg shadow-amber-900/30 transition-all">
                                        承認する
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        @if($pendingProjectRequests->isEmpty())
                            <div class="text-center py-12 text-slate-500">
                                <svg class="w-10 h-10 mx-auto mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                                <p class="text-sm">承認待ちの申請はありません。</p>
                            </div>
                        @endif
                    @endforelse
                </div>
            </div>

            {{-- ▼ 処理済み（直近10件） ▼ --}}
            @php
                $allProcessed = $processedRequests->concat($processedProjectRequests)->sortByDesc('updated_at')->take(10);
            @endphp
            @if($allProcessed->isNotEmpty())
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <span class="w-1.5 h-6 bg-slate-500 rounded-full"></span>
                    処理済みの申請（直近10件）
                </h3>
                <div class="space-y-3">
                    @foreach($allProcessed as $editRequest)
                        <div class="bg-black/20 border border-white/5 rounded-2xl p-4 flex flex-wrap items-center gap-x-4 gap-y-2">
                            @if($editRequest->status === 'approved')
                                <span class="px-2.5 py-1 bg-green-500/20 text-green-300 border border-green-500/40 rounded-full text-[10px] font-black whitespace-nowrap">承認済み</span>
                            @else
                                <span class="px-2.5 py-1 bg-red-500/20 text-red-300 border border-red-500/40 rounded-full text-[10px] font-black whitespace-nowrap">却下</span>
                            @endif
                            @if($editRequest instanceof \App\Models\ProjectEditRequest)
                                <span class="px-2 py-0.5 bg-purple-500/20 text-purple-300 border border-purple-500/40 rounded-full text-[10px] font-black whitespace-nowrap">最終見積</span>
                                <span class="text-xs text-slate-400">案件「{{ $editRequest->project->name ?? '-' }}」</span>
                                <span class="text-sm font-mono text-slate-300">¥{{ number_format($editRequest->requested_final_price) }}</span>
                            @else
                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 rounded-full text-[10px] font-black whitespace-nowrap">見積金額</span>
                                <span class="text-sm font-bold text-emerald-400">{{ $editRequest->estimate->partner_name ?? '-' }}</span>
                                <span class="text-xs text-slate-400">案件「{{ $editRequest->estimate->project->name ?? '-' }}」</span>
                                <span class="text-sm font-mono text-slate-300">¥{{ number_format($editRequest->requested_cost_price) }}</span>
                            @endif
                            <span class="text-xs text-slate-500">申請：{{ $editRequest->requester->name ?? '不明' }}</span>
                            <span class="text-xs text-slate-600 font-mono ml-auto">{{ $editRequest->updated_at->format('Y/m/d H:i') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>

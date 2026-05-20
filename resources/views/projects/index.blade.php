<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-white tracking-tight">案件管理台帳</h2>
    </x-slot>

    <div class="py-8 min-h-screen">
        <div class="max-w-[98%] mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 overflow-hidden shadow-2xl">
                
                <form action="{{ route('projects.index') }}" method="GET" id="filter-form">
                    <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
                    <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-[16px] border-collapse">
                            <thead class="bg-white/5 text-slate-300 text-[16px] tracking-wider border-b border-white/10">
                                <tr>
                                    <th class="px-6 py-5 align-top">
                                        @include('projects.partials.sort-link', ['label' => '案件名', 'field' => 'name'])
                                        <input type="text" name="search_name" value="{{ request('search_name') }}" placeholder="検索" class="w-full mt-3 bg-black/40 border-white/30 rounded-xl text-sm p-2.5 focus:ring-blue-500 text-white">
                                    </th>
                                    <th class="px-6 py-5 align-top">
                                        @include('projects.partials.sort-link', ['label' => '依頼元', 'field' => 'customer_name'])
                                        <input type="text" name="search_customer" value="{{ request('search_customer') }}" placeholder="検索" class="w-full mt-3 bg-black/40 border-white/30 rounded-xl text-sm p-2.5 focus:ring-blue-500 text-white">
                                    </th>
                                    <th class="px-6 py-5 align-top">
                                        @include('projects.partials.sort-link', ['label' => 'ステータス', 'field' => 'status'])
                                        <select name="search_status" onchange="this.form.submit()" class="w-full mt-3 bg-black/40 border-white/30 rounded-xl text-sm p-2.5 focus:ring-blue-500 text-white cursor-pointer">
                                            <option value="">全て</option>
                                            @foreach(\App\Models\Project::STATUS_OPTIONS as $option)
                                                <option value="{{ $option }}" {{ request('search_status') == $option ? 'selected' : '' }}>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    
                                    <th class="px-6 py-5 align-top">
                                        @include('projects.partials.sort-link', ['label' => '対象機種', 'field' => 'device_model'])
                                        <input type="text" name="search_device" value="{{ request('search_device') }}" placeholder="検索" class="w-full mt-3 bg-black/40 border-white/30 rounded-xl text-sm p-2.5 focus:ring-blue-500 text-white">
                                    </th>
                                    <th class="px-6 py-5 align-top text-center w-24">
                                        @include('projects.partials.sort-link', ['label' => '台数', 'field' => 'device_count'])
                                    </th>
                                    <th class="px-6 py-5 align-top w-32">
                                        @include('projects.partials.sort-link', ['label' => '金額', 'field' => 'price'])
                                    </th>
                                    <th class="px-6 py-5 align-top w-36">
                                        @include('projects.partials.sort-link', ['label' => '完了予定日', 'field' => 'completion_date'])
                                    </th>
                                    <th class="px-6 py-5 align-top text-center w-28">
                                         @include('projects.partials.sort-link', ['label' => '回答状況', 'field' => 'reply_status'])
                                    </th>
                                    <th class="px-6 py-5 text-right align-bottom pb-6 w-32">
                                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white py-2.5 rounded-xl font-bold transition-all active:scale-95 text-sm">絞り込み</button>
                                    </th>
                                    
                                </tr>
                            </thead>
                            
                            <tbody class="divide-y divide-white/10">
                                @forelse($projects as $project)
                                <tr class="hover:bg-white/5 transition-colors group">
                                    <td class="px-6 py-5 font-bold text-white text-[16px]">{{ $project->name }}</td>
                                    
                                    <td class="px-6 py-5 text-sky-300 font-bold text-[16px] tracking-wider">{{ $project->customer->name ?? 'None' }}</td>
                                    
                                    <td class="px-6 py-5">
                                        @php
                                            $statusColor = match($project->status) {
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
                                        <span class="px-4 py-2 border rounded-full text-[16px] font-bold tracking-widest whitespace-nowrap {{ $statusColor }}">
                                            {{ $project->status }}
                                        </span>
                                    </td>
                                    
                                    <td class="px-6 py-5 text-slate-300 text-[16px]">{{ $project->device_model ?? '-' }}</td>
                                    
                                    <td class="px-6 py-5 text-center text-slate-200 font-mono text-[16px]">{{ $project->device_count ?? '-' }}</td>
                                    
                                    <td class="px-6 py-5 font-mono text-[16px]">
                                        @if($project->final_price)
                                            {{-- STEP 4が完了している場合：明るい緑で強調 --}}
                                            <span class="text-emerald-400 text-[11px] font-black block mb-1 tracking-tighter opacity-100">【最終確定】</span>
                                            <span class="text-emerald-300 text-lg font-bold">¥{{ number_format($project->final_price) }}</span>
                                        @elseif($project->price)
                                            {{-- まだ予算案の場合：明るい水色で表示 --}}
                                            <span class="text-sky-400 text-[11px] font-black block mb-1 tracking-tighter opacity-100">【予算案】</span>
                                            <span class="text-sky-300">¥{{ number_format($project->price) }}</span>
                                        @else
                                            <span class="text-slate-500">-</span>
                                        @endif
                                    </td>
                                    {{-- ▼ 復活：完了予定日の列 ▼ --}}
                                    <td class="px-6 py-5 text-slate-300 text-[16px]">
                                        {{ $project->completion_date ? \Carbon\Carbon::parse($project->completion_date)->format('Y/m/d') : '-' }}
                                    </td>
                                    
                                    {{-- ▼ 修正：回答状況の列（全社入力で「回答記録済」になる最新版） ▼ --}}
                                    <td class="px-6 py-5 text-center">
                                        @php
                                            $estimatesCount = $project->estimates->count();
                                            $isAllAnswered = false;

                                            if ($estimatesCount > 0) {
                                                // すべての会社の回答が入力されているかチェック
                                                $isAllAnswered = $project->estimates->every(function ($estimate) {
                                                    return !empty($estimate->cost_price) || !empty($estimate->partner_completion_date) || !empty($estimate->partner_message);
                                                });
                                            }
                                        @endphp

                                        @if($isAllAnswered)
                                            <span class="px-3 py-1.5 border border-emerald-500/30 bg-emerald-500/20 text-emerald-300 rounded-full text-[16px] font-bold tracking-widest whitespace-nowrap">
                                                回答記録済
                                            </span>
                                        @else
                                            <span class="px-3 py-1.5 border border-slate-600 bg-slate-700/50 text-slate-400 rounded-full text-[16px] font-bold tracking-widest whitespace-nowrap">
                                                未回答
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <a href="{{ route('projects.workspace', $project) }}" class="inline-flex items-center gap-2 bg-white/10 hover:bg-blue-600 text-slate-200 hover:text-white border border-white/10 hover:border-blue-500 font-bold py-2.5 px-5 rounded-lg transition-all text-[16px] whitespace-nowrap">
                                            詳細
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                            </svg>
                                        </a>
                                    </td>
                                    
                                </tr>
                                @empty
                                <tr><td colspan="8" class="px-6 py-20 text-center text-slate-400 text-sm">案件が見つかりません。</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
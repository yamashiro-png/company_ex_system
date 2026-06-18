<x-app-layout>
    @php
        $statusRank = [
            '見積もり待ち' => 1,
            '見積もり依頼中' => 2,
            '見積もり依頼待ち' => 3,
            '見積もり結果待ち' => 4,
            '物品入荷待ち' => 5,
            '失注' => 6,
            '完了' => 7,
        ];
        $currentRank = $statusRank[$project->status] ?? 1;

        // 各STEPの完了判定
        $isStep1Completed = !empty($project->device_model)
                         && !empty($project->os)
                         && !empty($project->device_count)
                         && !empty($project->contract_date)
                         && !empty($project->completion_date);

        $estimatesCount = $project->estimates->count();
        $isAllAnswered = $estimatesCount > 0 && $project->estimates->every(function ($estimate) {
            return !empty($estimate->cost_price) || !empty($estimate->partner_completion_date) || !empty($estimate->partner_message);
        });

        $hasAnyAnswer = $estimatesCount > 0 && $project->estimates->contains(function ($estimate) {
            return !empty($estimate->cost_price) || !empty($estimate->partner_completion_date) || !empty($estimate->partner_message);
        });

        $isStep4Completed = !empty($project->final_price);

        // 受注済み（受注後ステータス）かどうか
        $isOrdered = in_array($project->status, \App\Models\Project::POST_ORDER_STATUSES, true);

        // 受注後フローの進行判定（ステータスで段階的に解放）
        $orderStatusRank = [
            '受注確定' => 1,
            '入荷登録情報待ち' => 2,
            '出荷情報登録待ち' => 3,
            '出荷情報待ち' => 4,
            '納品済み' => 5,
            '案件完了' => 6,
            '完了' => 6, // 旧データ互換
            '物品入荷待ち' => 2, // 旧データ互換
        ];
        $orderRank = $orderStatusRank[$project->status] ?? 0;
        $isSplitDelivery = $project->delivery_method === '分納';
        $shipmentTotal = $project->shipments->sum('planned_count');

        // ファイルの区分
        $parameterFiles = $project->files->whereNull('category');
        $orderFiles = $project->files->where('category', 'order_form');
        $manualFiles = $project->files->where('category', 'manual');
        $arrivalParamFiles = $project->files->where('category', 'arrival_parameter');
        $shippingFiles = $project->files->where('category', 'shipping_data');

        $headerStatusColor = match($project->status) {
            '見積もり待ち' => 'bg-blue-600/30 text-blue-200 border-blue-500/50',
            '見積もり依頼中' => 'bg-orange-600/30 text-orange-200 border-orange-500/50',
            '見積もり依頼待ち' => 'bg-yellow-600/30 text-yellow-200 border-yellow-500/50',
            '見積もり結果待ち' => 'bg-purple-600/30 text-purple-200 border-purple-500/50',
            '受注確定' => 'bg-emerald-600/30 text-emerald-200 border-emerald-500/50',
            '入荷登録情報待ち' => 'bg-pink-600/30 text-pink-200 border-pink-500/50',
            '出荷情報登録待ち' => 'bg-cyan-600/30 text-cyan-200 border-cyan-500/50',
            '出荷情報待ち' => 'bg-indigo-600/30 text-indigo-200 border-indigo-500/50',
            '納品済み' => 'bg-teal-600/30 text-teal-200 border-teal-500/50',
            '案件完了' => 'bg-green-600/30 text-green-200 border-green-500/50',
            '物品入荷待ち' => 'bg-pink-600/30 text-pink-200 border-pink-500/50',
            '失注' => 'bg-slate-700 text-slate-400 border-slate-600',
            '完了' => 'bg-green-600/30 text-green-200 border-green-500/50',
            default => 'bg-slate-700 text-slate-200 border-slate-500',
        };
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    @if($project->project_number)
                        <span class="text-sm font-mono text-slate-500">{{ str_pad($project->project_number, 6, '0', STR_PAD_LEFT) }}</span>
                    @endif
                    <h2 class="font-bold text-2xl text-white tracking-tight">{{ $project->name }}</h2>
                    <span class="px-3 py-1 border rounded-full text-xs font-bold tracking-widest whitespace-nowrap {{ $headerStatusColor }}">
                        {{ $project->status }}
                    </span>
                </div>
                <p class="text-sm text-slate-400 mt-1">
                    クライアント：<span class="text-sky-300 font-bold">{{ $project->customer->name ?? '-' }}</span>
                    @if($project->ownPic)
                        <span class="ml-4">自社担当：<span class="text-slate-200 font-bold">{{ $project->ownPic->name }}</span></span>
                    @endif
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="{{ $isOrdered ? 'w-full px-4 sm:px-6 lg:px-8' : 'max-w-4xl mx-auto sm:px-6 lg:px-8' }} space-y-8">

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
            @if ($errors->any())
                <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-4 rounded-2xl text-sm" role="alert">
                    <p class="font-bold mb-2">入力内容にエラーがあります：</p>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="{{ $isOrdered ? 'lg:flex lg:items-start lg:gap-8' : '' }}">

                @if($isOrdered)
                {{-- ▼ 受注後：案件情報パネル（左サイドバー） ▼ --}}
                <aside class="lg:w-64 flex-shrink-0 mb-8 lg:mb-0">
                    <div class="lg:sticky lg:top-24 bg-white/5 backdrop-blur-xl border border-green-500/20 rounded-3xl p-6 shadow-2xl">
                        <h3 class="text-sm font-bold text-green-300 flex items-center gap-2 pb-4 mb-4 border-b border-white/10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            受注案件情報
                        </h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-xs font-bold text-slate-500 mb-0.5">OS</dt>
                                <dd class="text-white font-bold">{{ $project->os ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-slate-500 mb-0.5">機種名</dt>
                                <dd class="text-white font-bold">{{ $project->device_model ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-slate-500 mb-0.5">台数</dt>
                                <dd class="text-white font-bold font-mono">{{ $project->device_count ? number_format($project->device_count) . ' 台' : '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-slate-500 mb-0.5">開始日</dt>
                                <dd class="text-white font-bold">{{ $project->contract_date ? \Carbon\Carbon::parse($project->contract_date)->format('Y/m/d') : '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold text-slate-500 mb-0.5">納品日</dt>
                                <dd class="text-white font-bold">{{ $project->completion_date ? \Carbon\Carbon::parse($project->completion_date)->format('Y/m/d') : '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </aside>
                @endif

                {{-- ▼ STEP 1〜4（受注後は折りたたみ） ▼ --}}
                <div class="flex-1 min-w-0">
                    <div class="{{ $isOrdered ? 'max-w-4xl mx-auto' : '' }}">

                    {{-- ============ 受注後フロー（STEP 5〜9） ============ --}}

                    {{-- ▼ STEP 9: 請求 ▼ --}}
                    @if($orderRank >= 5)
                    <div class="relative mb-12" x-data="{ open: {{ in_array($project->status, ['案件完了','完了']) ? 'false' : 'true' }} }">
                        <div class="absolute -left-4 -top-4 bg-rose-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-rose-400/50">STEP 9</div>
                        <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3" :class="open ? 'mb-6' : ''">
                                <span class="w-2 h-8 bg-rose-500 rounded-full"></span>請求
                                @if(in_array($project->status, ['案件完了','完了']))<span class="text-xs bg-green-500/20 text-green-300 border border-green-500/40 px-2.5 py-0.5 rounded-full">案件完了</span>@endif
                                <button type="button" @click="open = !open" class="ml-auto p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/10 transition-all">
                                    <svg class="w-5 h-5 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </h3>
                            <div x-show="open">
                                @php
                                    $billedTotal = (int) $project->invoices->sum('billing_count');
                                    $billRemaining = (int) $project->device_count - $billedTotal;

                                    // 出荷（STEP 8）を月ごとに集計
                                    $deliveryMonths = $project->deliveries
                                        ->groupBy(fn($d) => \Carbon\Carbon::parse($d->shipped_date)->format('Y-m'))
                                        ->map(fn($group) => [
                                            'count' => (int) $group->sum('shipped_count'),
                                            'cost'  => (float) $group->sum('shipping_cost'),
                                        ])
                                        ->sortKeys();
                                    $billedMonths = $project->invoices->pluck('billing_month')->filter()->values()->all();
                                    $unbilledMonths = $deliveryMonths->reject(fn($v, $m) => in_array($m, $billedMonths, true));
                                @endphp

                                {{-- 請求履歴 --}}
                                @if($project->invoices->count() > 0)
                                    <div class="bg-black/20 border border-white/10 rounded-2xl p-5 mb-5">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm font-bold text-slate-300">請求履歴</span>
                                            <span class="text-xs font-mono {{ $billedTotal >= $project->device_count ? 'text-green-400' : 'text-amber-400' }}">
                                                請求済 {{ number_format($billedTotal) }} / {{ number_format($project->device_count) }} 台
                                            </span>
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($project->invoices as $inv)
                                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 bg-white/5 rounded-xl px-4 py-2.5 border border-white/10 text-sm">
                                                    <span class="font-mono text-rose-300">{{ $project->documentNumber('S') . ($project->delivery_method === '分納' ? '-' . str_pad($inv->sequence, 2, '0', STR_PAD_LEFT) : '') }}</span>
                                                    <span class="text-slate-300">{{ \Carbon\Carbon::parse($inv->billing_date)->format('Y/m/d') }}</span>
                                                    <span class="font-mono text-slate-200">{{ number_format($inv->billing_count) }} 台</span>
                                                    <span class="font-mono text-slate-400 ml-auto">¥{{ number_format($inv->amount_total) }}（税込）</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- 月ごとの出荷集計 --}}
                                @if($deliveryMonths->isNotEmpty())
                                    <div class="bg-black/20 border border-white/10 rounded-2xl p-5 mb-5">
                                        <span class="text-sm font-bold text-slate-300">月ごとの出荷（請求対象）</span>
                                        <div class="space-y-2 mt-3">
                                            @foreach($deliveryMonths as $month => $info)
                                                @php $isBilled = in_array($month, $billedMonths, true); @endphp
                                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 bg-white/5 rounded-xl px-4 py-2.5 border border-white/10 text-sm">
                                                    <span class="text-slate-200 font-bold">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('Y年n月') }}</span>
                                                    <span class="font-mono text-white">{{ number_format($info['count']) }} 台</span>
                                                    <span class="font-mono text-slate-400">出荷費用 ¥{{ number_format($info['cost']) }}</span>
                                                    @if($isBilled)
                                                        <span class="ml-auto text-[11px] bg-green-500/20 text-green-300 border border-green-500/40 px-2.5 py-0.5 rounded-full">請求済み</span>
                                                    @else
                                                        <span class="ml-auto text-[11px] bg-amber-500/20 text-amber-300 border border-amber-500/40 px-2.5 py-0.5 rounded-full">未請求</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($billRemaining > 0)
                                    @if($unbilledMonths->isNotEmpty())
                                    <form action="{{ route('projects.invoice_pdf', $project) }}" method="POST"
                                          x-data="{ months: {{ \Illuminate\Support\Js::from($unbilledMonths->map(fn($v) => ['count' => $v['count'], 'cost' => $v['cost']])) }}, selected: '{{ $unbilledMonths->keys()->first() }}' }">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1.5">請求対象月 <span class="text-red-400">*</span></label>
                                                <select name="billing_month" x-model="selected" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-rose-500 cursor-pointer">
                                                    @foreach($unbilledMonths as $month => $info)
                                                        <option value="{{ $month }}">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('Y年n月') }}（{{ number_format($info['count']) }}台）</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1.5">請求日 <span class="text-red-400">*</span></label>
                                                <input type="date" name="billing_date" value="{{ now()->format('Y-m-d') }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 [color-scheme:dark]">
                                            </div>
                                            <button type="submit" class="bg-rose-600 hover:bg-rose-500 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-rose-900/30 flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                請求書を生成
                                            </button>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-2">
                                            請求台数 <span class="text-rose-300 font-mono" x-text="months[selected] ? months[selected].count : 0"></span> 台
                                            ／ 出荷費用 ¥<span class="text-rose-300 font-mono" x-text="months[selected] ? Number(months[selected].cost).toLocaleString() : 0"></span>（自動集計）
                                        </p>
                                    </form>
                                    <p class="text-xs text-slate-500 mt-3">請求対象月を選ぶと、その月に出荷（STEP 8）した分が自動で請求台数になります。全数の請求＋全数の入荷が完了するとステータスが「案件完了」になります。</p>
                                    @else
                                    <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl py-4 text-center">
                                        <span class="text-amber-300 font-bold text-sm">未請求の出荷がありません。</span>
                                        <p class="text-xs text-slate-400 mt-1">STEP 8 で出荷（納期情報）を登録すると、その月の分を請求できます。</p>
                                    </div>
                                    @endif
                                @else
                                <div class="bg-green-500/10 border border-green-500/30 rounded-2xl py-4 text-center">
                                    <span class="text-green-300 font-bold">全数（{{ number_format($project->device_count) }}台）の請求が完了しました。</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif


                    {{-- ▼ STEP 8: 納期情報 ▼ --}}
                    @if($orderRank >= 4)
                    <div class="relative mb-12" x-data="{ open: {{ $orderRank == 4 ? 'true' : 'false' }} }">
                        <div class="absolute -left-4 -top-4 bg-teal-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-teal-400/50">STEP 8</div>
                        <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3" :class="open ? 'mb-6' : ''">
                                <span class="w-2 h-8 bg-teal-500 rounded-full"></span>納期情報
                                @if($orderRank > 4)<span class="text-xs bg-green-500/20 text-green-300 border border-green-500/40 px-2.5 py-0.5 rounded-full">登録済み</span>@endif
                                <button type="button" @click="open = !open" class="ml-auto p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/10 transition-all">
                                    <svg class="w-5 h-5 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </h3>
                            <div x-show="open" class="space-y-6">
                                {{-- 出荷データのアップロード --}}
                                <div>
                                    <h4 class="text-sm font-bold text-teal-300 mb-3">出荷データのアップロード</h4>
                                    <form action="{{ route('projects.order_files.store', $project) }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row items-stretch md:items-center gap-3 mb-3">
                                        @csrf
                                        <input type="hidden" name="category" value="shipping_data">
                                        <input type="file" name="order_files[]" multiple required class="flex-grow block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-5 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-teal-600 file:text-white hover:file:bg-teal-500 cursor-pointer bg-black/40 rounded-xl border border-white/30">
                                        <button type="submit" class="bg-teal-600 hover:bg-teal-500 text-white font-bold py-2.5 px-6 rounded-xl transition-all text-sm whitespace-nowrap">アップロード</button>
                                    </form>
                                    @if($shippingFiles->count() > 0)
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            @foreach($shippingFiles as $file)
                                                <div class="flex items-center justify-between bg-white/5 p-3 rounded-xl border border-white/10">
                                                    <span class="text-xs text-slate-300 truncate mr-2">{{ $file->file_name }}</span>
                                                    <div class="flex gap-3 shrink-0">
                                                        <a href="{{ route('projects.files.download', $file) }}" class="text-teal-400 text-xs hover:underline bg-teal-500/10 px-3 py-1 rounded">DL</a>
                                                        <button type="button" onclick="if(confirm('このファイルを削除しますか？')) document.getElementById('delete-file-form-{{ $file->id }}').submit();" class="text-red-400 text-xs hover:underline cursor-pointer">削除</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-500 italic">出荷データはまだアップロードされていません。</p>
                                    @endif
                                </div>
                                {{-- 出荷日・出荷台数・出荷費用（出荷便ごとに複数登録） --}}
                                <div class="pt-6 border-t border-white/10">
                                    <h4 class="text-sm font-bold text-teal-300 mb-3">出荷実績（出荷便ごとに登録）</h4>
                                    <div class="bg-black/20 border border-white/10 rounded-2xl p-5">
                                        @php $deliveredTotal = (int) $project->deliveries->sum('shipped_count'); @endphp
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm font-bold text-slate-300">出荷記録</span>
                                            <span class="text-xs font-mono text-slate-400">出荷済 {{ number_format($deliveredTotal) }} / {{ number_format($project->device_count) }} 台</span>
                                        </div>
                                        @if($project->deliveries->count() > 0)
                                            <div class="space-y-2 mb-3">
                                                @foreach($project->deliveries as $dv)
                                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 bg-white/5 rounded-xl px-4 py-2.5 border border-white/10 text-sm">
                                                        <span class="text-slate-200">{{ \Carbon\Carbon::parse($dv->shipped_date)->format('Y/m/d') }}</span>
                                                        <span class="font-mono text-white">{{ $dv->shipped_count !== null ? number_format($dv->shipped_count) . ' 台' : '-' }}</span>
                                                        <span class="font-mono text-slate-400">出荷費用 ¥{{ number_format($dv->shipping_cost ?? 0) }}</span>
                                                        <form action="{{ route('projects.deliveries.delete', [$project, $dv]) }}" method="POST" onsubmit="return confirm('この納期情報を削除しますか？');" class="ml-auto">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="text-red-400 text-xs hover:underline">削除</button>
                                                        </form>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-xs text-slate-500 italic mb-3">出荷記録がまだありません。</p>
                                        @endif
                                        <form action="{{ route('projects.deliveries.add', $project) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end pt-3 border-t border-white/10">
                                            @csrf
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-400 mb-1">出荷日 <span class="text-red-400">*</span></label>
                                                <input type="date" name="shipped_date" value="{{ now()->format('Y-m-d') }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-3 py-2 text-sm [color-scheme:dark]">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-400 mb-1">出荷台数</label>
                                                <input type="number" name="shipped_count" min="1" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-3 py-2 text-sm font-mono">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-400 mb-1">出荷費用（円）</label>
                                                <input type="number" name="shipping_cost" min="0" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-3 py-2 text-sm font-mono">
                                            </div>
                                            <button type="submit" class="bg-teal-600 hover:bg-teal-500 text-white font-bold py-2.5 px-5 rounded-xl transition-all text-sm whitespace-nowrap">納期情報を追加</button>
                                        </form>
                                        <p class="text-[11px] text-slate-500 mt-2">1件目を登録するとステータスが「納品済み」に進みます。月ごとの出荷便を分けて登録できます。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif


                    {{-- ▼ STEP 7: 出荷情報（分納対応） ▼ --}}
                    @if($orderRank >= 3)
                    <div class="relative mb-12" x-data="{ open: {{ $orderRank == 3 ? 'true' : 'false' }} }">
                        <div class="absolute -left-4 -top-4 bg-indigo-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-indigo-400/50">STEP 7</div>
                        <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                            <h3 @click="open = !open" class="text-xl font-bold text-white flex items-center gap-3 cursor-pointer select-none" :class="open ? 'mb-6' : ''">
                                <span class="w-2 h-8 bg-indigo-500 rounded-full"></span>出荷情報
                                <span class="text-xs bg-white/10 text-slate-300 border border-white/20 px-2.5 py-0.5 rounded-full">{{ $isSplitDelivery ? '分納' : '一括納品' }}</span>
                                @if($orderRank > 3)<span class="text-xs bg-green-500/20 text-green-300 border border-green-500/40 px-2.5 py-0.5 rounded-full">確定済み</span>@endif
                                <span class="ml-auto p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/10 transition-all">
                                    <svg class="w-5 h-5 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </h3>
                            <div x-show="open" class="space-y-5">
                                @php
                                    $remaining = (int) $project->device_count - (int) $shipmentTotal;
                                    // 出荷予定の編集（追加・削除）は、確定後でも出荷が全て終わるまで（納品済み前）可能
                                    $canEditShipments = $orderRank >= 3 && $orderRank < 5;
                                @endphp
                                <div class="bg-black/20 border border-white/10 rounded-2xl p-5">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-bold text-slate-300">出荷予定{{ $isSplitDelivery ? '（分割して登録）' : '' }}</span>
                                        <span class="text-xs font-mono {{ $shipmentTotal == $project->device_count ? 'text-green-400' : 'text-amber-400' }}">
                                            合計 {{ number_format($shipmentTotal) }} / {{ number_format($project->device_count) }} 台
                                        </span>
                                    </div>
                                    @if($project->shipments->count() > 0)
                                        <div class="space-y-2 mb-3">
                                            @foreach($project->shipments as $sh)
                                                <div class="flex items-center justify-between bg-white/5 rounded-xl px-4 py-2.5 border border-white/10">
                                                    <span class="text-sm text-slate-200">{{ \Carbon\Carbon::parse($sh->planned_date)->format('Y/m/d') }}</span>
                                                    <span class="text-sm font-mono text-white">{{ number_format($sh->planned_count) }} 台</span>
                                                    @if($canEditShipments)
                                                        <form action="{{ route('projects.shipments.delete', [$project, $sh]) }}" method="POST" onsubmit="return confirm('この出荷予定を削除しますか？');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="text-red-400 text-xs hover:underline">削除</button>
                                                        </form>
                                                    @else
                                                        <span class="text-[11px] text-slate-500">確定</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-500 italic mb-3">出荷予定がまだありません。</p>
                                    @endif

                                    @if($canEditShipments && $remaining > 0)
                                        <form action="{{ route('projects.shipments.add', $project) }}" method="POST" class="pt-4 border-t border-white/10"
                                              x-data="shipmentPlanner({ remaining: {{ $remaining }}, isSplit: {{ $isSplitDelivery ? 'true' : 'false' }} })">
                                            @csrf
                                            <p class="text-[11px] font-bold text-slate-400 mb-3">カレンダーの日付をクリックすると、出荷予定の入力欄が追加されます（残 {{ number_format($remaining) }} 台）</p>
                                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                                                {{-- カレンダー --}}
                                                <div class="bg-black/30 border border-white/10 rounded-2xl p-4 select-none">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <button type="button" @click="prevMonth()" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-white/10 text-lg">‹</button>
                                                        <span class="text-sm font-bold text-white" x-text="monthLabel"></span>
                                                        <button type="button" @click="nextMonth()" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-white/10 text-lg">›</button>
                                                    </div>
                                                    <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-slate-500 mb-1">
                                                        <template x-for="w in ['日','月','火','水','木','金','土']" :key="w"><span x-text="w"></span></template>
                                                    </div>
                                                    <div class="grid grid-cols-7 gap-1">
                                                        <template x-for="(d, i) in cells" :key="i">
                                                            <div>
                                                                <template x-if="d">
                                                                    <button type="button" @click="toggleDate(d)"
                                                                            :class="isSelected(d) ? 'bg-indigo-600 text-white font-bold shadow' : 'text-slate-300 hover:bg-white/10'"
                                                                            class="w-full aspect-square rounded-lg text-xs transition-colors" x-text="d"></button>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                                {{-- 選択した日付の入力欄 --}}
                                                <div class="flex flex-col">
                                                    <template x-if="selectedDates.length === 0">
                                                        <p class="text-xs text-slate-500 italic py-4">日付を選択すると、ここに台数の入力欄が表示されます。</p>
                                                    </template>
                                                    <div class="space-y-2">
                                                        <template x-for="(d, i) in selectedDates" :key="d">
                                                            <div class="flex items-center gap-3 bg-white/5 rounded-xl px-3 py-2 border border-white/10">
                                                                <span class="text-sm text-slate-200 flex-1" x-text="formatDate(d)"></span>
                                                                <input type="hidden" :name="'shipments[' + i + '][planned_date]'" :value="d">
                                                                <input type="number" min="1" x-model="selected[d]" :name="'shipments[' + i + '][planned_count]'"
                                                                       class="w-24 bg-black/40 border-white/30 rounded-lg text-white px-3 py-1.5 text-sm font-mono text-right" placeholder="台数">
                                                                <span class="text-xs text-slate-500">台</span>
                                                                <button type="button" @click="toggleDate(d)" class="text-red-400 text-xl leading-none hover:text-red-300">&times;</button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-3 mt-4 pt-3 border-t border-white/10">
                                                        <span class="text-xs font-mono" :class="total > remaining ? 'text-red-400' : 'text-slate-400'">
                                                            合計 <span x-text="total"></span> / 残 {{ number_format($remaining) }} 台
                                                        </span>
                                                        <button type="submit" :disabled="!valid"
                                                                class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold py-2 px-8 rounded-xl transition-all text-sm">保存</button>
                                                    </div>
                                                    <p class="text-[11px] text-red-400 mt-2" x-show="total > remaining" x-cloak>残り台数（{{ number_format($remaining) }}台）を超えています。</p>
                                                </div>
                                            </div>
                                        </form>
                                    @endif

                                    @if($orderRank == 3)
                                        <form action="{{ route('projects.shipments.confirm', $project) }}" method="POST" class="mt-4">
                                            @csrf
                                            @if($shipmentTotal >= 1 && $shipmentTotal <= $project->device_count)
                                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-xl shadow-lg transition-all">出荷予定を確定（出荷情報待ちへ）</button>
                                                @if($shipmentTotal < $project->device_count)
                                                    <p class="text-[11px] text-amber-400 text-center mt-2">全数（{{ number_format($project->device_count) }}台）に未達ですが、分納のため今ある分で確定して次へ進めます。残りは確定後もこの画面から追加できます。</p>
                                                @endif
                                            @else
                                                <div class="w-full bg-black/20 border border-white/10 rounded-xl text-slate-500 py-3 text-center text-sm">出荷予定を1件以上追加すると確定できます（受注台数を超える登録は不可）</div>
                                            @endif
                                        </form>
                                    @endif

                                    @if($orderRank == 4 && $remaining > 0)
                                        <p class="text-[11px] text-amber-400 mt-3">確定済みですが、分納の残り {{ number_format($remaining) }} 台を上のフォームから追加できます。</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif


                    {{-- ▼ STEP 6: 入荷情報 ▼ --}}
                    @if($orderRank >= 2)
                    <div class="relative mb-12" x-data="{ open: {{ $orderRank == 2 ? 'true' : 'false' }} }">
                        <div class="absolute -left-4 -top-4 bg-pink-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-pink-400/50">STEP 6</div>
                        <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3" :class="open ? 'mb-6' : ''">
                                <span class="w-2 h-8 bg-pink-500 rounded-full"></span>入荷情報
                                @if($orderRank > 2)<span class="text-xs bg-green-500/20 text-green-300 border border-green-500/40 px-2.5 py-0.5 rounded-full">登録済み</span>@endif
                                <button type="button" @click="open = !open" class="ml-auto p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/10 transition-all">
                                    <svg class="w-5 h-5 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </h3>
                            <div x-show="open" class="space-y-6">
                                {{-- 手順書のアップロード --}}
                                <div>
                                    <h4 class="text-sm font-bold text-pink-300 mb-3">手順書のアップロード</h4>
                                    <form action="{{ route('projects.order_files.store', $project) }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row items-stretch md:items-center gap-3 mb-3">
                                        @csrf
                                        <input type="hidden" name="category" value="manual">
                                        <input type="file" name="order_files[]" multiple required class="flex-grow block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-5 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-pink-600 file:text-white hover:file:bg-pink-500 cursor-pointer bg-black/40 rounded-xl border border-white/30">
                                        <button type="submit" class="bg-pink-600 hover:bg-pink-500 text-white font-bold py-2.5 px-6 rounded-xl transition-all text-sm whitespace-nowrap">アップロード</button>
                                    </form>
                                    @if($manualFiles->count() > 0)
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            @foreach($manualFiles as $file)
                                                <div class="flex items-center justify-between bg-white/5 p-3 rounded-xl border border-white/10">
                                                    <span class="text-xs text-slate-300 truncate mr-2">{{ $file->file_name }}</span>
                                                    <div class="flex gap-3 shrink-0">
                                                        <a href="{{ route('projects.files.download', $file) }}" class="text-pink-400 text-xs hover:underline bg-pink-500/10 px-3 py-1 rounded">DL</a>
                                                        <button type="button" onclick="if(confirm('このファイルを削除しますか？')) document.getElementById('delete-file-form-{{ $file->id }}').submit();" class="text-red-400 text-xs hover:underline cursor-pointer">削除</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-500 italic">手順書はまだアップロードされていません。</p>
                                    @endif
                                </div>
                                {{-- パラメータのアップロード --}}
                                <div class="pt-6 border-t border-white/10">
                                    <h4 class="text-sm font-bold text-pink-300 mb-3">パラメータのアップロード</h4>
                                    <form action="{{ route('projects.order_files.store', $project) }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row items-stretch md:items-center gap-3 mb-3">
                                        @csrf
                                        <input type="hidden" name="category" value="arrival_parameter">
                                        <input type="file" name="order_files[]" multiple required class="flex-grow block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-5 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-pink-600 file:text-white hover:file:bg-pink-500 cursor-pointer bg-black/40 rounded-xl border border-white/30">
                                        <button type="submit" class="bg-pink-600 hover:bg-pink-500 text-white font-bold py-2.5 px-6 rounded-xl transition-all text-sm whitespace-nowrap">アップロード</button>
                                    </form>
                                    @if($arrivalParamFiles->count() > 0)
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            @foreach($arrivalParamFiles as $file)
                                                <div class="flex items-center justify-between bg-white/5 p-3 rounded-xl border border-white/10">
                                                    <span class="text-xs text-slate-300 truncate mr-2">{{ $file->file_name }}</span>
                                                    <div class="flex gap-3 shrink-0">
                                                        <a href="{{ route('projects.files.download', $file) }}" class="text-pink-400 text-xs hover:underline bg-pink-500/10 px-3 py-1 rounded">DL</a>
                                                        <button type="button" onclick="if(confirm('このファイルを削除しますか？')) document.getElementById('delete-file-form-{{ $file->id }}').submit();" class="text-red-400 text-xs hover:underline cursor-pointer">削除</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-500 italic">パラメータはまだアップロードされていません。</p>
                                    @endif
                                </div>
                                {{-- 入荷日・台数 --}}
                                <div class="pt-6 border-t border-white/10">
                                    <h4 class="text-sm font-bold text-pink-300 mb-3">入荷情報の登録</h4>

                                    @if($isSplitDelivery)
                                        {{-- 分納：入荷日＋台数を複数回登録 --}}
                                        @php $arrivalTotal = (int) $project->arrivals->sum('arrived_count'); @endphp
                                        <div class="bg-black/20 border border-white/10 rounded-2xl p-5">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-sm font-bold text-slate-300">入荷記録（分割）</span>
                                                <span class="text-xs font-mono {{ $arrivalTotal >= $project->device_count ? 'text-green-400' : 'text-amber-400' }}">
                                                    合計 {{ number_format($arrivalTotal) }} / {{ number_format($project->device_count) }} 台
                                                </span>
                                            </div>
                                            @if($project->arrivals->count() > 0)
                                                <div class="space-y-2 mb-3">
                                                    @foreach($project->arrivals as $ar)
                                                        <div class="flex items-center justify-between bg-white/5 rounded-xl px-4 py-2.5 border border-white/10">
                                                            <span class="text-sm text-slate-200">{{ \Carbon\Carbon::parse($ar->arrived_date)->format('Y/m/d') }}</span>
                                                            <span class="text-sm font-mono text-white">{{ number_format($ar->arrived_count) }} 台</span>
                                                            <form action="{{ route('projects.arrivals.delete', [$project, $ar]) }}" method="POST" onsubmit="return confirm('この入荷記録を削除しますか？');">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="text-red-400 text-xs hover:underline">削除</button>
                                                            </form>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-xs text-slate-500 italic mb-3">入荷記録がまだありません。</p>
                                            @endif
                                            <form action="{{ route('projects.arrivals.add', $project) }}" method="POST" class="flex flex-col md:flex-row items-stretch md:items-end gap-3 pt-3 border-t border-white/10">
                                                @csrf
                                                <div class="flex-1">
                                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">入荷日</label>
                                                    <input type="date" name="arrived_date" value="{{ now()->format('Y-m-d') }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-3 py-2 text-sm [color-scheme:dark]">
                                                </div>
                                                <div class="w-full md:w-40">
                                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">入荷台数</label>
                                                    <input type="number" name="arrived_count" min="1" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-3 py-2 text-sm font-mono">
                                                </div>
                                                <button type="submit" class="bg-pink-600 hover:bg-pink-500 text-white font-bold py-2 px-5 rounded-xl transition-all text-sm whitespace-nowrap">入荷を追加</button>
                                            </form>
                                            <p class="text-[11px] text-slate-500 mt-2">1件目を登録するとステータスが「出荷情報登録待ち」に進みます。以降も追加・削除できます。</p>
                                        </div>
                                    @else
                                        {{-- 一括納品：従来どおり1回登録 --}}
                                        <form action="{{ route('projects.arrival.register', $project) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1.5">入荷日 <span class="text-red-400">*</span></label>
                                                <input type="date" name="arrival_date" value="{{ $project->arrival_date ?: now()->format('Y-m-d') }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 [color-scheme:dark]">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1.5">台数 <span class="text-red-400">*</span></label>
                                                <input type="number" name="arrival_count" value="{{ $project->arrival_count }}" min="0" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-pink-500 font-mono">
                                            </div>
                                            <button type="submit" class="bg-pink-600 hover:bg-pink-500 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-pink-900/30">登録（出荷情報登録待ちへ）</button>
                                        </form>
                                        @if($project->device_count && $project->arrival_count !== null)
                                            <p class="text-xs mt-3 {{ (int) $project->arrival_count >= (int) $project->device_count ? 'text-green-400' : 'text-amber-400' }}">
                                                受注台数 {{ number_format($project->device_count) }} 台中 {{ number_format($project->arrival_count) }} 台が入荷済みです。
                                            </p>
                                        @endif
                                    @endif
                                </div>

                                {{-- 付属品の入荷数（付属品有無＝有のとき） --}}
                                @if($project->has_accessory === '有' && $project->projectAccessories->count() > 0)
                                <div class="pt-6 border-t border-white/10">
                                    <h4 class="text-sm font-bold text-pink-300 mb-3">付属品の入荷</h4>
                                    <form action="{{ route('projects.accessories.arrivals', $project) }}" method="POST" class="space-y-2">
                                        @csrf
                                        @foreach($project->projectAccessories as $pa)
                                            <div class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-2.5 border border-white/10">
                                                <span class="text-sm text-slate-200 flex-1">{{ $pa->accessory->name ?? '（削除済み）' }}</span>
                                                <input type="number" name="accessories[{{ $pa->id }}][arrived_count]" value="{{ $pa->arrived_count }}" min="0" max="{{ $pa->planned_count }}" class="w-24 bg-black/40 border-white/30 rounded-lg text-white px-3 py-1.5 text-sm font-mono">
                                                <span class="text-xs font-mono {{ $pa->arrived_count >= $pa->planned_count ? 'text-green-400' : 'text-amber-400' }}">/ {{ number_format($pa->planned_count) }} 台</span>
                                            </div>
                                        @endforeach
                                        <button type="submit" class="mt-2 bg-pink-600 hover:bg-pink-500 text-white font-bold py-2.5 px-6 rounded-xl transition-all text-sm">付属品の入荷数を保存</button>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif


                    {{-- ▼ STEP 5: 受注情報 ▼ --}}
                    @if($orderRank >= 1)
                    <div class="relative mb-12" x-data="{ open: {{ $orderRank == 1 ? 'true' : 'false' }} }">
                        <div class="absolute -left-4 -top-4 bg-emerald-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-emerald-400/50">STEP 5</div>
                        <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3" :class="open ? 'mb-6' : ''">
                                <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>受注情報
                                @if($orderRank > 1)<span class="text-xs bg-green-500/20 text-green-300 border border-green-500/40 px-2.5 py-0.5 rounded-full">確定済み</span>@endif
                                <button type="button" @click="open = !open" class="ml-auto p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/10 transition-all">
                                    <svg class="w-5 h-5 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </h3>
                            <div x-show="open" class="space-y-6">
                                {{-- 請負先見積書（受注書）のアップロード --}}
                                <div>
                                    <h4 class="text-sm font-bold text-emerald-300 mb-3">請負先見積書（受注書）のアップロード</h4>
                                    <form action="{{ route('projects.order_files.store', $project) }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row items-stretch md:items-center gap-3 mb-3">
                                        @csrf
                                        <input type="hidden" name="category" value="order_form">
                                        <input type="file" name="order_files[]" multiple required class="flex-grow block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-5 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500 cursor-pointer bg-black/40 rounded-xl border border-white/30">
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 px-6 rounded-xl transition-all text-sm whitespace-nowrap">アップロード</button>
                                    </form>
                                    @if($orderFiles->count() > 0)
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            @foreach($orderFiles as $file)
                                                <div class="flex items-center justify-between bg-white/5 p-3 rounded-xl border border-white/10">
                                                    <span class="text-xs text-slate-300 truncate mr-2">{{ $file->file_name }}</span>
                                                    <div class="flex gap-3 shrink-0">
                                                        <a href="{{ route('projects.files.download', $file) }}" class="text-emerald-400 text-xs hover:underline bg-emerald-500/10 px-3 py-1 rounded">DL</a>
                                                        <button type="button" onclick="if(confirm('このファイルを削除しますか？')) document.getElementById('delete-file-form-{{ $file->id }}').submit();" class="text-red-400 text-xs hover:underline cursor-pointer">削除</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                {{-- 受注情報の確定フォーム --}}
                                <div class="pt-6 border-t border-white/10">
                                    <form action="{{ route('projects.order_info.confirm', $project) }}" method="POST" class="space-y-4">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1.5">機種名 <span class="text-red-400">*</span></label>
                                                <input type="text" name="device_model" value="{{ $project->device_model }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-emerald-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1.5">台数 <span class="text-red-400">*</span></label>
                                                <input type="number" name="device_count" value="{{ $project->device_count }}" min="1" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-emerald-500 font-mono">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1.5">開始予定日 <span class="text-red-400">*</span></label>
                                                <input type="date" name="contract_date" value="{{ $project->contract_date }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 [color-scheme:dark]">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1.5">納品予定日 <span class="text-red-400">*</span></label>
                                                <input type="date" name="completion_date" value="{{ $project->completion_date }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 [color-scheme:dark]">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1.5">納品方法 <span class="text-red-400">*</span></label>
                                                <select name="delivery_method" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-emerald-500 cursor-pointer">
                                                    <option value="">選択してください</option>
                                                    @foreach(\App\Models\Project::METHOD_OPTIONS as $m)
                                                        <option value="{{ $m }}" {{ $project->delivery_method === $m ? 'selected' : '' }}>{{ $m }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3.5 rounded-xl shadow-lg transition-all">受注書作成（受注情報を確定）</button>
                                    </form>
                                </div>

                                {{-- 付属品（付属品有無＝有のとき） --}}
                                @if($project->has_accessory === '有')
                                <div class="pt-6 border-t border-white/10">
                                    <h4 class="text-sm font-bold text-emerald-300 mb-3">付属品</h4>
                                    @if($project->projectAccessories->count() > 0)
                                        <div class="space-y-2 mb-3">
                                            @foreach($project->projectAccessories as $pa)
                                                <div class="flex items-center justify-between bg-white/5 rounded-xl px-4 py-2.5 border border-white/10">
                                                    <span class="text-sm text-slate-200">{{ $pa->accessory->name ?? '（削除済み）' }}</span>
                                                    <span class="text-sm font-mono text-white">{{ number_format($pa->planned_count) }} 台</span>
                                                    <form action="{{ route('projects.accessories.delete', [$project, $pa]) }}" method="POST" onsubmit="return confirm('この付属品を削除しますか？');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-red-400 text-xs hover:underline">削除</button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-500 italic mb-3">付属品がまだ登録されていません。</p>
                                    @endif
                                    <form action="{{ route('projects.accessories.add', $project) }}" method="POST" class="flex flex-col md:flex-row items-stretch md:items-end gap-3">
                                        @csrf
                                        <div class="flex-1">
                                            <label class="block text-[11px] font-bold text-slate-400 mb-1">商品名</label>
                                            <select name="accessory_id" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-3 py-2 text-sm cursor-pointer">
                                                <option value="">選択してください</option>
                                                @foreach($accessoryMaster as $acc)
                                                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="w-full md:w-40">
                                            <label class="block text-[11px] font-bold text-slate-400 mb-1">台数</label>
                                            <input type="number" name="planned_count" min="1" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-3 py-2 text-sm font-mono">
                                        </div>
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-5 rounded-xl transition-all text-sm whitespace-nowrap">付属品を追加</button>
                                    </form>
                                    @if($accessoryMaster->isEmpty())
                                        <p class="text-[11px] text-amber-400 mt-2">付属品マスタが空です。マスター設定の「付属品マスタ」で登録してください。</p>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif



                    <div class="space-y-12">

            {{-- STEP 4: 見積確定・選定 --}}
            @if($isAllAnswered || $currentRank >= 4)
            @php
                $hasPendingProjectRequest = $project->editRequests->where('status', 'pending')->isNotEmpty();
                $latestProjectRequest = $project->editRequests->first();
            @endphp
            <div class="relative" x-data="{ requestModal4: false, historyModal4: false, decisionModal: null, open: {{ $isOrdered ? 'false' : 'true' }} }">
                <div class="absolute -left-4 -top-4 bg-purple-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-purple-400/50">STEP 4</div>
                <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                    <div class="flex items-center justify-between gap-3" :class="open ? 'mb-8' : ''">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <span class="w-2 h-8 bg-purple-500 rounded-full"></span>最終見積・発注先の選定
                            {{-- 変更履歴アイコン --}}
                            <button type="button" x-show="open" @if($isOrdered) x-cloak @endif @click="historyModal4 = true" title="最終見積の変更履歴"
                                    class="relative p-1.5 rounded-lg bg-white/5 hover:bg-sky-500/20 text-slate-400 hover:text-sky-300 border border-white/10 hover:border-sky-500/40 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @if($project->priceHistories->count() > 0)
                                    <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-sky-500 text-white text-[9px] font-black rounded-full flex items-center justify-center">{{ $project->priceHistories->count() }}</span>
                                @endif
                            </button>
                        </h3>
                        @if($isStep4Completed)
                            @if($hasPendingProjectRequest)
                                {{-- 承認待ちの申請がある --}}
                                <span x-show="open" @if($isOrdered) x-cloak @endif class="text-xs bg-amber-500/20 text-amber-300 font-bold px-3 py-1.5 rounded-lg border border-amber-500/40 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    承認待ち
                                </span>
                            @else
                                <div x-show="open" @if($isOrdered) x-cloak @endif class="flex items-center gap-2">
                                    @if($latestProjectRequest && $latestProjectRequest->status === 'rejected')
                                        <span class="text-[10px] bg-red-500/20 text-red-300 font-bold px-2.5 py-1 rounded-lg border border-red-500/40">前回の申請は却下されました</span>
                                    @endif
                                    <button type="button" @click="requestModal4 = true"
                                            class="text-sm bg-white/10 hover:bg-white/20 text-purple-300 font-bold px-4 py-2 rounded-lg border border-white/20 transition-all flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        編集申請
                                    </button>
                                </div>
                            @endif
                        @endif
                        <button type="button" @click="open = !open" class="p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/10 transition-all">
                            <svg class="w-5 h-5 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>

                    <div x-show="open" @if($isOrdered) x-cloak @endif>
                    {{-- ▼ STEP 4 表示モード ▼ --}}
                    @if($isStep4Completed)
                    <div id="display-mode-step4" class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-black/20 p-6 rounded-2xl border border-white/5">
                        <div>
                            <div class="text-xs font-bold text-slate-400 mb-1">顧客への提示金額 (税抜)</div>
                            <div class="text-white font-mono font-bold text-2xl text-purple-300">¥{{ number_format($project->final_price) }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-400 mb-1">採用した依頼先企業</div>
                            <div class="text-white font-bold text-xl">{{ $project->partner_name }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-400 mb-1">送料</div>
                            @if($project->quote_shipping_enabled)
                                <div class="text-white font-mono font-bold text-xl">¥{{ number_format($project->quote_shipping_fee ?? 0) }}</div>
                            @else
                                <div class="text-slate-300 text-base">実費精算</div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- ▼ STEP 4 編集モード (フォーム) ▼ --}}
                    <div id="edit-mode-step4" class="{{ $isStep4Completed ? 'hidden' : '' }}">
                        <form action="{{ route('projects.update', $project) }}" method="POST" class="space-y-6"
                              x-data="{ shipping: {{ $project->quote_shipping_enabled ? 'true' : 'false' }} }">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">顧客への提示金額 (税抜)</label>
                                    <input type="number" name="final_price" value="{{ $project->final_price }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white pl-4 pr-4 py-3 focus:ring-purple-500 font-mono text-lg" placeholder="最終見積額を入力" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">採用する依頼先企業</label>
                                    <select name="selected_estimate_id" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-purple-500 cursor-pointer">
                                        <option value="">企業を選択してください</option>
                                        @foreach($project->estimates as $estimate)
                                            <option value="{{ $estimate->id }}" {{ $estimate->result === '受注' ? 'selected' : '' }}>
                                                {{ $estimate->partner_name }} (回答額: ¥{{ number_format($estimate->cost_price) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- 送料入力 --}}
                            <div class="bg-black/20 border border-white/10 rounded-2xl p-5">
                                <label class="block text-xs font-bold text-slate-400 mb-3 ml-1">送料入力</label>
                                <div class="flex gap-6 mb-1">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="quote_shipping_enabled" value="1" @change="shipping = true" {{ $project->quote_shipping_enabled ? 'checked' : '' }} class="text-purple-500 focus:ring-purple-500 bg-black/40 border-white/30">
                                        <span class="text-sm text-slate-300 font-bold">送料を入力する</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="quote_shipping_enabled" value="0" @change="shipping = false" {{ !$project->quote_shipping_enabled ? 'checked' : '' }} class="text-purple-500 focus:ring-purple-500 bg-black/40 border-white/30">
                                        <span class="text-sm text-slate-300 font-bold">入力しない（実費精算）</span>
                                    </label>
                                </div>
                                <div x-show="shipping" x-cloak class="mt-3">
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">送料（税抜）</label>
                                    <input type="number" name="quote_shipping_fee" value="{{ $project->quote_shipping_fee !== null ? (int) $project->quote_shipping_fee : '' }}" min="0" placeholder="送料を入力" class="w-full md:w-1/2 bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-purple-500 font-mono">
                                </div>
                                <p x-show="!shipping" x-cloak class="mt-2 text-xs text-slate-500">見積書の備考に「送料は実費精算となります」と記載されます。</p>
                            </div>

                            <div class="flex gap-4 mt-6">
                                @if($isStep4Completed)
                                    <button type="button" onclick="cancelEditMode('step4')" class="w-1/3 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-xl transition-all border border-white/10">キャンセル</button>
                                @endif
                                <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-500 text-white font-black py-4 rounded-xl shadow-lg text-lg">
                                    最終見積もりを確定し、結果待ちへ進む
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- 見積書PDF生成セクション --}}
                    @if($project->final_price)
                        <div class="mt-8 pt-6 border-t border-white/10">
                            <a href="{{ route('projects.quotation_pdf', $project) }}"
                               class="w-full bg-white/5 hover:bg-purple-600 text-purple-300 hover:text-white font-bold py-3 rounded-xl border border-purple-500/30 hover:border-purple-500 transition-all flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                見積書（PDF）を生成する
                            </a>
                            <p class="text-xs text-slate-500 text-center mt-2">自社担当者の印鑑が押印された見積書PDFがダウンロードされます。</p>
                        </div>

                        {{-- ▼ 最終判定：受注・失注 ▼ --}}
                        <div class="mt-6 pt-6 border-t border-white/10">
                            @if($project->status === '見積もり結果待ち')
                                <div class="text-center mb-4">
                                    <p class="text-sm font-bold text-slate-300">見積もりの最終結果を選択してください</p>
                                    <p class="text-xs text-slate-500 mt-1">顧客からの回答に応じて、受注または失注を確定します。</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <button type="button" @click="decisionModal = 'won'"
                                            class="bg-green-600/20 hover:bg-green-600 text-green-300 hover:text-white font-black py-4 rounded-xl border border-green-500/40 hover:border-green-500 transition-all text-lg flex items-center justify-center gap-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        受注
                                    </button>
                                    <button type="button" @click="decisionModal = 'lost'"
                                            class="bg-slate-700/40 hover:bg-slate-600 text-slate-400 hover:text-white font-black py-4 rounded-xl border border-slate-600 hover:border-slate-500 transition-all text-lg flex items-center justify-center gap-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        失注
                                    </button>
                                </div>
                            @elseif(in_array($project->status, \App\Models\Project::POST_ORDER_STATUSES))
                                <div class="flex items-center justify-center gap-3 bg-green-500/10 border border-green-500/30 rounded-2xl py-4">
                                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-green-300 font-black text-lg">受注済み</span>
                                    <span class="text-xs text-slate-400">（現在のステータス：{{ $project->status }}）</span>
                                </div>
                            @elseif($project->status === '失注')
                                <div class="flex items-center justify-center gap-3 bg-slate-800/60 border border-slate-600 rounded-2xl py-4">
                                    <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-slate-400 font-black text-lg">失注</span>
                                </div>
                            @endif
                        </div>
                    @endif
                    </div> {{-- /x-show open --}}
                </div>

                {{-- ▼ 受注・失注の確認モーダル ▼ --}}
                <div x-show="decisionModal !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    {{-- オーバーレイ --}}
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="decisionModal = null"></div>

                    {{-- モーダル本体 --}}
                    <div class="relative w-full max-w-md bg-slate-900 border border-white/15 rounded-3xl shadow-2xl overflow-hidden"
                         @keydown.escape.window="decisionModal = null">

                        {{-- 受注の確認 --}}
                        <template x-if="decisionModal === 'won'">
                            <div class="p-8 text-center">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-500/20 border border-green-500/40 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h4 class="text-xl font-black text-white mb-2">受注を確定しますか？</h4>
                                <p class="text-sm text-slate-400 mb-1">案件「{{ $project->name }}」（¥{{ number_format($project->final_price ?? 0) }}）</p>
                                <p class="text-xs text-slate-500 mb-6">ステータスが「<span class="text-pink-300 font-bold">物品入荷待ち</span>」に変更されます。</p>
                                <div class="flex gap-3">
                                    <button type="button" @click="decisionModal = null" class="w-1/2 bg-white/5 hover:bg-white/10 text-white font-bold py-3 rounded-xl border border-white/10 transition-all">キャンセル</button>
                                    <form action="{{ route('projects.decision', $project) }}" method="POST" class="w-1/2">
                                        @csrf
                                        <input type="hidden" name="decision" value="won">
                                        <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-black py-3 rounded-xl shadow-lg shadow-green-900/40 transition-all">受注を確定</button>
                                    </form>
                                </div>
                            </div>
                        </template>

                        {{-- 失注の確認 --}}
                        <template x-if="decisionModal === 'lost'">
                            <div class="p-8 text-center">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-700/60 border border-slate-600 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h4 class="text-xl font-black text-white mb-2">失注として確定しますか？</h4>
                                <p class="text-sm text-slate-400 mb-1">案件「{{ $project->name }}」</p>
                                <p class="text-xs text-slate-500 mb-6">ステータスが「<span class="text-slate-300 font-bold">失注</span>」に変更されます。</p>
                                <div class="flex gap-3">
                                    <button type="button" @click="decisionModal = null" class="w-1/2 bg-white/5 hover:bg-white/10 text-white font-bold py-3 rounded-xl border border-white/10 transition-all">キャンセル</button>
                                    <form action="{{ route('projects.decision', $project) }}" method="POST" class="w-1/2">
                                        @csrf
                                        <input type="hidden" name="decision" value="lost">
                                        <button type="submit" class="w-full bg-slate-600 hover:bg-slate-500 text-white font-black py-3 rounded-xl shadow-lg transition-all">失注を確定</button>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ▼ 最終見積の変更履歴モーダル ▼ --}}
                <div x-show="historyModal4" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    {{-- オーバーレイ --}}
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="historyModal4 = false"></div>

                    {{-- モーダル本体 --}}
                    <div class="relative w-full max-w-xl max-h-[85vh] bg-slate-900 border border-white/15 rounded-3xl shadow-2xl flex flex-col overflow-hidden"
                         @keydown.escape.window="historyModal4 = false">

                        {{-- ヘッダー --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-white/5 flex-shrink-0">
                            <h4 class="font-bold text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                最終見積の変更履歴
                            </h4>
                            <button type="button" @click="historyModal4 = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- 履歴リスト（新しい順） --}}
                        <div class="flex-1 overflow-y-auto p-6 space-y-3">
                            @forelse($project->priceHistories as $history)
                                <div class="bg-black/30 border border-white/10 rounded-2xl p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold text-sky-400 font-mono">{{ $history->created_at->format('Y/m/d H:i') }}</span>
                                        @if($history->approved_by)
                                            <span class="text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/40 px-2 py-0.5 rounded-full">承認による変更</span>
                                        @else
                                            <span class="text-[10px] font-black bg-slate-700 text-slate-300 border border-slate-600 px-2 py-0.5 rounded-full">{{ $history->old_final_price === null ? '初回確定' : '直接変更' }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-3 font-mono font-bold">
                                        @if($history->old_final_price !== null)
                                            <span class="text-slate-400 line-through">¥{{ number_format($history->old_final_price) }}</span>
                                            <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        @endif
                                        <span class="text-white text-lg">¥{{ number_format($history->new_final_price) }}</span>
                                    </div>
                                    @if($history->old_partner_name !== $history->new_partner_name)
                                        <div class="flex items-center gap-2 mt-1.5 text-xs">
                                            <span class="text-slate-500">採用企業：</span>
                                            @if($history->old_partner_name)
                                                <span class="text-slate-400 line-through">{{ $history->old_partner_name }}</span>
                                                <svg class="w-3 h-3 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                            @endif
                                            <span class="text-slate-200 font-bold">{{ $history->new_partner_name ?? '-' }}</span>
                                        </div>
                                    @elseif($history->new_partner_name)
                                        <div class="mt-1.5 text-xs text-slate-500">採用企業：<span class="text-slate-300">{{ $history->new_partner_name }}</span></div>
                                    @endif
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-xs text-slate-400">
                                        <span>変更：<span class="text-slate-200 font-bold">{{ $history->changedBy?->name ?? '不明' }}</span></span>
                                        @if($history->approvedBy)
                                            <span>承認：<span class="text-amber-300 font-bold">{{ $history->approvedBy->name }}</span></span>
                                        @endif
                                    </div>
                                    @if($history->reason)
                                        <div class="mt-2 text-xs text-slate-300 bg-white/5 border border-white/10 rounded-xl p-2.5 whitespace-pre-wrap">{{ $history->reason }}</div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-8 text-slate-500 text-sm">
                                    まだ変更履歴がありません。
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ▼ 最終見積の編集申請モーダル ▼ --}}
                <div x-show="requestModal4" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    {{-- オーバーレイ --}}
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="requestModal4 = false"></div>

                    {{-- モーダル本体 --}}
                    <div class="relative w-full max-w-lg bg-slate-900 border border-white/15 rounded-3xl shadow-2xl overflow-hidden"
                         @keydown.escape.window="requestModal4 = false">

                        {{-- ヘッダー --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-white/5">
                            <h4 class="font-bold text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                最終見積の編集申請
                            </h4>
                            <button type="button" @click="requestModal4 = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-5">

                            {{-- 申請者 → 上長 --}}
                            <div class="flex items-center justify-center gap-4 bg-black/30 border border-white/10 rounded-2xl p-4">
                                <div class="text-center">
                                    <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">申請者</div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                            <span class="text-xs font-black text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                                        </div>
                                        <span class="text-sm font-bold text-white">{{ auth()->user()->name }}</span>
                                    </div>
                                </div>
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <div class="text-center">
                                    <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">上長（承認者）</div>
                                    @if(auth()->user()->supervisor)
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                                                <span class="text-xs font-black text-white">{{ mb_substr(auth()->user()->supervisor->name, 0, 1) }}</span>
                                            </div>
                                            <span class="text-sm font-bold text-amber-300">{{ auth()->user()->supervisor->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-sm font-bold text-red-400">未登録</span>
                                    @endif
                                </div>
                            </div>

                            @if(auth()->user()->supervisor)
                                {{-- 申請フォーム --}}
                                <form action="{{ route('projects.edit_requests.store', $project) }}" method="POST" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1">現在の最終見積金額</label>
                                        <div class="text-white font-mono font-bold text-lg bg-black/30 border border-white/10 rounded-xl px-4 py-2.5">
                                            ¥{{ number_format($project->final_price ?? 0) }}
                                            <span class="text-xs text-slate-400 font-sans ml-3">採用企業：{{ $project->partner_name ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1">申請する金額 <span class="text-red-400">*</span></label>
                                        <input type="number" name="requested_final_price" value="{{ $project->final_price }}" required min="0"
                                               class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-purple-500 font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1">採用する依頼先企業 <span class="text-red-400">*</span></label>
                                        <select name="requested_estimate_id" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-purple-500 cursor-pointer">
                                            @foreach($project->estimates as $estimateOption)
                                                <option value="{{ $estimateOption->id }}" {{ $estimateOption->result === '受注' ? 'selected' : '' }}>
                                                    {{ $estimateOption->partner_name }} (回答額: ¥{{ number_format($estimateOption->cost_price) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1">編集理由 <span class="text-red-400">*</span></label>
                                        <textarea name="reason" rows="3" required placeholder="最終見積を変更する理由を入力してください"
                                                  class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-purple-500 placeholder-slate-600 text-sm"></textarea>
                                    </div>
                                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-500 text-white font-bold py-3 rounded-xl shadow-lg shadow-purple-900/30 transition-all">
                                        上長に申請する
                                    </button>
                                </form>
                            @else
                                {{-- 上長未登録の警告 --}}
                                <div class="bg-red-500/10 border border-red-500/30 rounded-2xl p-4 text-center">
                                    <p class="text-sm text-red-300 font-bold mb-1">上長が登録されていないため申請できません</p>
                                    <p class="text-xs text-slate-400">マスター設定のユーザー管理から上長を登録してください。</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- STEP 3: 回答保存 --}}
            @if($project->estimates->count() > 0)
            <div class="relative" x-data="{ exchangeModal: null, requestModal: null, historyModal: null, editing: null, open: {{ $isOrdered ? 'false' : 'true' }} }">
                <div class="absolute -left-4 -top-4 bg-emerald-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-emerald-400/50">STEP 3</div>
                <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                    @php $pricedCount = $project->estimates->whereNotNull('cost_price')->count(); @endphp
                    <div :class="open ? 'mb-8' : ''">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>各社からの見積回答を入力
                            <span class="text-xs {{ $pricedCount >= $estimatesCount ? 'bg-green-500/20 text-green-300 border-green-500/40' : 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' }} border px-2.5 py-0.5 rounded-full">金額入力 {{ $pricedCount }} / {{ $estimatesCount }} 社</span>
                            <button type="button" @click="open = !open" class="ml-auto p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/10 transition-all">
                                <svg class="w-5 h-5 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </h3>
                    </div>

                    {{-- ▼ 各社ごとのカード（表示・編集を個別に切り替え） ▼ --}}
                    <div class="space-y-4" x-show="open" @if($isOrdered) x-cloak @endif>
                        @foreach($project->estimates as $estimate)
                            <div class="p-6 bg-black/20 rounded-2xl border border-white/5 relative" data-estimate-card="{{ $estimate->id }}">

                                {{-- カードヘッダー：社名 + メモアイコン + 各社の編集ボタン --}}
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <span class="text-emerald-400 font-black text-lg tracking-widest">{{ $estimate->partner_name }}</span>
                                        @if($estimate->result === '受注')
                                            <span class="px-2.5 py-0.5 bg-green-500/20 text-green-300 border border-green-500/40 rounded-full text-[11px] font-black">受注</span>
                                        @elseif($estimate->result === '失注')
                                            <span class="px-2.5 py-0.5 bg-slate-600/40 text-slate-400 border border-slate-600 rounded-full text-[11px] font-black">失注</span>
                                        @endif
                                        {{-- やり取りメモアイコン --}}
                                        <button type="button" @click="exchangeModal = {{ $estimate->id }}" title="やり取りの記録"
                                                class="relative p-1.5 rounded-lg bg-white/5 hover:bg-emerald-500/20 text-slate-400 hover:text-emerald-300 border border-white/10 hover:border-emerald-500/40 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            @if($estimate->exchanges->count() > 0)
                                                <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-emerald-500 text-white text-[9px] font-black rounded-full flex items-center justify-center">{{ $estimate->exchanges->count() }}</span>
                                            @endif
                                        </button>
                                        {{-- 金額変更履歴アイコン --}}
                                        <button type="button" @click="historyModal = {{ $estimate->id }}" title="金額の変更履歴"
                                                class="relative p-1.5 rounded-lg bg-white/5 hover:bg-sky-500/20 text-slate-400 hover:text-sky-300 border border-white/10 hover:border-sky-500/40 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @if($estimate->priceHistories->count() > 0)
                                                <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-sky-500 text-white text-[9px] font-black rounded-full flex items-center justify-center">{{ $estimate->priceHistories->count() }}</span>
                                            @endif
                                        </button>
                                    </div>
                                    @if(!empty($estimate->cost_price))
                                        @php
                                            $hasPendingRequest = $estimate->editRequests->where('status', 'pending')->isNotEmpty();
                                            $latestRequest = $estimate->editRequests->first();
                                        @endphp
                                        @if($hasPendingRequest)
                                            {{-- 承認待ちの申請がある --}}
                                            <span class="text-xs bg-amber-500/20 text-amber-300 font-bold px-3 py-1.5 rounded-lg border border-amber-500/40 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                承認待ち
                                            </span>
                                        @else
                                            <div class="flex items-center gap-2">
                                                @if($latestRequest && $latestRequest->status === 'rejected')
                                                    <span class="text-[10px] bg-red-500/20 text-red-300 font-bold px-2.5 py-1 rounded-lg border border-red-500/40">前回の申請は却下されました</span>
                                                @endif
                                                <button type="button" @click="requestModal = {{ $estimate->id }}"
                                                        class="text-xs bg-white/10 hover:bg-white/20 text-emerald-300 font-bold px-3 py-1.5 rounded-lg border border-white/20 transition-all flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                    編集申請
                                                </button>
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                @if(empty($estimate->cost_price))
                                    {{-- ▼ 金額未登録：この社だけ保存（画面を再読込せず保存） ▼ --}}
                                    <form action="{{ route('projects.update', $project) }}" method="POST" class="space-y-4" data-draft-form data-step3-form>
                                        @csrf @method('PUT')
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 mb-1">見積金額 (税抜)</label>
                                            <input type="number" name="estimates[{{ $estimate->id }}][cost_price]" value="{{ old('estimates.' . $estimate->id . '.cost_price') }}" data-draft-key="est-{{ $project->id }}-{{ $estimate->id }}-cost" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-emerald-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 mb-1">備考</label>
                                            <div class="w-full bg-black/20 border border-white/10 rounded-xl text-slate-500 px-4 py-3 text-sm italic flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                見積金額の登録後に入力できます
                                            </div>
                                        </div>
                                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl shadow-lg transition-all">
                                            回答を保存する
                                        </button>
                                    </form>
                                @else
                                    {{-- ▼ 表示モード ▼ --}}
                                    <div x-show="editing !== {{ $estimate->id }}">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <div class="text-xs font-bold text-slate-400 mb-1">見積金額 (税抜)</div>
                                                <div class="text-white font-mono font-bold text-lg">¥{{ number_format($estimate->cost_price) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-slate-400 mb-1">備考</div>
                                                @if(filled($estimate->partner_message))
                                                    {{-- 備考保存済み：表示のみ（修正は「編集する」から） --}}
                                                    <div class="text-slate-300 text-sm whitespace-pre-wrap">{{ $estimate->partner_message }}</div>
                                                @else
                                                    {{-- 備考未入力：その場で入力（この社だけ保存・画面を再読込せず保存） --}}
                                                    <form action="{{ route('projects.update', $project) }}" method="POST" class="flex flex-col gap-2" data-draft-form data-step3-form>
                                                        @csrf @method('PUT')
                                                        <textarea name="estimates[{{ $estimate->id }}][partner_message]" rows="2" placeholder="備考を入力" data-draft-key="est-{{ $project->id }}-{{ $estimate->id }}-msg" class="w-full bg-black/40 border-white/20 rounded-xl text-slate-200 px-3 py-2 text-sm placeholder-slate-600 focus:ring-emerald-500">{{ old('estimates.' . $estimate->id . '.partner_message') }}</textarea>
                                                        <button type="submit" class="self-end bg-emerald-600/80 hover:bg-emerald-500 text-white font-bold py-1.5 px-5 rounded-lg transition-all text-xs">
                                                            備考を保存
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ▼ 編集モード（この会社だけ） ▼ --}}
                                    <div x-show="editing === {{ $estimate->id }}" x-cloak>
                                        <form action="{{ route('projects.update', $project) }}" method="POST" class="space-y-4" data-step3-form>
                                            @csrf @method('PUT')
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1">見積金額 (税抜)</label>
                                                <input type="number" name="estimates[{{ $estimate->id }}][cost_price]" value="{{ $estimate->cost_price }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-emerald-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-400 mb-1">備考</label>
                                                <textarea name="estimates[{{ $estimate->id }}][partner_message]" rows="2" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-emerald-500">{{ $estimate->partner_message }}</textarea>
                                            </div>
                                            <div class="flex gap-3">
                                                <button type="button" @click="editing = null" class="w-1/3 bg-white/5 hover:bg-white/10 text-white font-bold py-3 rounded-xl transition-all border border-white/10">キャンセル</button>
                                                <button type="submit" @click="editing = null" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl shadow-lg transition-all">
                                                    保存する
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ▼ 金額変更履歴モーダル（各社ごと） ▼ --}}
                @foreach($project->estimates as $estimate)
                    <div x-show="historyModal === {{ $estimate->id }}" x-cloak data-estimate-modal="history-{{ $estimate->id }}"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        {{-- オーバーレイ --}}
                        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="historyModal = null"></div>

                        {{-- モーダル本体 --}}
                        <div class="relative w-full max-w-xl max-h-[85vh] bg-slate-900 border border-white/15 rounded-3xl shadow-2xl flex flex-col overflow-hidden"
                             @keydown.escape.window="historyModal = null">

                            {{-- ヘッダー --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-white/5 flex-shrink-0">
                                <h4 class="font-bold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $estimate->partner_name }} の金額変更履歴
                                </h4>
                                <button type="button" @click="historyModal = null" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            {{-- 履歴リスト（新しい順） --}}
                            <div class="flex-1 overflow-y-auto p-6 space-y-3">
                                @forelse($estimate->priceHistories as $history)
                                    <div class="bg-black/30 border border-white/10 rounded-2xl p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-bold text-sky-400 font-mono">{{ $history->created_at->format('Y/m/d H:i') }}</span>
                                            @if($history->approved_by)
                                                <span class="text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/40 px-2 py-0.5 rounded-full">承認による変更</span>
                                            @else
                                                <span class="text-[10px] font-black bg-slate-700 text-slate-300 border border-slate-600 px-2 py-0.5 rounded-full">{{ $history->old_cost_price === null ? '初回登録' : '直接変更' }}</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-3 font-mono font-bold">
                                            @if($history->old_cost_price !== null)
                                                <span class="text-slate-400 line-through">¥{{ number_format($history->old_cost_price) }}</span>
                                                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                            @endif
                                            <span class="text-white text-lg">¥{{ number_format($history->new_cost_price) }}</span>
                                        </div>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-xs text-slate-400">
                                            <span>変更：<span class="text-slate-200 font-bold">{{ $history->changedBy?->name ?? '不明' }}</span></span>
                                            @if($history->approvedBy)
                                                <span>承認：<span class="text-amber-300 font-bold">{{ $history->approvedBy->name }}</span></span>
                                            @endif
                                        </div>
                                        @if($history->reason)
                                            <div class="mt-2 text-xs text-slate-300 bg-white/5 border border-white/10 rounded-xl p-2.5 whitespace-pre-wrap">{{ $history->reason }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-center py-8 text-slate-500 text-sm">
                                        まだ変更履歴がありません。
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- ▼ 編集申請モーダル（各社ごと） ▼ --}}
                @foreach($project->estimates as $estimate)
                    <div x-show="requestModal === {{ $estimate->id }}" x-cloak data-estimate-modal="request-{{ $estimate->id }}"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        {{-- オーバーレイ --}}
                        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="requestModal = null"></div>

                        {{-- モーダル本体 --}}
                        <div class="relative w-full max-w-lg bg-slate-900 border border-white/15 rounded-3xl shadow-2xl overflow-hidden"
                             @keydown.escape.window="requestModal = null">

                            {{-- ヘッダー --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-white/5">
                                <h4 class="font-bold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                    編集申請 — {{ $estimate->partner_name }}
                                </h4>
                                <button type="button" @click="requestModal = null" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div class="p-6 space-y-5">

                                {{-- 申請者 → 上長 --}}
                                <div class="flex items-center justify-center gap-4 bg-black/30 border border-white/10 rounded-2xl p-4">
                                    <div class="text-center">
                                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">申請者</div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                                <span class="text-xs font-black text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                                            </div>
                                            <span class="text-sm font-bold text-white">{{ auth()->user()->name }}</span>
                                        </div>
                                    </div>
                                    <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    <div class="text-center">
                                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">上長（承認者）</div>
                                        @if(auth()->user()->supervisor)
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                                                    <span class="text-xs font-black text-white">{{ mb_substr(auth()->user()->supervisor->name, 0, 1) }}</span>
                                                </div>
                                                <span class="text-sm font-bold text-amber-300">{{ auth()->user()->supervisor->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-sm font-bold text-red-400">未登録</span>
                                        @endif
                                    </div>
                                </div>

                                @if(auth()->user()->supervisor)
                                    {{-- 申請フォーム --}}
                                    <form action="{{ route('projects.estimates.edit_requests.store', $estimate) }}" method="POST" class="space-y-4">
                                        @csrf
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 mb-1">現在の見積金額</label>
                                            <div class="text-white font-mono font-bold text-lg bg-black/30 border border-white/10 rounded-xl px-4 py-2.5">
                                                ¥{{ number_format($estimate->cost_price) }}
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 mb-1">申請する金額 <span class="text-red-400">*</span></label>
                                            <input type="number" name="requested_cost_price" value="{{ $estimate->cost_price }}" required min="0"
                                                   class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-amber-500 font-mono">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 mb-1">編集理由 <span class="text-red-400">*</span></label>
                                            <textarea name="reason" rows="3" required placeholder="金額を変更する理由を入力してください"
                                                      class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-amber-500 placeholder-slate-600 text-sm"></textarea>
                                        </div>
                                        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 rounded-xl shadow-lg shadow-amber-900/30 transition-all">
                                            上長に申請する
                                        </button>
                                    </form>
                                @else
                                    {{-- 上長未登録の警告 --}}
                                    <div class="bg-red-500/10 border border-red-500/30 rounded-2xl p-4 text-center">
                                        <p class="text-sm text-red-300 font-bold mb-1">上長が登録されていないため申請できません</p>
                                        <p class="text-xs text-slate-400">マスター設定のユーザー管理から上長を登録してください。</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- ▼ やり取り記録モーダル（各社ごと） ▼ --}}
                @foreach($project->estimates as $estimate)
                    <div x-show="exchangeModal === {{ $estimate->id }}" x-cloak data-estimate-modal="exchange-{{ $estimate->id }}"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        {{-- オーバーレイ --}}
                        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="exchangeModal = null"></div>

                        {{-- モーダル本体 --}}
                        <div class="relative w-full max-w-2xl max-h-[85vh] bg-slate-900 border border-white/15 rounded-3xl shadow-2xl flex flex-col overflow-hidden"
                             @keydown.escape.window="exchangeModal = null">

                            {{-- ヘッダー --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-white/5 flex-shrink-0">
                                <h4 class="font-bold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    {{ $estimate->partner_name }} とのやり取り
                                </h4>
                                <button type="button" @click="exchangeModal = null" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            {{-- やり取り履歴 --}}
                            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                                @forelse($estimate->exchanges as $exchange)
                                    <div class="bg-black/30 border border-white/10 rounded-2xl p-4 space-y-3">
                                        <div class="text-xs font-bold text-emerald-400 font-mono">
                                            {{ \Carbon\Carbon::parse($exchange->exchanged_at)->format('Y/m/d') }}
                                        </div>
                                        @if($exchange->inquiry)
                                            <div>
                                                <div class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">問い合わせ</div>
                                                <div class="text-sm text-slate-200 whitespace-pre-wrap bg-blue-500/10 border border-blue-500/20 rounded-xl p-3">{{ $exchange->inquiry }}</div>
                                            </div>
                                        @endif
                                        @if($exchange->reply)
                                            <div>
                                                <div class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-1">回答</div>
                                                <div class="text-sm text-slate-200 whitespace-pre-wrap bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3">{{ $exchange->reply }}</div>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-center py-8 text-slate-500 text-sm">
                                        まだやり取りの記録がありません。
                                    </div>
                                @endforelse
                            </div>

                            {{-- 新規記録フォーム --}}
                            <div class="border-t border-white/10 bg-white/3 p-6 flex-shrink-0">
                                <form action="{{ route('projects.estimates.exchanges.store', $estimate) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="flex items-center gap-3">
                                        <label class="text-xs font-bold text-slate-400 whitespace-nowrap">日付</label>
                                        <input type="date" name="exchanged_at" value="{{ now()->format('Y-m-d') }}" required class="bg-black/40 border-white/30 rounded-xl text-white px-3 py-2 text-sm [color-scheme:dark]">
                                    </div>
                                    <textarea name="inquiry" rows="2" placeholder="問い合わせ内容" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-2.5 text-sm placeholder-slate-500"></textarea>
                                    <textarea name="reply" rows="2" placeholder="回答内容" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-2.5 text-sm placeholder-slate-500"></textarea>
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 rounded-xl transition-all text-sm">
                                        やり取りを記録する
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            {{-- STEP 2: メール生成 --}}
            @if($isStep1Completed || $currentRank >= 2)
            <div class="relative" x-data="{ open: {{ $isOrdered ? 'false' : 'true' }} }">
                <div class="absolute -left-4 -top-4 bg-orange-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-orange-400/50">STEP 2</div>
                <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                    <h3 class="text-xl font-bold text-white flex items-center gap-3" :class="open ? 'mb-6' : ''">
                        <span class="w-2 h-8 bg-orange-500 rounded-full"></span>見積もり依頼メールの生成
                        @if($estimatesCount > 0)
                            <span class="text-xs bg-orange-500/20 text-orange-300 border border-orange-500/40 px-2.5 py-0.5 rounded-full">依頼数 {{ $estimatesCount }} 社</span>
                        @endif
                        <button type="button" @click="open = !open" class="ml-auto p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/10 transition-all">
                            <svg class="w-5 h-5 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </h3>
                    <div x-show="open" @if($isOrdered) x-cloak @endif>
                    <form action="{{ route('projects.generate_email', $project) }}" method="POST" class="flex gap-4 items-end">
                        @csrf
                        <div class="flex-grow">
                            <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">依頼先企業を選択</label>
                            <select name="partner_name" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-orange-500 cursor-pointer">
                                @foreach($partners as $partner)
                                    <option value="{{ $partner }}">{{ $partner }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white font-black py-3 px-8 rounded-xl transition-all shadow-lg active:scale-95">
                            メール文面を生成
                        </button>
                    </form>

                    @if(session('generated_email'))
                        <div class="mt-6 p-6 bg-black/40 rounded-2xl border border-orange-500/30 relative group">
                            <pre class="text-sm text-orange-100 whitespace-pre-wrap font-mono">{{ session('generated_email') }}</pre>
                            <button onclick="navigator.clipboard.writeText({{ json_encode(session('generated_email')) }}).then(() => alert('コピーしました'))" class="absolute top-4 right-4 bg-orange-500/20 text-orange-300 text-xs py-1 px-3 rounded-lg border border-orange-500/30 hover:bg-orange-500 hover:text-white transition-all">コピー</button>
                        </div>
                    @endif
                    </div> {{-- /x-show open --}}
                </div>
            </div>
            @endif

            {{-- STEP 1: 案件詳細 --}}
            <div class="relative" x-data="{ open: {{ $isOrdered ? 'false' : 'true' }} }">
                <div class="absolute -left-4 -top-4 bg-blue-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-blue-400/50">STEP 1</div>
                <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                    <div class="flex items-center justify-between gap-3" :class="open ? 'mb-8' : ''">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <span class="w-2 h-8 bg-blue-500 rounded-full"></span>案件の基本情報と手順書
                        </h3>
                        <div class="flex items-center gap-2">
                            @if($isStep1Completed)
                                <button type="button" x-show="open" @if($isOrdered) x-cloak @endif onclick="toggleEditMode('step1')" id="btn-edit-step1" class="text-sm bg-white/10 hover:bg-white/20 text-blue-300 font-bold px-4 py-2 rounded-lg border border-white/20 transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    編集する
                                </button>
                            @endif
                            <button type="button" @click="open = !open" class="p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/10 transition-all">
                                <svg class="w-5 h-5 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                    </div>

                    <div x-show="open" @if($isOrdered) x-cloak @endif>
                    {{-- ▼ STEP 1 表示モード ▼ --}}
                    @if($isStep1Completed)
                    <div id="display-mode-step1" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-black/20 p-6 rounded-2xl border border-white/5">
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">機種名</div>
                                <div class="text-white text-lg font-bold">{{ $project->device_model }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">OS</div>
                                <div class="text-white text-lg font-bold">{{ $project->os }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">台数</div>
                                <div class="text-white text-lg font-bold font-mono">{{ number_format($project->device_count) }} 台</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">付属品有無</div>
                                <div class="text-white text-lg">{{ $project->has_accessory ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">開始予定日</div>
                                <div class="text-white text-lg">{{ \Carbon\Carbon::parse($project->contract_date)->format('Y/m/d') }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">入荷方法</div>
                                <div class="text-white text-lg">{{ $project->arrival_method ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">納品予定日</div>
                                <div class="text-white text-lg">{{ \Carbon\Carbon::parse($project->completion_date)->format('Y/m/d') }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">納品方法</div>
                                <div class="text-white text-lg">{{ $project->delivery_method ?? '-' }}</div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-white/10">
                            <div class="text-xs font-bold text-slate-400 mb-4 ml-1 uppercase tracking-widest">手順書</div>
                            @if($project->parameter_input_type === 'text' && filled($project->parameter_text))
                                <div class="bg-black/20 p-4 rounded-xl border border-white/5 text-slate-300 whitespace-pre-wrap text-sm">{{ $project->parameter_text }}</div>
                            @elseif($parameterFiles->count() > 0)
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($parameterFiles as $file)
                                        <div class="flex items-center justify-between bg-white/5 p-3 rounded-xl border border-white/10">
                                            <span class="text-xs text-slate-300 truncate mr-2">{{ $file->file_name }}</span>
                                            <a href="{{ route('projects.files.download', $file) }}" class="text-blue-400 text-xs hover:underline bg-blue-500/10 px-3 py-1 rounded">ダウンロード</a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-sm text-slate-500 italic">手順書はありません</div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- ▼ STEP 1 編集モード (フォーム) ▼ --}}
                    <div id="edit-mode-step1" class="{{ $isStep1Completed ? 'hidden' : '' }}">
                        <form action="{{ route('projects.update', $project) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">機種名</label>
                                    <input type="text" name="device_model" value="{{ $project->device_model }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">OS</label>
                                    <select name="os" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-blue-500 cursor-pointer">
                                        <option value="">選択してください</option>
                                        @foreach(\App\Models\Project::OS_OPTIONS as $os)
                                            <option value="{{ $os }}" {{ $project->os === $os ? 'selected' : '' }}>{{ $os }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">台数</label>
                                    <input type="number" name="device_count" value="{{ $project->device_count }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">付属品有無</label>
                                    <select name="has_accessory" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-blue-500 cursor-pointer">
                                        <option value="">選択してください</option>
                                        @foreach(\App\Models\Project::ACCESSORY_OPTIONS as $acc)
                                            <option value="{{ $acc }}" {{ $project->has_accessory === $acc ? 'selected' : '' }}>{{ $acc }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">開始予定日</label>
                                    <input type="date" name="contract_date" value="{{ $project->contract_date }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 [color-scheme:dark]">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">入荷方法</label>
                                    <select name="arrival_method" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-blue-500 cursor-pointer">
                                        <option value="">選択してください</option>
                                        @foreach(\App\Models\Project::METHOD_OPTIONS as $m)
                                            <option value="{{ $m }}" {{ $project->arrival_method === $m ? 'selected' : '' }}>{{ $m }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">納品予定日</label>
                                    <input type="date" name="completion_date" value="{{ $project->completion_date }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 [color-scheme:dark]">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">納品方法</label>
                                    <select name="delivery_method" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-blue-500 cursor-pointer">
                                        <option value="">選択してください</option>
                                        @foreach(\App\Models\Project::METHOD_OPTIONS as $m)
                                            <option value="{{ $m }}" {{ $project->delivery_method === $m ? 'selected' : '' }}>{{ $m }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-white/10">
                                <label class="block text-xs font-bold text-slate-400 mb-4 ml-1 uppercase tracking-widest">手順書</label>
                                <div class="flex gap-6 mb-4 ml-1">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="parameter_input_type" value="file" class="text-blue-500 focus:ring-blue-500 bg-black/40 border-white/30" onchange="toggleParameterInput(this.value)" {{ $project->parameter_input_type !== 'text' ? 'checked' : '' }}>
                                        <span class="text-sm text-slate-300 font-bold">ファイルアップロード</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="parameter_input_type" value="text" class="text-blue-500 focus:ring-blue-500 bg-black/40 border-white/30" onchange="toggleParameterInput(this.value)" {{ $project->parameter_input_type === 'text' ? 'checked' : '' }}>
                                        <span class="text-sm text-slate-300 font-bold">テキスト入力</span>
                                    </label>
                                </div>

                                <div id="parameter-file-area" class="{{ $project->parameter_input_type === 'text' ? 'hidden' : 'block' }}">
                                    <input type="file" name="parameter_files[]" multiple class="block w-full text-sm text-slate-400 file:mr-4 file:py-2.5 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                                    @if($parameterFiles->count() > 0)
                                        <div class="mt-4 grid grid-cols-2 gap-2">
                                            @foreach($parameterFiles as $file)
                                                <div class="flex items-center justify-between bg-white/5 p-3 rounded-xl border border-white/10">
                                                    <span class="text-xs text-slate-300 truncate mr-2">{{ $file->file_name }}</span>
                                                    <div class="flex gap-3">
                                                        <button type="button" onclick="document.getElementById('delete-file-form-{{ $file->id }}').submit();" class="text-red-400 text-xs hover:underline cursor-pointer">削除</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div id="parameter-text-area" class="{{ $project->parameter_input_type === 'text' ? 'block' : 'hidden' }}">
                                    <textarea name="parameter_text" rows="4" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-blue-500 placeholder-slate-500" placeholder="パラメーター情報を直接入力してください">{{ $project->parameter_text }}</textarea>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                @if($isStep1Completed)
                                    <button type="button" onclick="cancelEditMode('step1')" class="w-1/3 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-xl transition-all border border-white/10">キャンセル</button>
                                @endif
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-xl transition-all shadow-lg active:scale-95 text-lg">
                                    STEP 1 情報を保存する
                                </button>
                            </div>
                        </form>
                    </div>
                    </div> {{-- /x-show open --}}
                </div>
            </div>

                    </div> {{-- /steps container --}}
                    </div> {{-- /max-w-4xl --}}
                </div> {{-- /flex-1 --}}
            </div> {{-- /lg:flex wrapper --}}

        </div>
    </div>

    {{-- UI切り替え用 JavaScript --}}
    <script>
        // パラメーター入力タイプの切り替え
        function toggleParameterInput(type) {
            const fileArea = document.getElementById('parameter-file-area');
            const textArea = document.getElementById('parameter-text-area');
            if (type === 'file') {
                fileArea.classList.remove('hidden');
                textArea.classList.add('hidden');
            } else {
                fileArea.classList.add('hidden');
                textArea.classList.remove('hidden');
            }
        }

        // 表示モードから編集モードへの切り替え
        function toggleEditMode(stepId) {
            document.getElementById(`display-mode-${stepId}`).classList.add('hidden');
            document.getElementById(`edit-mode-${stepId}`).classList.remove('hidden');
            document.getElementById(`btn-edit-${stepId}`).classList.add('hidden');
        }

        // 編集モードのキャンセル（元の表示モードに戻す）
        function cancelEditMode(stepId) {
            document.getElementById(`edit-mode-${stepId}`).classList.add('hidden');
            document.getElementById(`display-mode-${stepId}`).classList.remove('hidden');
            document.getElementById(`btn-edit-${stepId}`).classList.remove('hidden');
        }

        // STEP 3: 見積回答を「画面を再読込せず」に保存する（保存した社のカードだけ更新）
        (function () {
            const DRAFT_PREFIX = 'estDraft:';

            // 未保存の入力をブラウザに一時保存（万一の再読込でも消えないように）
            function restoreDrafts(root) {
                root.querySelectorAll('[data-draft-key]').forEach(el => {
                    const saved = localStorage.getItem(DRAFT_PREFIX + el.dataset.draftKey);
                    if (saved !== null && el.value === '') el.value = saved;
                });
            }

            // 簡易トースト
            function toast(message, ok = true) {
                const t = document.createElement('div');
                t.textContent = message;
                t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:9999;'
                    + 'padding:10px 22px;border-radius:9999px;font-size:13px;font-weight:bold;color:#fff;'
                    + 'box-shadow:0 8px 24px rgba(0,0,0,.4);'
                    + (ok ? 'background:#059669;' : 'background:#e11d48;');
                document.body.appendChild(t);
                setTimeout(() => t.remove(), 2600);
            }

            // 入力のたびに一時保存
            document.addEventListener('input', (e) => {
                const el = e.target;
                if (!el.dataset || !el.dataset.draftKey) return;
                const key = DRAFT_PREFIX + el.dataset.draftKey;
                if (el.value === '') localStorage.removeItem(key);
                else localStorage.setItem(key, el.value);
            });

            // STEP 3 フォームの送信を AJAX 化（画面遷移なし）
            document.addEventListener('submit', async (e) => {
                const form = e.target;
                if (!form.matches || !form.matches('form[data-step3-form]')) return;
                e.preventDefault();

                const btn = form.querySelector('button[type="submit"]');
                const label = btn ? btn.textContent : '';
                if (btn) { btn.disabled = true; btn.textContent = '保存中…'; }

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });

                    if (res.ok) {
                        // 保存できたら一時データを破棄
                        form.querySelectorAll('[data-draft-key]').forEach(el => {
                            localStorage.removeItem(DRAFT_PREFIX + el.dataset.draftKey);
                        });
                        // この社のカード＋関連モーダルだけ最新HTMLに差し替え（他社の入力欄はそのまま）
                        const card = form.closest('[data-estimate-card]');
                        if (card) {
                            const id = card.getAttribute('data-estimate-card');
                            const html = await (await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).text();
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const swap = (sel) => {
                                const cur = document.querySelector(sel);
                                const fr = doc.querySelector(sel);
                                if (cur && fr) cur.replaceWith(fr);
                            };
                            swap('[data-estimate-card="' + id + '"]');
                            swap('[data-estimate-modal="history-' + id + '"]');
                            swap('[data-estimate-modal="request-' + id + '"]');
                            swap('[data-estimate-modal="exchange-' + id + '"]');
                        }
                        toast('保存しました');
                    } else {
                        const data = await res.json().catch(() => ({}));
                        toast(data.message || '保存に失敗しました。', false);
                        if (btn) { btn.disabled = false; btn.textContent = label; }
                    }
                } catch (err) {
                    toast('通信エラーが発生しました。', false);
                    if (btn) { btn.disabled = false; btn.textContent = label; }
                }
            });

            document.addEventListener('DOMContentLoaded', () => restoreDrafts(document));
        })();

        // STEP 7: 出荷予定のカレンダー入力
        document.addEventListener('alpine:init', () => {
            Alpine.data('shipmentPlanner', (config) => ({
                remaining: config.remaining,
                isSplit: config.isSplit,
                viewYear: new Date().getFullYear(),
                viewMonth: new Date().getMonth(),
                selected: {}, // 'YYYY-MM-DD' => 台数
                pad(n) { return String(n).padStart(2, '0'); },
                get monthLabel() { return this.viewYear + '年 ' + (this.viewMonth + 1) + '月'; },
                get cells() {
                    const first = new Date(this.viewYear, this.viewMonth, 1).getDay();
                    const days = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                    const arr = [];
                    for (let i = 0; i < first; i++) arr.push(null);
                    for (let d = 1; d <= days; d++) arr.push(d);
                    return arr;
                },
                dateStr(d) { return this.viewYear + '-' + this.pad(this.viewMonth + 1) + '-' + this.pad(d); },
                isSelected(d) { return Object.prototype.hasOwnProperty.call(this.selected, this.dateStr(d)); },
                toggleDate(d) {
                    const key = (typeof d === 'number') ? this.dateStr(d) : d;
                    if (Object.prototype.hasOwnProperty.call(this.selected, key)) {
                        delete this.selected[key];
                    } else {
                        this.selected[key] = this.isSplit ? '' : String(this.remaining);
                    }
                },
                get selectedDates() { return Object.keys(this.selected).sort(); },
                get total() { return this.selectedDates.reduce((s, d) => s + (parseInt(this.selected[d]) || 0), 0); },
                get valid() {
                    return this.selectedDates.length > 0
                        && this.total >= 1 && this.total <= this.remaining
                        && this.selectedDates.every(d => (parseInt(this.selected[d]) || 0) > 0);
                },
                prevMonth() { if (this.viewMonth === 0) { this.viewMonth = 11; this.viewYear--; } else { this.viewMonth--; } },
                nextMonth() { if (this.viewMonth === 11) { this.viewMonth = 0; this.viewYear++; } else { this.viewMonth++; } },
                formatDate(str) {
                    const [y, m, d] = str.split('-').map(Number);
                    const wd = ['日', '月', '火', '水', '木', '金', '土'][new Date(y, m - 1, d).getDay()];
                    return y + '/' + this.pad(m) + '/' + this.pad(d) + '（' + wd + '）';
                },
            }));
        });
    </script>

    {{-- 削除用フォーム（隠し） --}}
    @foreach($project->files as $file)
        <form id="delete-file-form-{{ $file->id }}" action="{{ route('projects.files.delete', $file) }}" method="POST" class="hidden">
            @csrf @method('DELETE')
        </form>
    @endforeach
</x-app-layout>

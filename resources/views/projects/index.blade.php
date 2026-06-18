<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-white tracking-tight">案件管理台帳</h2>

            {{-- 案件登録は全ロール可能。ボタンはイベントを飛ばすだけで、モーダル本体は本文側（ヘッダーのbackdrop-blurの影響を避けるため） --}}
            <button type="button" x-data @click="$dispatch('open-project-create')"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 px-6 rounded-xl transition-all shadow-lg shadow-blue-600/30 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                案件登録
            </button>
        </div>
    </x-slot>

    <div class="py-8 min-h-screen">
        <div class="max-w-[98%] mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="bg-green-500/20 border border-green-500/50 text-green-200 p-4 rounded-2xl text-sm font-bold" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ▼ 案件登録モーダル ▼ --}}
            <div x-data="{ createModal: {{ $errors->any() ? 'true' : 'false' }}, paramType: '{{ old('parameter_input_type', 'file') }}' }"
                 @open-project-create.window="createModal = true">
                <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    {{-- オーバーレイ --}}
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="createModal = false"></div>

                    {{-- モーダル本体 --}}
                    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-slate-900 border border-white/15 rounded-3xl shadow-2xl"
                         @keydown.escape.window="createModal = false">

                        {{-- ヘッダー --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-white/5">
                            <h4 class="font-bold text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                新規案件の登録
                            </h4>
                            <button type="button" @click="createModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                            @csrf

                            @if ($errors->any())
                                <div class="bg-red-500/20 border border-red-500/50 text-red-200 p-3 rounded-xl text-xs" role="alert">
                                    <ul class="list-disc list-inside space-y-0.5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <p class="text-xs text-slate-400 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5">
                                案件番号は登録時に自動で採番されます（通番）。
                            </p>

                            {{-- ▼ 基本情報 ▼ --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1.5">依頼元（顧客） <span class="text-red-400">*</span></label>
                                    <select name="customer_id" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500 cursor-pointer">
                                        <option value="">顧客を選択してください</option>
                                        @foreach($customers ?? [] as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1.5">案件名 <span class="text-red-400">*</span></label>
                                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1.5">自社担当者 <span class="text-red-400">*</span></label>
                                    <select name="own_pic_id" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500 cursor-pointer">
                                        <option value="">担当者を選択してください</option>
                                        @foreach($users ?? [] as $userOption)
                                            <option value="{{ $userOption->id }}" {{ (string) old('own_pic_id', auth()->id()) === (string) $userOption->id ? 'selected' : '' }}>{{ $userOption->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1.5">相手担当者名</label>
                                    <input type="text" name="pic_name" value="{{ old('pic_name') }}" class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-400 mb-1.5">相手担当者メールアドレス</label>
                                    <input type="email" name="pic_email" value="{{ old('pic_email') }}" class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500">
                                </div>
                            </div>

                            {{-- ▼ 案件の基本情報（旧STEP 1） ▼ --}}
                            <div class="pt-4 border-t border-white/10">
                                <p class="text-xs font-black text-blue-400 uppercase tracking-widest mb-4">案件の基本情報</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1.5">機種名 <span class="text-red-400">*</span></label>
                                        <input type="text" name="device_model" value="{{ old('device_model') }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1.5">OS <span class="text-red-400">*</span></label>
                                        <select name="os" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500 cursor-pointer">
                                            <option value="">選択してください</option>
                                            @foreach(\App\Models\Project::OS_OPTIONS as $os)
                                                <option value="{{ $os }}" {{ old('os') === $os ? 'selected' : '' }}>{{ $os }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1.5">台数 <span class="text-red-400">*</span></label>
                                        <input type="number" name="device_count" value="{{ old('device_count') }}" min="1" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1.5">付属品有無 <span class="text-red-400">*</span></label>
                                        <select name="has_accessory" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500 cursor-pointer">
                                            <option value="">選択してください</option>
                                            @foreach(\App\Models\Project::ACCESSORY_OPTIONS as $acc)
                                                <option value="{{ $acc }}" {{ old('has_accessory') === $acc ? 'selected' : '' }}>{{ $acc }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1.5">開始予定日 <span class="text-red-400">*</span></label>
                                        <input type="date" name="contract_date" value="{{ old('contract_date') }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 [color-scheme:dark]">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1.5">入荷方法 <span class="text-red-400">*</span></label>
                                        <select name="arrival_method" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500 cursor-pointer">
                                            <option value="">選択してください</option>
                                            @foreach(\App\Models\Project::METHOD_OPTIONS as $m)
                                                <option value="{{ $m }}" {{ old('arrival_method') === $m ? 'selected' : '' }}>{{ $m }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1.5">納品予定日 <span class="text-red-400">*</span></label>
                                        <input type="date" name="completion_date" value="{{ old('completion_date') }}" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 [color-scheme:dark]">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 mb-1.5">納品方法 <span class="text-red-400">*</span></label>
                                        <select name="delivery_method" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500 cursor-pointer">
                                            <option value="">選択してください</option>
                                            @foreach(\App\Models\Project::METHOD_OPTIONS as $m)
                                                <option value="{{ $m }}" {{ old('delivery_method') === $m ? 'selected' : '' }}>{{ $m }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- ▼ 手順書 ▼ --}}
                            <div class="pt-4 border-t border-white/10">
                                <p class="text-xs font-black text-blue-400 uppercase tracking-widest mb-4">手順書</p>
                                <div class="flex gap-6 mb-3">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="parameter_input_type" value="file" x-model="paramType" class="text-blue-500 focus:ring-blue-500 bg-black/40 border-white/30">
                                        <span class="text-sm text-slate-300 font-bold">ファイルアップロード</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="parameter_input_type" value="text" x-model="paramType" class="text-blue-500 focus:ring-blue-500 bg-black/40 border-white/30">
                                        <span class="text-sm text-slate-300 font-bold">テキスト入力</span>
                                    </label>
                                </div>
                                <div x-show="paramType === 'file'">
                                    <input type="file" name="parameter_files[]" multiple class="block w-full text-sm text-slate-400 file:mr-4 file:py-2.5 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                                </div>
                                <div x-show="paramType === 'text'" x-cloak>
                                    <textarea name="parameter_text" rows="3" placeholder="手順書の内容を直接入力してください" class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-blue-500 placeholder-slate-600">{{ old('parameter_text') }}</textarea>
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-blue-600/30">
                                案件を登録する
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 overflow-hidden shadow-2xl">
                
                <form action="{{ route('projects.index') }}" method="GET" id="filter-form">
                    <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
                    <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-[16px] border-collapse">
                            <thead class="bg-white/5 text-slate-300 text-[16px] tracking-wider border-b border-white/10">
                                <tr>
                                    <th class="px-6 py-5 align-top w-28">
                                        @include('projects.partials.sort-link', ['label' => '案件番号', 'field' => 'project_number'])
                                    </th>
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
                                    <td class="px-6 py-5 font-mono text-sky-300 text-[16px] font-bold whitespace-nowrap">
                                        {{ $project->project_number ? str_pad($project->project_number, 6, '0', STR_PAD_LEFT) : '-' }}
                                    </td>
                                    <td class="px-6 py-5 font-bold text-white text-[16px]">{{ $project->name }}</td>
                                    
                                    <td class="px-6 py-5 text-sky-300 font-bold text-[16px] tracking-wider">{{ $project->customer->name ?? 'None' }}</td>
                                    
                                    <td class="px-6 py-5">
                                        @php
                                            $statusColor = match($project->status) {
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
                                <tr><td colspan="10" class="px-6 py-20 text-center text-slate-400 text-sm">案件が見つかりません。</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
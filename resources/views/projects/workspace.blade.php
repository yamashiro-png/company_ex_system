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
                         && !empty($project->device_count) 
                         && !empty($project->contract_date) 
                         && !empty($project->completion_date) 
                         && !empty($project->price);

        $estimatesCount = $project->estimates->count();
        $isAllAnswered = $estimatesCount > 0 && $project->estimates->every(function ($estimate) {
            return !empty($estimate->cost_price) || !empty($estimate->partner_completion_date) || !empty($estimate->partner_message);
        });

        $hasAnyAnswer = $estimatesCount > 0 && $project->estimates->contains(function ($estimate) {
            return !empty($estimate->cost_price) || !empty($estimate->partner_completion_date) || !empty($estimate->partner_message);
        });

        $isStep4Completed = !empty($project->final_price);
    @endphp

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-12">
            
            {{-- STEP 1: 案件詳細 --}}
            <div class="relative">
                <div class="absolute -left-4 -top-4 bg-blue-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-blue-400/50">STEP 1</div>
                <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <span class="w-2 h-8 bg-blue-500 rounded-full"></span>案件の基本情報とパラメーター
                        </h3>
                        @if($isStep1Completed)
                            <button type="button" onclick="toggleEditMode('step1')" id="btn-edit-step1" class="text-sm bg-white/10 hover:bg-white/20 text-blue-300 font-bold px-4 py-2 rounded-lg border border-white/20 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                編集する
                            </button>
                        @endif
                    </div>

                    {{-- ▼ STEP 1 表示モード ▼ --}}
                    @if($isStep1Completed)
                    <div id="display-mode-step1" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-black/20 p-6 rounded-2xl border border-white/5">
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">対象機種名</div>
                                <div class="text-white text-lg font-bold">{{ $project->device_model }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">台数</div>
                                <div class="text-white text-lg font-bold font-mono">{{ number_format($project->device_count) }} 台</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">契約日</div>
                                <div class="text-white text-lg">{{ \Carbon\Carbon::parse($project->contract_date)->format('Y/m/d') }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 mb-1">完了予定日</div>
                                <div class="text-white text-lg">{{ \Carbon\Carbon::parse($project->completion_date)->format('Y/m/d') }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <div class="text-xs font-bold text-slate-400 mb-1">予算（暫定単価）</div>
                                <div class="text-white text-xl font-bold font-mono">¥{{ number_format($project->price) }}</div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-white/10">
                            <div class="text-xs font-bold text-slate-400 mb-4 ml-1 uppercase tracking-widest">Parameter</div>
                            @if($project->parameter_input_type === 'text')
                                <div class="bg-black/20 p-4 rounded-xl border border-white/5 text-slate-300 whitespace-pre-wrap text-sm">{{ $project->parameter_text }}</div>
                            @elseif($project->files->count() > 0)
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($project->files as $file)
                                        <div class="flex items-center justify-between bg-white/5 p-3 rounded-xl border border-white/10">
                                            <span class="text-xs text-slate-300 truncate mr-2">{{ $file->file_name }}</span>
                                            <a href="{{ route('projects.files.download', $file) }}" class="text-blue-400 text-xs hover:underline bg-blue-500/10 px-3 py-1 rounded">ダウンロード</a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-sm text-slate-500 italic">パラメーター情報はありません</div>
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
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">対象機種名</label>
                                    <input type="text" name="device_model" value="{{ $project->device_model }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">台数</label>
                                    <input type="number" name="device_count" value="{{ $project->device_count }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">契約日</label>
                                    <input type="date" name="contract_date" value="{{ $project->contract_date }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 [color-scheme:dark]">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">完了予定日</label>
                                    <input type="date" name="completion_date" value="{{ $project->completion_date }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 [color-scheme:dark]">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">予算（暫定単価）</label>
                                    <input type="number" name="price" value="{{ $project->price }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="pt-4 border-t border-white/10">
                                <label class="block text-xs font-bold text-slate-400 mb-4 ml-1 uppercase tracking-widest">Parameter</label>
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
                                    @if($project->files->count() > 0)
                                        <div class="mt-4 grid grid-cols-2 gap-2">
                                            @foreach($project->files as $file)
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
                </div>
            </div>

            {{-- STEP 2: メール生成 (ここは生成アクションのみなので表示切り替えなし) --}}
            @if($isStep1Completed || $currentRank >= 2)
            <div class="relative">
                <div class="absolute -left-4 -top-4 bg-orange-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-orange-400/50">STEP 2</div>
                <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="w-2 h-8 bg-orange-500 rounded-full"></span>見積もり依頼メールの生成
                    </h3>
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
                            <button onclick="navigator.clipboard.writeText(`{{ session('generated_email') }}`).then(() => alert('コピーしました'))" class="absolute top-4 right-4 bg-orange-500/20 text-orange-300 text-xs py-1 px-3 rounded-lg border border-orange-500/30 hover:bg-orange-500 hover:text-white transition-all">コピー</button>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- STEP 3: 回答保存 --}}
            @if($project->estimates->count() > 0)
            <div class="relative">
                <div class="absolute -left-4 -top-4 bg-emerald-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-emerald-400/50">STEP 3</div>
                <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>各社からの見積回答を入力
                        </h3>
                        @if($hasAnyAnswer)
                            <button type="button" onclick="toggleEditMode('step3')" id="btn-edit-step3" class="text-sm bg-white/10 hover:bg-white/20 text-emerald-300 font-bold px-4 py-2 rounded-lg border border-white/20 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                編集する
                            </button>
                        @endif
                    </div>

                    {{-- ▼ STEP 3 表示モード ▼ --}}
                    @if($hasAnyAnswer)
                    <div id="display-mode-step3" class="space-y-4">
                        @foreach($project->estimates as $estimate)
                            <div class="p-6 bg-black/20 rounded-2xl border border-white/5 relative">
                                <div class="text-emerald-400 font-black text-lg tracking-widest mb-3">{{ $estimate->partner_name }}</div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <div class="text-xs font-bold text-slate-400 mb-1">見積金額 (税抜)</div>
                                        <div class="text-white font-mono font-bold text-lg">
                                            {{ $estimate->cost_price ? '¥'.number_format($estimate->cost_price) : '未回答' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-400 mb-1">回答納期</div>
                                        <div class="text-white text-lg">
                                            {{ $estimate->partner_completion_date ? \Carbon\Carbon::parse($estimate->partner_completion_date)->format('Y/m/d') : '未回答' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-400 mb-1">備考</div>
                                        <div class="text-slate-300 text-sm whitespace-pre-wrap">{{ $estimate->partner_message ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- ▼ STEP 3 編集モード (フォーム) ▼ --}}
                    <div id="edit-mode-step3" class="{{ $hasAnyAnswer ? 'hidden' : '' }}">
                        <form action="{{ route('projects.update', $project) }}" method="POST" class="space-y-6">
                            @csrf @method('PUT')
                            @foreach($project->estimates as $estimate)
                                <div class="p-6 bg-white/5 rounded-2xl border border-white/10 space-y-4">
                                    <div class="text-emerald-400 font-black text-sm tracking-widest border-b border-emerald-500/20 pb-2 mb-4">{{ $estimate->partner_name }}</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 mb-1">見積金額 (税抜)</label>
                                            <input type="number" name="estimates[{{ $estimate->id }}][cost_price]" value="{{ $estimate->cost_price }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-emerald-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 mb-1">回答納期</label>
                                            <input type="date" name="estimates[{{ $estimate->id }}][partner_completion_date]" value="{{ $estimate->partner_completion_date }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 [color-scheme:dark]">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-slate-400 mb-1">備考</label>
                                            <textarea name="estimates[{{ $estimate->id }}][partner_message]" rows="2" class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3">{{ $estimate->partner_message }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <div class="flex gap-4 mt-6">
                                @if($hasAnyAnswer)
                                    <button type="button" onclick="cancelEditMode('step3')" class="w-1/3 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-xl transition-all border border-white/10">キャンセル</button>
                                @endif
                                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black py-4 rounded-xl shadow-lg text-lg">
                                    回答情報を保存する
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            {{-- STEP 4: 見積確定・選定 --}}
            @if($isAllAnswered || $currentRank >= 4)
            <div class="relative">
                <div class="absolute -left-4 -top-4 bg-purple-600 text-white font-black px-5 py-1.5 rounded-full text-sm shadow-lg z-10 border border-purple-400/50">STEP 4</div>
                <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 p-8 shadow-2xl">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <span class="w-2 h-8 bg-purple-500 rounded-full"></span>最終見積・発注先の選定
                        </h3>
                        @if($isStep4Completed)
                            <button type="button" onclick="toggleEditMode('step4')" id="btn-edit-step4" class="text-sm bg-white/10 hover:bg-white/20 text-purple-300 font-bold px-4 py-2 rounded-lg border border-white/20 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                編集する
                            </button>
                        @endif
                    </div>

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
                    </div>
                    @endif

                    {{-- ▼ STEP 4 編集モード (フォーム) ▼ --}}
                    <div id="edit-mode-step4" class="{{ $isStep4Completed ? 'hidden' : '' }}">
                        <form action="{{ route('projects.update', $project) }}" method="POST" class="space-y-6">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">顧客への提示金額 (税抜)</label>
                                    <input type="number" name="final_price" value="{{ $project->final_price }}" class="w-full bg-black/40 border-white/30 rounded-xl text-white pl-4 pr-4 py-3 focus:ring-purple-500 font-mono text-lg" placeholder="最終見積額を入力" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">採用する依頼先企業</label>
                                    <select name="partner_name" required class="w-full bg-black/40 border-white/30 rounded-xl text-white px-4 py-3 focus:ring-purple-500 cursor-pointer">
                                        <option value="">企業を選択してください</option>
                                        @foreach($project->estimates as $estimate)
                                            <option value="{{ $estimate->partner_name }}" {{ $project->partner_name == $estimate->partner_name ? 'selected' : '' }}>
                                                {{ $estimate->partner_name }} (回答額: ¥{{ number_format($estimate->cost_price) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
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

                    {{-- 顧客提出用メール生成セクション (表示モードでも編集モードでも使用可能) --}}
                    @if($project->final_price)
                        <div class="mt-8 pt-6 border-t border-white/10">
                            <form action="{{ route('projects.generate_final_email', $project) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-white/5 hover:bg-white/10 text-purple-300 font-bold py-3 rounded-xl border border-purple-500/30 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    顧客提出用メールを生成する
                                </button>
                            </form>

                            @if(session('generated_final_email'))
                                <div class="mt-4 p-5 bg-purple-900/20 rounded-2xl border border-purple-500/30 relative">
                                    <pre class="text-sm text-purple-100 whitespace-pre-wrap font-mono">{{ session('generated_final_email') }}</pre>
                                    <button onclick="navigator.clipboard.writeText(`{{ session('generated_final_email') }}`).then(() => alert('コピーしました'))" class="absolute top-4 right-4 bg-purple-500/20 text-purple-300 text-xs py-1 px-3 rounded-lg border border-purple-500/30 hover:bg-purple-500 hover:text-white transition-all cursor-pointer">コピー</button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @endif

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
    </script>

    {{-- 削除用フォーム（隠し） --}}
    @foreach($project->files as $file)
        <form id="delete-file-form-{{ $file->id }}" action="{{ route('projects.files.delete', $file) }}" method="POST" class="hidden">
            @csrf @method('DELETE')
        </form>
    @endforeach
</x-app-layout>
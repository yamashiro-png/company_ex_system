<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-white tracking-tight">システム管理・マスター設定</h2>
    </x-slot>

    <div class="py-8 min-h-screen"
         x-data="{ activeTab: localStorage.getItem('adminMgmtTab') || 'customers', editUser: null }"
         x-init="$watch('activeTab', v => localStorage.setItem('adminMgmtTab', v))">
        <div class="max-w-[98%] mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <div class="flex justify-end">
                <div class="bg-black/40 border border-white/20 rounded-xl p-2 flex items-center gap-4 shadow-lg">
                    <span class="text-xs font-bold text-slate-300 tracking-widest pl-3">メニュー選択</span>
                    <select x-model="activeTab" class="bg-white/10 border-white/20 rounded-lg text-sm font-bold text-white py-2 pl-4 pr-10 focus:ring-blue-500 cursor-pointer transition-all">
                        <option value="customers" class="bg-slate-900">依頼元（顧客）管理</option>
                        <option value="under_companies" class="bg-slate-900">依頼先会社管理</option>
                        <option value="users" class="bg-slate-900">システムユーザー管理</option>
                        <option value="accessories" class="bg-slate-900">付属品マスタ</option>
                        <option value="projects_list" class="bg-slate-900">全案件の管理・削除</option>
                    </select>
                </div>
            </div>

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
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div x-show="activeTab === 'customers'" x-cloak class="space-y-8">
                @can('system_admin')
                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-blue-500 rounded-full"></span>
                        新規依頼元の登録
                    </h3>
                    <form action="{{ route('customers.store') }}" method="POST" class="flex flex-col md:flex-row gap-4">
                        @csrf
                        <input type="text" name="name" placeholder="新しい依頼元名を入力" class="flex-grow bg-black/40 border-white/30 rounded-xl text-sm focus:ring-blue-500 text-white px-4 py-3" required>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-blue-600/30 text-sm whitespace-nowrap">顧客を登録</button>
                    </form>
                </div>
                @endcan

                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-purple-500 rounded-full"></span>
                        登録済み依頼元 ＆ 案件一覧
                    </h3>
                    <div class="space-y-4">
                        @forelse($customers ?? [] as $customer)
                            <div class="bg-black/20 border border-white/10 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-white/5 transition-all">
                                <div class="flex items-center flex-wrap gap-4 md:gap-8 flex-grow">
                                    <h4 class="text-[16px] font-bold text-sky-300 min-w-[120px]">{{ $customer->name }}</h4>
                                    @php $customerProjects = collect($projects ?? [])->where('customer_id', $customer->id); @endphp
                                    <div class="flex flex-wrap gap-2">
                                        @forelse($customerProjects as $project)
                                            <a href="{{ route('projects.workspace', $project) }}" class="px-3 py-1.5 bg-white/10 hover:bg-blue-600/30 border border-white/20 hover:border-blue-400 rounded-lg text-[px] font-bold text-slate-200 transition-all">{{ $project->name }}</a>
                                        @empty
                                            <span class="text-xs text-slate-500 italic">紐づく案件なし</span>
                                        @endforelse
                                    </div>
                                </div>
                                @can('system_admin')
                                <div class="shrink-0">
                                    <form action="{{ route('admin.customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[16px] bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white py-1.5 px-4 rounded-full font-bold transition-all border border-red-500/30 shadow-md">削除</button>
                                    </form>
                                </div>
                                @endcan
                            </div>
                        @empty
                            <p class="text-center text-slate-400 py-10 text-sm">顧客データがありません。</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'under_companies'" x-cloak class="space-y-8">
                @can('system_admin')
                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-emerald-500 rounded-full"></span>
                        新規依頼先会社の登録
                    </h3>
                    <form action="{{ route('admin.under_companies.store') }}" method="POST" class="flex flex-col md:flex-row gap-4">
                        @csrf
                        <input type="text" name="name" placeholder="新しい依頼先会社名を入力" class="flex-grow bg-black/40 border-white/30 rounded-xl text-sm focus:ring-emerald-500 text-white px-4 py-3" required>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-emerald-600/30 text-sm whitespace-nowrap">会社を登録</button>
                    </form>
                </div>
                @endcan

                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-slate-500 rounded-full"></span>
                        登録済み依頼先会社一覧
                    </h3>
                    <div class="space-y-4">
                        @forelse($underCompanies ?? [] as $company)
                            <div class="bg-black/20 border border-white/10 rounded-2xl p-4 flex items-center justify-between hover:bg-white/5 transition-all">
                                <h4 class="text-[16px] font-bold text-emerald-300">{{ $company->name }}</h4>
                                @can('system_admin')
                                <form action="{{ route('admin.under_companies.destroy', $company) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-[16px] bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white py-1.5 px-4 rounded-full font-bold transition-all border border-red-500/30 shadow-md">削除</button>
                                </form>
                                @endcan
                            </div>
                        @empty
                            <p class="text-center text-slate-400 py-10 text-sm">依頼先会社が登録されていません。</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'users'" x-cloak class="space-y-8">
                @can('admin')
                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-orange-500 rounded-full"></span>
                        新規システムユーザーの登録
                    </h3>
                    <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <input type="text" name="name" placeholder="ユーザー名" class="bg-black/40 border-white/30 rounded-xl text-sm focus:ring-orange-500 text-white px-4 py-3" required>
                            <input type="email" name="email" placeholder="メールアドレス" class="bg-black/40 border-white/30 rounded-xl text-sm focus:ring-orange-500 text-white px-4 py-3" required>
                            <input type="password" name="password" placeholder="パスワード（4文字以上）" class="bg-black/40 border-white/30 rounded-xl text-sm focus:ring-orange-500 text-white px-4 py-3" required>
                            <select name="role" class="bg-black/40 border-white/30 rounded-xl text-sm focus:ring-orange-500 text-white px-4 py-3 cursor-pointer" required>
                                <option value="">権限を選択</option>
                                <option value="user">user</option>
                                <option value="admin">admin</option>
                                @can('system_admin')
                                    <option value="system_admin">system_admin</option>
                                @endcan
                            </select>
                            <select name="supervisor_id" class="bg-black/40 border-white/30 rounded-xl text-sm focus:ring-orange-500 text-white px-4 py-3 cursor-pointer">
                                <option value="">上長を選択（任意）</option>
                                @foreach(($users ?? [])->whereIn('role', ['admin', 'system_admin']) as $supervisorOption)
                                    <option value="{{ $supervisorOption->id }}">{{ $supervisorOption->name }}（{{ $supervisorOption->role }}）</option>
                                @endforeach
                            </select>
                            <select name="company_name" class="bg-black/40 border-white/30 rounded-xl text-sm focus:ring-orange-500 text-white px-4 py-3 cursor-pointer" required>
                                <option value="">所属会社を選択</option>
                                <option value="プロモート">プロモート</option>
                                @foreach($underCompanies ?? [] as $companyOption)
                                    <option value="{{ $companyOption->name }}">{{ $companyOption->name }}</option>
                                @endforeach
                                @foreach($customers ?? [] as $customerOption)
                                    <option value="{{ $customerOption->name }}">{{ $customerOption->name }}</option>
                                @endforeach
                            </select>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold text-slate-400 mb-2 ml-1">担当依頼先会社（任意・複数選択可）</label>
                                @include('admin.partials.under-company-picker', ['underCompanies' => $underCompanies, 'selected' => []])
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
                            <div class="flex-grow">
                                <label class="block text-xs font-bold text-slate-400 mb-1.5 ml-1">印鑑画像（png / jpeg / bmp）<span class="text-red-400">*</span></label>
                                <input type="file" name="stamp" accept=".png,.jpg,.jpeg,.bmp" required
                                       class="block w-full text-sm text-slate-400 file:mr-4 file:py-2.5 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-orange-600 file:text-white hover:file:bg-orange-500 cursor-pointer bg-black/40 rounded-xl border border-white/30">
                            </div>
                            <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white font-bold py-3 px-10 rounded-xl transition-all shadow-lg shadow-orange-600/30 text-sm whitespace-nowrap md:self-end">登録</button>
                        </div>
                    </form>
                </div>
                @endcan

                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-slate-500 rounded-full"></span>
                        登録済みユーザー一覧
                    </h3>
                    <div class="space-y-4">
                        @forelse($users ?? [] as $user)
                            <div class="bg-black/20 border border-white/10 rounded-2xl p-4 flex items-center justify-between hover:bg-white/5 transition-all">
                                <div class="flex items-center gap-6 flex-wrap">
                                    {{-- 印鑑画像 --}}
                                    @if($user->stamp_path)
                                        <img src="{{ asset('storage/' . $user->stamp_path) }}" alt="印鑑" title="{{ $user->name }} の印鑑"
                                             class="w-10 h-10 rounded-full object-cover border-2 border-red-500/40 bg-white">
                                    @else
                                        <div class="w-10 h-10 rounded-full border-2 border-dashed border-white/20 flex items-center justify-center text-[9px] text-slate-600">印</div>
                                    @endif
                                    <h4 class="text-[16px] font-bold text-orange-300">{{ $user->name }}</h4>
                                    <p class="text-[16px] text-slate-200">{{ $user->email }}</p>
                                    @if($user->company_name)
                                        <span class="text-xs text-slate-400">所属：<span class="text-slate-200 font-bold">{{ $user->company_name }}</span></span>
                                    @endif
                                    @if($user->assignedUnderCompanies->isNotEmpty())
                                        <span class="text-xs text-slate-400">担当依頼先：<span class="text-emerald-300 font-bold">{{ $user->assignedUnderCompanies->pluck('name')->implode('、') }}</span></span>
                                    @endif
                                    @php
                                        $roleColor = match($user->role) {
                                            'system_admin' => 'bg-red-500/20 text-red-300 border-red-500/40',
                                            'admin'        => 'bg-orange-500/20 text-orange-300 border-orange-500/40',
                                            default        => 'bg-slate-700 text-slate-400 border-slate-600',
                                        };
                                    @endphp
                                    <span class="px-3 py-1 border rounded-full text-xs font-bold tracking-wider {{ $roleColor }}">{{ $user->role }}</span>
                                    @can('admin')
                                        {{-- 上長の変更フォーム --}}
                                        <form action="{{ route('admin.users.update_supervisor', $user) }}" method="POST" class="flex items-center gap-2">
                                            @csrf @method('PUT')
                                            <span class="text-xs text-slate-400 whitespace-nowrap">上長：</span>
                                            <select name="supervisor_id" onchange="this.form.submit()" class="bg-black/40 border-white/20 rounded-lg text-xs text-white py-1.5 pl-3 pr-8 focus:ring-orange-500 cursor-pointer">
                                                <option value="">未設定</option>
                                                @foreach($users as $supervisorOption)
                                                    @if($supervisorOption->id !== $user->id && in_array($supervisorOption->role, ['admin', 'system_admin']))
                                                        <option value="{{ $supervisorOption->id }}" {{ $user->supervisor_id === $supervisorOption->id ? 'selected' : '' }}>{{ $supervisorOption->name }}（{{ $supervisorOption->role }}）</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        @if($user->supervisor)
                                            <span class="text-xs text-slate-400">上長：<span class="text-slate-200 font-bold">{{ $user->supervisor->name }}</span></span>
                                        @endif
                                    @endcan
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    @can('admin')
                                    <button type="button" @click="editUser = {{ $user->id }}"
                                            class="text-[16px] bg-white/10 hover:bg-orange-600 text-orange-300 hover:text-white py-1.5 px-4 rounded-full font-bold transition-all border border-orange-500/30 shadow-md">
                                        編集
                                    </button>
                                    @endcan
                                    @can('system_admin')
                                    @if(auth()->id() !== $user->id)
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[16px] bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white py-1.5 px-4 rounded-full font-bold transition-all border border-red-500/30 shadow-md">削除</button>
                                    </form>
                                    @endif
                                    @endcan
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-slate-400 py-10 text-sm">ユーザーデータがありません。</p>
                        @endforelse
                    </div>
                </div>

                {{-- ▼ ユーザー編集モーダル（印鑑・所属会社） ▼ --}}
                @can('admin')
                @foreach($users ?? [] as $user)
                    <div x-show="editUser === {{ $user->id }}" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center p-4">
                        {{-- オーバーレイ --}}
                        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="editUser = null"></div>

                        {{-- モーダル本体 --}}
                        <div class="relative w-full max-w-lg bg-slate-900 border border-white/15 rounded-3xl shadow-2xl overflow-hidden"
                             @keydown.escape.window="editUser = null">

                            {{-- ヘッダー --}}
                            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-white/5">
                                <h4 class="font-bold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    {{ $user->name }} の編集
                                </h4>
                                <button type="button" @click="editUser = null" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <form action="{{ route('admin.users.update_profile', $user) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                                @csrf @method('PUT')

                                {{-- 現在の印鑑 --}}
                                <div class="flex items-center gap-4 bg-black/30 border border-white/10 rounded-2xl p-4">
                                    @if($user->stamp_path)
                                        <img src="{{ asset('storage/' . $user->stamp_path) }}" alt="現在の印鑑"
                                             class="w-14 h-14 rounded-full object-cover border-2 border-red-500/40 bg-white">
                                    @else
                                        <div class="w-14 h-14 rounded-full border-2 border-dashed border-white/20 flex items-center justify-center text-[10px] text-slate-600">未登録</div>
                                    @endif
                                    <div>
                                        <div class="text-xs font-bold text-slate-400">現在の印鑑</div>
                                        <div class="text-sm text-slate-300 mt-0.5">{{ $user->stamp_path ? '登録済み' : '未登録' }}</div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1.5">印鑑画像の差し替え（png / jpeg / bmp・未選択なら現在のまま）</label>
                                    <input type="file" name="stamp" accept=".png,.jpg,.jpeg,.bmp"
                                           class="block w-full text-sm text-slate-400 file:mr-4 file:py-2.5 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-orange-600 file:text-white hover:file:bg-orange-500 cursor-pointer bg-black/40 rounded-xl border border-white/30">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1.5">所属会社 <span class="text-red-400">*</span></label>
                                    <select name="company_name" required class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-orange-500 cursor-pointer">
                                        <option value="">所属会社を選択</option>
                                        <option value="プロモート" {{ $user->company_name === 'プロモート' ? 'selected' : '' }}>プロモート</option>
                                        @foreach($underCompanies ?? [] as $companyOption)
                                            <option value="{{ $companyOption->name }}" {{ $user->company_name === $companyOption->name ? 'selected' : '' }}>{{ $companyOption->name }}</option>
                                        @endforeach
                                        @foreach($customers ?? [] as $customerOption)
                                            <option value="{{ $customerOption->name }}" {{ $user->company_name === $customerOption->name ? 'selected' : '' }}>{{ $customerOption->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1.5">担当依頼先会社（複数選択可）</label>
                                    @include('admin.partials.under-company-picker', ['underCompanies' => $underCompanies, 'selected' => $user->assignedUnderCompanies->pluck('id')->all()])
                                </div>

                                <button type="submit" class="w-full bg-orange-600 hover:bg-orange-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-orange-600/30">
                                    保存する
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
                @endcan
            </div>

            <div x-show="activeTab === 'accessories'" x-cloak class="space-y-8">
                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-cyan-500 rounded-full"></span>
                        新規付属品の登録
                    </h3>
                    <form action="{{ route('admin.accessories.store') }}" method="POST" class="flex flex-col md:flex-row gap-4">
                        @csrf
                        <input type="text" name="name" placeholder="新しい付属品名を入力（例：ケース）" class="flex-grow bg-black/40 border-white/30 rounded-xl text-sm focus:ring-cyan-500 text-white px-4 py-3" required>
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-cyan-600/30 text-sm whitespace-nowrap">付属品を登録</button>
                    </form>
                </div>

                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-slate-500 rounded-full"></span>
                        登録済み付属品一覧
                    </h3>
                    <div class="space-y-3">
                        @forelse($accessories ?? [] as $accessory)
                            <div class="bg-black/20 border border-white/10 rounded-2xl p-4 flex items-center justify-between hover:bg-white/5 transition-all">
                                <h4 class="text-[16px] font-bold text-cyan-300">{{ $accessory->name }}</h4>
                                <form action="{{ route('admin.accessories.destroy', $accessory) }}" method="POST" onsubmit="return confirm('付属品「{{ $accessory->name }}」を削除しますか？');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-[16px] bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white py-1.5 px-4 rounded-full font-bold transition-all border border-red-500/30 shadow-md">削除</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-center text-slate-400 py-10 text-sm">付属品が登録されていません。</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'projects_list'" x-cloak class="space-y-8">
                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-red-500 rounded-full"></span>
                        全案件の一括管理・削除
                    </h3>
                    
                    <div class="space-y-4">
                        @forelse($projects ?? [] as $project)
                            <div class="bg-black/20 border border-white/10 rounded-2xl p-4 flex items-center justify-between hover:bg-white/5 transition-all">
                                <div>
                                    <h4 class="text-[16px] font-bold text-white">{{ $project->name }}</h4>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="text-[16px] text-sky-300 font-bold">{{ $project->customer->name ?? '依頼元不明' }}</span>
                                        
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
                                        <span class="px-2.5 py-0.5 border rounded-full text-[16px] font-bold tracking-widest whitespace-nowrap {{ $statusColor }}">
                                            {{ $project->status }}
                                        </span>
                                        </div>
                                </div>
                                @can('system_admin')
                                <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" onsubmit="return confirm({{ \Illuminate\Support\Js::from('本当に案件「' . $project->name . '」を完全に削除しますか？' . "\n" . 'この操作は取り消せません。') }});">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-[16px] bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white py-1.5 px-4 rounded-full font-bold transition-all border border-red-500/30 shadow-md">削除</button>
                                </form>
                                @endcan
                            </div>
                        @empty
                            <p class="text-center text-slate-400 py-10 text-sm">現在、管理できる案件はありません。</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 担当依頼先会社ピッカーの Alpine コンポーネント --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('underCompanyPicker', (items, selected) => ({
                all: items,
                selected: selected,
                query: '',
                open: false,
                selectedItems() {
                    return this.all.filter(i => this.selected.includes(i.id));
                },
                available() {
                    const q = this.query.trim().toLowerCase();
                    return this.all.filter(i =>
                        !this.selected.includes(i.id) &&
                        (q === '' || i.name.toLowerCase().includes(q))
                    );
                },
                add(id) {
                    if (!this.selected.includes(id)) this.selected.push(id);
                    this.query = '';
                    this.open = false;
                },
                remove(id) {
                    this.selected = this.selected.filter(i => i !== id);
                },
            }));
        });
    </script>
</x-app-layout>
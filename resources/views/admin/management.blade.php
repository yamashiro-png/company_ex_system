<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-white tracking-tight">システム管理・マスター設定</h2>
    </x-slot>

    <div class="py-8 min-h-screen" x-data="{ activeTab: 'customers' }">
        <div class="max-w-[98%] mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <div class="flex justify-end">
                <div class="bg-black/40 border border-white/20 rounded-xl p-2 flex items-center gap-4 shadow-lg">
                    <span class="text-xs font-bold text-slate-300 tracking-widest pl-3">メニュー選択</span>
                    <select x-model="activeTab" class="bg-white/10 border-white/20 rounded-lg text-sm font-bold text-white py-2 pl-4 pr-10 focus:ring-blue-500 cursor-pointer transition-all">
                        <option value="customers" class="bg-slate-900">依頼元（顧客）管理</option>
                        <option value="project_create" class="bg-slate-900">新規案件登録</option>
                        <option value="under_companies" class="bg-slate-900">依頼先会社管理</option>
                        <option value="users" class="bg-slate-900">システムユーザー管理</option>
                        <option value="projects_list" class="bg-slate-900">全案件の管理・削除</option>
                    </select>
                </div>
            </div>

            <div x-show="activeTab === 'customers'" x-cloak class="space-y-8">
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
                                <div class="shrink-0">
                                    <form action="{{ route('admin.customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[16px] bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white py-1.5 px-4 rounded-full font-bold transition-all border border-red-500/30 shadow-md">削除</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-slate-400 py-10 text-sm">顧客データがありません。</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'under_companies'" x-cloak class="space-y-8">
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

                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-slate-500 rounded-full"></span>
                        登録済み依頼先会社一覧
                    </h3>
                    <div class="space-y-4">
                        @forelse($underCompanies ?? [] as $company)
                            <div class="bg-black/20 border border-white/10 rounded-2xl p-4 flex items-center justify-between hover:bg-white/5 transition-all">
                                <h4 class="text-[16px] font-bold text-emerald-300">{{ $company->name }}</h4>
                                <form action="{{ route('admin.under_companies.destroy', $company) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-[16px] bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white py-1.5 px-4 rounded-full font-bold transition-all border border-red-500/30 shadow-md">削除</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-center text-slate-400 py-10 text-sm">依頼先会社が登録されていません。</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div x-show="activeTab === 'users'" x-cloak class="space-y-8">
                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-orange-500 rounded-full"></span>
                        新規システムユーザーの登録
                    </h3>
                    <form action="{{ route('admin.users.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        @csrf
                        <input type="text" name="name" placeholder="ユーザー名" class="bg-black/40 border-white/30 rounded-xl text-sm focus:ring-orange-500 text-white px-4 py-3" required>
                        <input type="email" name="email" placeholder="メールアドレス" class="bg-black/40 border-white/30 rounded-xl text-sm focus:ring-orange-500 text-white px-4 py-3" required>
                        <select name="role" class="bg-black/40 border-white/30 rounded-xl text-sm focus:ring-orange-500 text-white px-4 py-3 cursor-pointer" required>
                            <option value="">権限を選択</option>
                            <option value="user">user（一般）</option>
                            <option value="admin">admin（管理者）</option>
                            <option value="system_admin">system_admin（最高管理者）</option>
                        </select>
                        <div class="flex gap-4">
                            <input type="password" name="password" placeholder="パスワード（8文字以上）" class="flex-grow bg-black/40 border-white/30 rounded-xl text-sm focus:ring-orange-500 text-white px-4 py-3" required>
                            <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white font-bold py-3 px-8 rounded-xl transition-all shadow-lg shadow-orange-600/30 text-sm whitespace-nowrap">登録</button>
                        </div>
                    </form>
                </div>

                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-slate-500 rounded-full"></span>
                        登録済みユーザー一覧
                    </h3>
                    <div class="space-y-4">
                        @forelse($users ?? [] as $user)
                            <div class="bg-black/20 border border-white/10 rounded-2xl p-4 flex items-center justify-between hover:bg-white/5 transition-all">
                                <div class="flex items-center gap-6">
                                    <h4 class="text-[16px] font-bold text-orange-300">{{ $user->name }}</h4>
                                    <p class="text-[16px] text-slate-200 mt-1">{{ $user->email }}</p>
                                </div>
                                @if(auth()->id() !== $user->id)
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-[16px] bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white py-1.5 px-4 rounded-full font-bold transition-all border border-red-500/30 shadow-md">削除</button>
                                </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-center text-slate-400 py-10 text-sm">ユーザーデータがありません。</p>
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
                                                '物品入荷待ち' => 'bg-pink-600/30 text-pink-200 border-pink-500/50',
                                                '失注' => 'bg-slate-700 text-slate-400 border-slate-600',
                                                '完了' => 'bg-green-600/30 text-green-200 border-green-500/50',
                                                default => 'bg-slate-700 text-slate-200 border-slate-500',
                                            };
                                        @endphp
                                        <span class="px-2.5 py-0.5 border rounded-full text-[16px] font-bold tracking-widest whitespace-nowrap {{ $statusColor }}">
                                            {{ $project->status }}
                                        </span>
                                        </div>
                                </div>
                                <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" onsubmit="return confirm({{ \Illuminate\Support\Js::from('本当に案件「' . $project->name . '」を完全に削除しますか？' . "\n" . 'この操作は取り消せません。') }});">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-[16px] bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white py-1.5 px-4 rounded-full font-bold transition-all border border-red-500/30 shadow-md">削除</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-center text-slate-400 py-10 text-sm">現在、管理できる案件はありません。</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div x-show="activeTab === 'project_create'" x-cloak class="space-y-8">
                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-yellow-500 rounded-full"></span>
                        新規案件の登録
                    </h3>
                    
                    <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label class="block text-sm font-bold text-slate-300 mb-2">依頼元（顧客） <span class="text-red-400">*</span></label>
                            <select name="customer_id" class="w-full bg-black/40 border-white/30 rounded-xl text-sm focus:ring-yellow-500 text-white px-4 py-3" required>
                                <option value="">顧客を選択してください</option>
                                @foreach($customers ?? [] as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-300 mb-2">案件名 <span class="text-red-400">*</span></label>
                            <input type="text" name="name" class="w-full bg-black/40 border-white/30 rounded-xl text-sm focus:ring-yellow-500 text-white px-4 py-3" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-300 mb-2">担当者名</label>
                                <input type="text" name="pic_name" class="w-full bg-black/40 border-white/30 rounded-xl text-sm focus:ring-yellow-500 text-white px-4 py-3">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-300 mb-2">担当者メールアドレス</label>
                                <input type="email" name="pic_email" class="w-full bg-black/40 border-white/30 rounded-xl text-sm focus:ring-yellow-500 text-white px-4 py-3">
                            </div>
                        </div>
                        
                        

                        <div class="pt-6">
                            <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-500 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-yellow-600/30 text-base">新規案件を登録する</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
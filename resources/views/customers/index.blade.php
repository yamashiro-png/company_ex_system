<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('案件ダッシュボード（作業の開始）') }}
            </h2>
            

        </div>
    </x-slot>

    <div class="py-12 bg-slate-900 min-h-screen text-white">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex gap-6">

            <div class="w-1/3 bg-slate-800 rounded-xl p-6 border border-slate-700 shadow-lg h-[80vh] overflow-y-auto">
                <h3 class="font-bold text-lg mb-4 text-purple-400">1. 依頼元(顧客)を選択</h3>
                <div class="space-y-2">
                    @forelse($customers as $customer)
                        <a href="{{ route('customers.show', $customer) }}" 
                           class="block p-4 rounded-lg border transition-all {{ isset($selectedCustomer) && $selectedCustomer->id === $customer->id ? 'bg-purple-600/20 border-purple-500 text-purple-300' : 'bg-slate-900/50 border-slate-700 hover:bg-slate-700 text-slate-300' }}">
                            <div class="font-bold">{{ $customer->name }}</div>
                            <div class="text-xs text-slate-500 mt-1">案件数: {{ $customer->projects()->count() }}件</div>
                        </a>
                    @empty
                        <p class="text-slate-500 text-sm">登録されている顧客がいません。</p>
                    @endforelse
                </div>
            </div>

            <div class="w-2/3 bg-slate-800 rounded-xl p-6 border border-slate-700 shadow-lg h-[80vh] overflow-y-auto">
                @if(isset($selectedCustomer))
                    <div class="flex justify-between items-end mb-6 border-b border-slate-700 pb-4">
                        <h3 class="font-bold text-xl text-white">
                            <span class="text-purple-400 text-sm block mb-1">選択中の顧客</span>
                            {{ $selectedCustomer->name }} の案件一覧
                        </h3>
                    </div>

                    @if($projects->isEmpty())
                        <div class="text-center py-12 text-slate-500">
                            <p>この顧客にはまだ案件が登録されていません。</p>
                            <a href="{{ route('admin.management') }}" class="inline-block mt-4 text-sm text-purple-400 hover:underline">マスター管理から案件を追加する</a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-4">
                            @foreach($projects as $project)
                                <div class="bg-slate-900 border border-slate-700 p-5 rounded-xl hover:border-purple-500 transition-colors group">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="text-[10px] bg-slate-700 px-2 py-1 rounded text-slate-300">{{ $project->status }}</span>
                                            <h4 class="font-bold text-lg mt-2 group-hover:text-purple-400 transition-colors">{{ $project->name }}</h4>
                                            @if($project->pic_name)
                                                <p class="text-xs text-slate-400 mt-1">担当: {{ $project->pic_name }}</p>
                                            @endif
                                        </div>
                                        
                                        <a href="{{ route('projects.workspace', $project) }}" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-6 rounded-lg shadow-lg shadow-blue-900/50 transition-all flex items-center gap-2">
                                            案件詳細画面
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="h-full flex flex-col items-center justify-center text-slate-500">
                        <svg class="w-16 h-16 mb-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                        <p class="text-lg font-bold">依頼元(顧客)を選択してください</p>
                        <p class="text-sm mt-2">左側のリストから顧客を選択すると、その顧客の案件一覧が表示されます。</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
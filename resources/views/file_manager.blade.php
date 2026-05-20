<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-3xl text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-600 leading-tight flex items-center tracking-tighter">
            <svg class="w-8 h-8 mr-3 text-blue-500 drop-shadow-[0_0_8px_rgba(59,130,246,0.5)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"></path></svg>
            {{ __('File Explorer') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-900 min-h-screen relative overflow-hidden font-sans">
        
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-600 rounded-full mix-blend-screen filter blur-[120px] opacity-20"></div>
        <div class="absolute top-1/2 -right-24 w-80 h-80 bg-purple-600 rounded-full mix-blend-screen filter blur-[100px] opacity-20"></div>

        <div class="max-w-[98%] mx-auto sm:px-6 lg:px-8 space-y-8 relative z-10">

            @if (session('success'))
                <div class="backdrop-blur-md bg-green-500/20 border border-green-500/50 text-green-200 p-4 rounded-2xl shadow-[0_0_15px_rgba(34,197,94,0.2)] animate-pulse" role="alert">
                    <p class="font-bold flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        {{ session('success') }}
                    </p>
                </div>
            @endif

            <div class="backdrop-blur-xl bg-white/5 border border-white/10 shadow-2xl sm:rounded-3xl overflow-hidden transition-all duration-300 hover:border-blue-500/30">
                <div class="bg-gradient-to-r from-blue-500/10 to-transparent px-8 py-5 border-b border-white/5">
                    <h2 class="text-xl font-bold text-blue-100 italic tracking-widest">UPLOAD CENTER</h2>
                </div>
                <div class="p-8">
                    <form action="{{ route('file_manager.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row items-center gap-6">
                        @csrf
                        <div class="relative w-full group">
                            <input type="file" name="file" class="block w-full text-sm text-slate-400 file:mr-6 file:py-3 file:px-8 file:rounded-full file:border-0 file:text-16px file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-500 file:transition-all cursor-pointer bg-slate-800/50 rounded-full border border-white/5 focus:outline-none focus:ring-2 focus:ring-blue-500/50" />
                        </div>
                        <button type="submit" class="w-full md:w-auto px-10 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full font-black text-16px uppercase tracking-tighter shadow-lg shadow-blue-900/40 hover:shadow-blue-500/50 hover:-translate-y-0.5 transition-all active:scale-95">
                            送信
                        </button>
                    </form>
                </div>
            </div>

            <div class="backdrop-blur-xl bg-white/5 border border-white/10 shadow-2xl sm:rounded-3xl overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500/10 to-transparent px-8 py-5 border-b border-white/5 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-purple-100 italic tracking-widest">FILE ARCHIVE</h2>
                    <span class="bg-purple-500/20 text-purple-300 text-xs font-black px-4 py-1.5 rounded-full border border-purple-500/30 uppercase">Items: {{ $documents->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm border-separate border-spacing-y-2 px-4">
                        <thead class="text-slate-400 font-bold uppercase tracking-widest text-[16px]">
                            <tr>
                                <th scope="col" class="px-6 py-4">Name</th>
                                <th scope="col" class="px-6 py-4 text-center">Size</th>
                                <th scope="col" class="px-6 py-4 text-center">Timestamp</th>
                                <th scope="col" class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-200">
                            @foreach ($documents as $document)
                            <tr class="group hover:bg-white/5 transition-all duration-200">
                                <td class="px-6 py-4 rounded-l-2xl border-y border-l border-white/0 group-hover:border-white/10">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-blue-500/10 rounded-lg mr-4 group-hover:bg-blue-500/20 transition-colors">
                                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <span class="font-bold tracking-tight">{{ $document->original_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-400 font-mono border-y border-white/0 group-hover:border-white/10">{{ number_format($document->file_size / 1024, 1) }} KB</td>
                                <td class="px-6 py-4 text-center text-slate-400 border-y border-white/0 group-hover:border-white/10">{{ $document->created_at->format('M d, H:i') }}</td>
                                <td class="px-6 py-4 text-right rounded-r-2xl border-y border-r border-white/0 group-hover:border-white/10">
                                    <div class="flex justify-end space-x-3">
                                        <a href="{{ route('file_manager.download', $document->id) }}" class="p-2 bg-white/5 hover:bg-blue-500/20 text-blue-300 rounded-xl transition-all border border-white/5 hover:border-blue-500/50">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        </a>
                                        <form action="{{ route('file_manager.destroy', $document->id) }}" method="POST" onsubmit="return confirm('DELETE THIS DATA?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-white/5 hover:bg-red-500/20 text-red-400 rounded-xl transition-all border border-white/5 hover:border-red-500/50">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
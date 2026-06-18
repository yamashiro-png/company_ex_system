{{-- 担当依頼先会社の複数選択ピッカー（検索＋チップ表示）
     必要な変数: $underCompanies（コレクション）, $selected（選択中ID配列・任意） --}}
@php
    $pickerItems = ($underCompanies ?? collect())->map(fn($c) => ['id' => (int) $c->id, 'name' => $c->name])->values();
    $pickerSelected = array_values(array_map('intval', $selected ?? []));
@endphp

@if($pickerItems->isEmpty())
    <p class="text-xs text-slate-500">依頼先会社が登録されていません。</p>
@else
<div x-data="underCompanyPicker({{ \Illuminate\Support\Js::from($pickerItems) }}, {{ \Illuminate\Support\Js::from($pickerSelected) }})"
     @click.away="open = false" class="relative">

    {{-- 選択済みチップ --}}
    <div class="flex flex-wrap gap-2" x-show="selected.length" :class="selected.length ? 'mb-2' : ''" x-cloak>
        <template x-for="item in selectedItems()" :key="item.id">
            <span class="flex items-center gap-1.5 bg-orange-600/20 border border-orange-500/50 text-orange-200 text-sm rounded-full pl-3 pr-1.5 py-1">
                <span x-text="item.name"></span>
                <button type="button" @click="remove(item.id)" class="w-4 h-4 flex items-center justify-center rounded-full text-orange-300 hover:bg-orange-500 hover:text-white leading-none">&times;</button>
            </span>
        </template>
    </div>

    {{-- 送信用の hidden input --}}
    <template x-for="id in selected" :key="'h-' + id">
        <input type="hidden" name="assigned_under_company_ids[]" :value="id">
    </template>

    {{-- 検索ボックス --}}
    <input type="text" x-model="query" @focus="open = true"
           placeholder="依頼先会社を検索して追加…"
           class="w-full bg-black/40 border-white/30 rounded-xl text-sm text-white px-4 py-3 focus:ring-orange-500">

    {{-- 候補リスト --}}
    <div x-show="open" x-cloak
         class="absolute z-30 mt-1 w-full max-h-56 overflow-auto bg-slate-900 border border-white/20 rounded-xl shadow-2xl">
        <template x-for="item in available()" :key="item.id">
            <button type="button" @click="add(item.id)"
                    class="block w-full text-left px-4 py-2.5 text-sm text-slate-200 hover:bg-orange-600/30 transition-colors"
                    x-text="item.name"></button>
        </template>
        <div x-show="available().length === 0" class="px-4 py-2.5 text-sm text-slate-500">該当する会社がありません</div>
    </div>
</div>
@endif

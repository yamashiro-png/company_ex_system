@php
    $currentSort = request('sort');
    $currentDirection = request('direction', 'asc');
    $isActive = $currentSort === $field;
    $nextDirection = ($isActive && $currentDirection === 'asc') ? 'desc' : 'asc';
@endphp

<a href="{{ request()->fullUrlWithQuery(['sort' => $field, 'direction' => $nextDirection]) }}" 
   class="flex items-center gap-1 hover:text-blue-400 transition-colors cursor-pointer whitespace-nowrap mb-2">
    {{ $label }}
    
    @if($isActive)
        @if($currentDirection === 'asc')
            <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
        @else
            <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        @endif
    @else
        <svg class="w-3 h-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
    @endif
</a>